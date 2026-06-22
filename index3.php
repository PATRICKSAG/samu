<?php

// Incluir el autoload de Composer
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=localhost;dbname=bd_samu", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo 'Error al conectarse a la base de datos: ', $e->getMessage();
}

$nombreArchivo = 'EXPEDIENTES 2024  24-02-2025 ACTUAL.xlsx';

// Cargar el archivo de Excel
$spreadsheet = IOFactory::load($nombreArchivo);

// Seleccionar la primera hoja
$sheet = $spreadsheet->getActiveSheet();

$filaInicio = 4;

// Recorrer todas las filas de la hoja
foreach ($sheet->getRowIterator($filaInicio) as $numRow => $row) {
    $cellIterator = $row->getCellIterator('A');
    $cellIterator->setIterateOnlyExistingCells(false); // Asegurarse de iterar todas las celdas, incluso si están vacías

    $data = [];
    foreach ($cellIterator as $index => $cell) {
        $value = $cell->getValue();

        // Eliminar espacios si el valor es un string
        if (is_string($value)) {
            $value = trim($value);
        }

        // Verificar si es una fecha (si es numérico, Excel la guarda como número)
        if ($index == "N" && is_numeric($value)) { // Asumiendo que la fecha está en la columna 3 (index 2)
            $dateTime = Date::excelToDateTimeObject($value); // Convertir a DateTime
            $value = $dateTime->format('Y-m-d'); // Formato compatible con MySQL
        }
        $data[] = $value;  // Obtener el valor de cada celda
    }

    try {

        $resgistrado = $data[55];

        if ($resgistrado == 1) {
            throw new Exception('La inspección ya ha sido registrada.', 200);
        }

        $ruc = $data[9];
        $situacionDigimed = $data[0];
        $direccion = $data[10];

        $establecimiento = buscarEstablecimientoPorRuc($pdo, $ruc);

        // EXISTE EMPRESA ?
        if ($establecimiento) {
            $idEstablecimiento = $establecimiento['idEstablecimiento'];
        } else {
            // REGISTRAR EMPRESA INFORMAL
            $razonSocial = $data[8];
            $idEstablecimiento = insertarEstablecimiento($pdo, [
                'ruc' => $ruc,
                'razonSocial' => $razonSocial,
                'nombreComercial' => null,
                'responsableLegal' => null,
                'informal' => 1
            ]);
        }

        $sedes = buscarSedesPorIdEstablecimiento($pdo, $idEstablecimiento);

        // TIENE UNA SOLA SEDE ?
        if (count($sedes) == 1) {
            $sede = $sedes[0];
            $idSede = $sede['idSede'];
        } else {
            $idSede = $data[0]; // TOMAR ID SEDE INGRESADA MANUALMENTE
            $sede = buscarSedePorId($pdo, $idSede);
            if (!$sede) {

                $sede = buscarSedePorRucYDireccion($pdo, $ruc, $direccion);

                $idSituacionDigemid = null;

                if ($situacionDigimed == 'CERRADO TEMPORAL') {
                    $idSituacionDigemid = 2;
                }

                if ($situacionDigimed == 'CERRADO DEFINITIVO') {
                    $idSituacionDigemid = 3;
                }

                if ($situacionDigimed == 'ANULADO') {
                    $idSituacionDigemid = 4;
                }
                
                if ($situacionDigimed == 'RUC INCORRECTO') {
                    $idSituacionDigemid = 5;
                }

                if (!$sede) {
                    $idSede = insertarSede($pdo, [
                        'nombre' => $data[8],
                        'idEstablecimiento' => $idEstablecimiento,
                        'idSituacionEstablecimiento' => $idSituacionDigemid ? 1 : 2,
                        'numeroEstacion' => null,
                        'fechaRegistroSi' => null,
                        'idCategoria' => null,
                        'direccion' => $data[10],
                        'telefono' => null,
                        'tieneQuimicoFarmaceutico' => 0,
                        'idDepartamento' => null,
                        'idProvincia' => null,
                        'idDistrito' => null,
                        'horarioFuncionamiento' => null,
                        'idSituacionDigemid' => $idSituacionDigemid,
                    ]);
                } else {
                    $idSede = $sede['idSede'];
                }
            }
        }

        // REGISTRAR INSPECCION

        $codigoInterno = $data[2];
        $judicializado = $data[3];
        $responsable = $data[4];
        $observacion = $data[5];
        $numeroFolios = $data[6];
        $numeroActa = $data[12];
        $fechaInspeccion = $data[13];
        $informeTecnicoInspeccion = $data[14];

        $idExpediente = insertarExpediente($pdo, [
            'idSede' => $idSede,
            'codigoInterno' => $codigoInterno,
            'judicializado' => $judicializado,
            'responsable' => $responsable,
            'observacion' => $observacion,
            'numeroFolios' => $numeroFolios,
            'numeroActa' => $numeroActa,
            'fechaInspeccion' => $fechaInspeccion,
            'informeTecnicoInspeccion' => $informeTecnicoInspeccion,
        ]);

        $sheet->setCellValue('BD' . $row->getRowIndex(), 1);
    } catch (Exception $e) {
        if ($e->getCode() != 200) {
            echo 'ERROR: Fila ' . '[' . $numRow . '] - ' . $e->getMessage() . '<br/>';
            error_log('ERROR: Fila ' . '[' . $numRow . '] - ' . $e->getMessage());
        }
    }
}

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($nombreArchivo);


// METODOS
function buscarEstablecimientoPorRuc(PDO $pdo, $ruc)
{
    $sql = "SELECT idEstablecimiento FROM establecimiento WHERE ruc = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ruc]);
    $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    return $establecimiento;
}

function buscarSedesPorIdEstablecimiento(PDO $pdo, $idEstablecimiento)
{
    $sql = "SELECT idSede, numeroEstacion, direccion FROM sede WHERE idEstablecimiento = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEstablecimiento]);
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $sedes;
}

function buscarSedePorId(PDO $pdo, $idSede)
{
    $sql = "SELECT idSede, numeroEstacion, direccion FROM sede WHERE idSede = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSede]);
    $sede = $stmt->fetch(PDO::FETCH_ASSOC);
    return $sede;
}

function insertarExpediente(PDO $pdo, array $data)
{
    $idSede = $data['idSede'];
    $codigoInterno = $data['codigoInterno'];
    $judicializado = $data['judicializado'];
    $responsable = $data['responsable'];
    $observacion = $data['observacion'];
    $numeroFolios = $data['numeroFolios'];
    $numeroActa = $data['numeroActa'];
    $fechaInspeccion = $data['fechaInspeccion'];
    $informeTecnicoInspeccion = $data['informeTecnicoInspeccion'];

    $sql = "INSERT INTO expediente(idSede, codigoInterno, judicializado, responsable, observacion, numeroFolios, numeroActa, fechaInspeccion, informeTecnicoInspeccion) VALUES(?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSede, $codigoInterno, $judicializado, $responsable, $observacion, $numeroFolios, $numeroActa, $fechaInspeccion, $informeTecnicoInspeccion]);
    return $pdo->lastInsertId();
}

function insertarEstablecimiento(PDO $pdo, array $data)
{
    $ruc = $data['ruc'];
    $razonSocial = $data['razonSocial'];
    $nombreComercial = $data['nombreComercial'];
    $responsableLegal = $data['responsableLegal'];
    $informal = $data['informal'];

    $sql = "INSERT INTO establecimiento(ruc, razonSocial, nombreComercial, responsableLegal, informal) VALUES(?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ruc, $razonSocial, $nombreComercial, $responsableLegal, $informal]);
    return $pdo->lastInsertId();
}

function insertarSede(PDO $pdo, array $data)
{
    $nombre = $data['nombre'];
    $idEstablecimiento = $data['idEstablecimiento'];
    $numeroEstacion = $data['numeroEstacion'];
    $fechaRegistroSi = $data['fechaRegistroSi'];
    $idCategoria = $data['idCategoria'];
    $idSituacionEstablecimiento = $data['idSituacionEstablecimiento'];
    $direccion = $data['direccion'];
    $telefono = $data['telefono'];
    $tieneQuimicoFarmaceutico = $data['tieneQuimicoFarmaceutico'];
    $idDepartamento = $data['idDepartamento'];
    $idProvincia = $data['idProvincia'];
    $idDistrito = $data['idDistrito'];
    $horarioFuncionamiento = $data['horarioFuncionamiento'];
    $idSituacionDigemid = $data['idSituacionDigemid'];

    $sql = "INSERT INTO sede(idEstablecimiento, nombre, numeroEstacion, fechaRegistroSi, idCategoria, idSituacionEstablecimiento, direccion, telefono, tieneQuimicoFarmaceutico, idDepartamento, idProvincia, idDistrito, horarioFuncionamiento, idSituacionDigemid) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEstablecimiento, $nombre, $numeroEstacion, $fechaRegistroSi, $idCategoria, $idSituacionEstablecimiento, $direccion, $telefono, $tieneQuimicoFarmaceutico, $idDepartamento, $idProvincia, $idDistrito, $horarioFuncionamiento, $idSituacionDigemid]);
    return $pdo->lastInsertId();
}

function buscarSedePorRucYDireccion(PDO $pdo, $ruc, $direccion)
{
    $sql = "SELECT * FROM sede s INNER JOIN establecimiento e ON s.idEstablecimiento = e.idEstablecimiento WHERE s.direccion = ? AND e.ruc = ?;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$direccion, $ruc]);
    $sede = $stmt->fetch(PDO::FETCH_ASSOC);
    return $sede;
}
