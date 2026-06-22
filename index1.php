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

// Cargar el archivo de Excel
$spreadsheet = IOFactory::load('EF REGISTRADOS EN EL SI DIGEMID (1).xlsx');

// Seleccionar la primera hoja
$sheet = $spreadsheet->getActiveSheet();

// Recorrer todas las filas de la hoja
foreach ($sheet->getRowIterator(5) as $row) {
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
        if ($index == "B" && is_numeric($value)) { // Asumiendo que la fecha está en la columna 3 (index 2)
            $dateTime = Date::excelToDateTimeObject($value); // Convertir a DateTime
            $value = $dateTime->format('Y-m-d'); // Formato compatible con MySQL
        }
        $data[] = $value;  // Obtener el valor de cada celda
    }

    try {

        $numeroEstacion = $data[0];
        $fechaRegistroSi = $data[1];
        $categoria =  buscarCategoriaPorNombre($pdo, $data[2]);
        if ($categoria) {
            $idCategoria = $categoria['idCategoria'];
        } else {
            $idCategoria = null;
        }

        $direccion = $data[6];
        $telefono = $data[7];

        if ($data[8] == "SI") {
            $tieneQuimicoFarmaceutico = 1;
        }

        if ($data[8] == "NO") {
            $tieneQuimicoFarmaceutico = 0;
        }

        $distrito = buscarDistritoPorNombre($pdo, $data[10]);
        if ($distrito) {
            $idDistrito = $distrito['idDistrito'];
            // $idProvincia = $distrito['idProvincia'];
            $provincia = buscarProvinciaPorNombre($pdo, $data[9]);
            $idProvincia = $provincia['idProvincia'];
            if ($provincia) {
                $idDepartamento = $provincia['idDepartamento'];
            } else {
                $idDepartamento = null;
            }
        } else {
            $idDepartamento = null;
            $idProvincia = null;
            $idDistrito = null;
        }

        $horarioFuncionamiento = $data[11];


        $pdo->beginTransaction(); // Iniciar la transaccion

        // Buscar establecimeiento por RUC
        $ruc = $data[5];
        $establecimiento = buscarEstablecimientoPorRuc($pdo, $ruc);
        if (!$establecimiento) {
            $razonSocial = $data[3];
            $responsableLegal = null;
            $idEstablecimiento = insertarEstablecimiento($pdo, [
                'ruc' => $ruc,
                'razonSocial' => $razonSocial,
                'nombreComercial' => null,
                'responsableLegal' => $responsableLegal,
                'informal' => 0
            ]);
        } else {
            $idEstablecimiento = $establecimiento['idEstablecimiento'];
        }

        $nombreComercial = $data[4];

        insertarSede($pdo, [
            'nombre' => $nombreComercial,
            'idEstablecimiento' => $idEstablecimiento,
            'idSituacionEstablecimiento' => 1,
            'numeroEstacion' => $numeroEstacion,
            'fechaRegistroSi' => $fechaRegistroSi,
            'idCategoria' => $idCategoria,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'tieneQuimicoFarmaceutico' => $tieneQuimicoFarmaceutico,
            'idDepartamento' => $idDepartamento,
            'idProvincia' => $idProvincia,
            'idDistrito' => $idDistrito,
            'horarioFuncionamiento' => $horarioFuncionamiento,
            'idSituacionDigemid' => 1,
        ]);

        $pdo->commit(); // Confirmar la transaccion

    } catch (Exception $e) {
        $pdo->rollBack(); // Cancelar la transaccion
        echo 'ERROR: ' . $e->getMessage() . '<br/>';
        error_log('ERROR: ' . $e->getMessage());
    }
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

function buscarEstablecimientoPorRuc(PDO $pdo, $ruc)
{
    $sql = "SELECT idEstablecimiento FROM establecimiento WHERE ruc = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ruc]);
    $establecimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    return $establecimiento;
}

function insertarSede(PDO $pdo, array $data)
{
    $nombre = $data['nombre'];
    $idEstablecimiento = $data['idEstablecimiento'];
    $numeroEstacion = $data['numeroEstacion'];
    $fechaRegistroSi = $data['fechaRegistroSi'];
    $idSituacionEstablecimiento = $data['idSituacionEstablecimiento'];
    $idCategoria = $data['idCategoria'];
    $direccion = $data['direccion'];
    $telefono = $data['telefono'];
    $tieneQuimicoFarmaceutico = $data['tieneQuimicoFarmaceutico'];
    $idDepartamento = $data['idDepartamento'];
    $idProvincia = $data['idProvincia'];
    $idDistrito = $data['idDistrito'];
    $horarioFuncionamiento = $data['horarioFuncionamiento'];
    $idSituacionDigemid = $data['idSituacionDigemid'];

    $sql = "INSERT INTO sede(nombre, idEstablecimiento, numeroEstacion, fechaRegistroSi, idSituacionEstablecimiento, idCategoria, direccion, telefono, tieneQuimicoFarmaceutico, idDepartamento, idProvincia, idDistrito, horarioFuncionamiento, idSituacionDigemid) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $idEstablecimiento, $numeroEstacion, $fechaRegistroSi, $idSituacionEstablecimiento, $idCategoria, $direccion, $telefono, $tieneQuimicoFarmaceutico, $idDepartamento, $idProvincia, $idDistrito, $horarioFuncionamiento, $idSituacionDigemid]);
}

function buscarDistritoPorNombre(PDO $pdo, $nombre)
{
    $sql = "SELECT idDistrito, idProvincia FROM distrito WHERE nombre = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre]);
    $distrito = $stmt->fetch(PDO::FETCH_ASSOC);
    return $distrito;
}

function buscarProvinciaPorNombre(PDO $pdo, $nombre)
{
    $sql = "SELECT idProvincia, idDepartamento FROM provincia WHERE nombre = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre]);
    $Provincia = $stmt->fetch(PDO::FETCH_ASSOC);
    return $Provincia;
}

function buscarCategoriaPorNombre(PDO $pdo, $nombre)
{
    $sql = "SELECT idCategoria FROM categoria WHERE nombre = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    return $categoria;
}
