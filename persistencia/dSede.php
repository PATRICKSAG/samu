<?php
//dSede.php
function insertarSede(PDO $pdo, array $data)
{
    $idEstablecimiento = $data['idEstablecimiento'];
    $nombreComercial = $data['nombre'];
    $numeroEstacion = $data['numeroEstacion'] ?? null;
    $fechaRegistroSi = $data['fechaRegistroSi'] ?? null;
    $idCategoria = $data['idCategoria'] ?? null;
    $idSituacionEstablecimiento = $data['idSituacionEstablecimiento'] ?? null;
    $direccion = $data['direccion'];
    $telefono = $data['telefono'] ?? null;
    $tieneQuimicoFarmaceutico = $data['tieneQuimicoFarmaceutico'] ?? 0;
    $idDepartamento = $data['idDepartamento'] ?? null;
    $idProvincia = $data['idProvincia'] ?? null;
    $idDistrito = $data['idDistrito'] ?? null;
    $horarioFuncionamiento = $data['horarioFuncionamiento'] ?? null;
    $idSituacionDigemid = $data['idSituacionDigemid'] ?? null;
    // Nuevos campos
    $areaOrigen = $data['areaOrigen'] ?? null;
    $idEstadoRenipress = $data['idEstadoRenipress'] ?? null;
    $idInstitucionRenipress = $data['idInstitucionRenipress'] ?? null;
    $idTipoRenipress = $data['idTipoRenipress'] ?? null;
    $idClasificacionRenipress = $data['idClasificacionRenipress'] ?? null;
    // NUEVOS CAMPOS UFRESBIT
    $categorizacion = $data['categorizacion'] ?? null;
    $inicioActividad = $data['inicioActividad'] ?? null;

    $sql = "INSERT INTO sede (
                idEstablecimiento, nombre, numeroEstacion, fechaRegistroSi, 
                idCategoria, idSituacionEstablecimiento, direccion, telefono, 
                tieneQuimicoFarmaceutico, idSituacionDigemid, 
                idDepartamento, idProvincia, idDistrito, horarioFuncionamiento,
                areaOrigen, idEstadoRenipress, idInstitucionRenipress, 
                idTipoRenipress, idClasificacionRenipress,
                categorizacion, inicioActividad
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idEstablecimiento, $nombreComercial, $numeroEstacion, $fechaRegistroSi,
        $idCategoria, $idSituacionEstablecimiento, $direccion, $telefono,
        $tieneQuimicoFarmaceutico, $idSituacionDigemid,
        $idDepartamento, $idProvincia, $idDistrito, $horarioFuncionamiento,
        $areaOrigen, $idEstadoRenipress, $idInstitucionRenipress,
        $idTipoRenipress, $idClasificacionRenipress,
        $categorizacion, $inicioActividad
    ]);
    return $pdo->lastInsertId();
}

function actualizarSede(PDO $pdo, array $data)
{
    $idSede = $data['idSede'] ?? null;
    $idEstablecimiento = $data['idEstablecimiento'] ?? null;
    $nombreComercial = $data['nombre'] ?? null;
    $numeroEstacion = $data['numeroEstacion'] ?? null;
    $fechaRegistroSi = $data['fechaRegistroSi'] ?? null;
    $idCategoria = $data['idCategoria'] ?? null;
    $idSituacionEstablecimiento = $data['idSituacionEstablecimiento'] ?? null;
    $direccion = $data['direccion'] ?? null;
    $telefono = $data['telefono'] ?? null;
    $tieneQuimicoFarmaceutico = $data['tieneQuimicoFarmaceutico'] ?? 0;
    $idDepartamento = $data['idDepartamento'] ?? null;
    $idProvincia = $data['idProvincia'] ?? null;
    $idDistrito = $data['idDistrito'] ?? null;
    $horarioFuncionamiento = $data['horarioFuncionamiento'] ?? null;
    $idSituacionDigemid = $data['idSituacionDigemid'] ?? null;
    // Nuevos campos
    $areaOrigen = $data['areaOrigen'] ?? null;
    $idEstadoRenipress = $data['idEstadoRenipress'] ?? null;
    $idInstitucionRenipress = $data['idInstitucionRenipress'] ?? null;
    $idTipoRenipress = $data['idTipoRenipress'] ?? null;
    $idClasificacionRenipress = $data['idClasificacionRenipress'] ?? null;
    // NUEVOS CAMPOS UFRESBIT
    $categorizacion = $data['categorizacion'] ?? null;
    $inicioActividad = $data['inicioActividad'] ?? null;

    $sql = "UPDATE sede SET 
                idEstablecimiento = ?,
                nombre = ?,
                numeroEstacion = ?,
                fechaRegistroSi = ?,
                idCategoria = ?,
                idSituacionEstablecimiento = ?,
                direccion = ?,
                telefono = ?,
                tieneQuimicoFarmaceutico = ?,
                idSituacionDigemid = ?,
                idDepartamento = ?,
                idProvincia = ?,
                idDistrito = ?,
                horarioFuncionamiento = ?,
                areaOrigen = ?,
                idEstadoRenipress = ?,
                idInstitucionRenipress = ?,
                idTipoRenipress = ?,
                idClasificacionRenipress = ?,
                categorizacion = ?,
                inicioActividad = ?
            WHERE idSede = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idEstablecimiento, $nombreComercial, $numeroEstacion, $fechaRegistroSi,
        $idCategoria, $idSituacionEstablecimiento, $direccion, $telefono,
        $tieneQuimicoFarmaceutico, $idSituacionDigemid,
        $idDepartamento, $idProvincia, $idDistrito, $horarioFuncionamiento,
        $areaOrigen, $idEstadoRenipress, $idInstitucionRenipress,
        $idTipoRenipress, $idClasificacionRenipress,
        $categorizacion, $inicioActividad,
        $idSede
    ]);
    return $stmt->rowCount();
}


function listarSedes(PDO $pdo)
{
    $sql = "SELECT 
                s.idSede,
                s.idEstablecimiento,
                s.idCategoria,
                s.idSituacionEstablecimiento,
                s.idSituacionDigemid,
                s.idDepartamento,
                s.idProvincia,
                s.idDistrito,
                s.numeroEstacion,
                s.nombre,
                s.fechaRegistroSi, 
                s.direccion,
                s.tieneQuimicoFarmaceutico,
                s.horarioFuncionamiento,
                s.telefono,
                CASE 
                    WHEN s.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE s.areaOrigen 
                END AS areaOrigen,
                s.idEstadoRenipress,
                s.idInstitucionRenipress,
                s.idTipoRenipress,
                s.idClasificacionRenipress,
                s.categorizacion,
                s.inicioActividad,
                (SELECT c.nombre FROM categoria c WHERE c.idCategoria = s.idCategoria) AS categoria,
                (SELECT e.ruc FROM establecimiento e WHERE e.idEstablecimiento = s.idEstablecimiento) AS ruc,
                (SELECT e.razonSocial FROM establecimiento e WHERE e.idEstablecimiento = s.idEstablecimiento) AS razonSocial,
                (SELECT p.nombre FROM provincia p WHERE p.idProvincia = s.idProvincia) AS provincia,
                (SELECT d.nombre FROM distrito d WHERE d.idDistrito = s.idDistrito) AS distrito,
                (SELECT se.nombre FROM situacion_establecimiento se WHERE se.idSituacionEstablecimiento = s.idSituacionEstablecimiento) AS situacion_establecimiento,
                (SELECT sd.nombre FROM situacion_digemid sd WHERE sd.idSituacionDigemid = s.idSituacionDigemid) AS Situacion_Digemid,
                (SELECT er.descripcion FROM estadoRenipress er WHERE er.id_estado = s.idEstadoRenipress) AS estadoRenipress,
                (SELECT ir.nombre FROM InsticionesRenipress ir WHERE ir.idInsticionRenipress = s.idInstitucionRenipress) AS institucionRenipress,
                (SELECT tr.nombre FROM tipoRenipress tr WHERE tr.idTipoRenipress = s.idTipoRenipress) AS tipoRenipress,
                (SELECT cr.nombre FROM clasificacionRenipress cr WHERE cr.idClasificacionRenipress = s.idClasificacionRenipress) AS clasificacionRenipress
            FROM sede s 
            WHERE s.activo = 1";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function eliminarSede(PDO $pdo, $idSede)
{
    // Borrado lógico (actualizar activo = 0)
    $sql = "UPDATE sede SET activo = 0 WHERE idSede = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSede]);
    return $stmt->rowCount();
}

/**
 * Obtiene los datos completos de una sede incluyendo nombres de FK
 */
function obtenerSedeCompleta(PDO $pdo, $idSede)
{
    $sql = "SELECT 
                s.*,
                dep.nombre AS departamento_nombre,
                prov.nombre AS provincia_nombre,
                dist.nombre AS distrito_nombre,
                se.nombre AS situacion_establecimiento_nombre,
                er.descripcion AS estado_renipress_nombre,
                ir.nombre AS institucion_renipress_nombre,
                tr.nombre AS tipo_renipress_nombre,
                cr.nombre AS clasificacion_renipress_nombre
            FROM sede s
            LEFT JOIN departamento dep ON s.idDepartamento = dep.idDepartamento
            LEFT JOIN provincia prov ON s.idProvincia = prov.idProvincia
            LEFT JOIN distrito dist ON s.idDistrito = dist.idDistrito
            LEFT JOIN situacion_establecimiento se ON s.idSituacionEstablecimiento = se.idSituacionEstablecimiento
            LEFT JOIN estadoRenipress er ON s.idEstadoRenipress = er.id_estado
            LEFT JOIN InsticionesRenipress ir ON s.idInstitucionRenipress = ir.idInsticionRenipress
            LEFT JOIN tipoRenipress tr ON s.idTipoRenipress = tr.idTipoRenipress
            LEFT JOIN clasificacionRenipress cr ON s.idClasificacionRenipress = cr.idClasificacionRenipress
            WHERE s.idSede = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSede]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================
// ENDPOINT AJAX PARA OBTENER DATOS DE SEDE
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'obtenerSedeCompleta' && isset($_POST['idSede'])) {
    include_once(__DIR__ . '/conexion.php');
    $pdo = Database::getConexion();
    $idSede = intval($_POST['idSede']);
    $data = obtenerSedeCompleta($pdo, $idSede);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'actualizarSedeDesdeExpediente') {
    include_once(__DIR__ . '/conexion.php');
    $pdo = Database::getConexion();
    $data = $_POST;

    if (empty($data['idSede'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de sede no proporcionado']);
        exit;
    }

    // Lista de campos que se pueden actualizar desde este formulario
    $camposPermitidos = [
        'idDepartamento',
        'idProvincia',
        'idDistrito',
        'idSituacionEstablecimiento',
        'direccion',
        'idEstadoRenipress',
        'idInstitucionRenipress',
        'idTipoRenipress',
        'idClasificacionRenipress',
        'categorizacion',
        'inicioActividad',
        'tieneQuimicoFarmaceutico' // solo si se usa para UFREMID, pero para UFRESBIT no se envía
    ];

    $updateData = ['idSede' => $data['idSede']];

    // Solo agregar al array los campos que realmente vienen en la petición
    foreach ($camposPermitidos as $campo) {
        if (isset($data[$campo])) {
            $updateData[$campo] = $data[$campo];
        }
    }

    try {
        $result = actualizarSedeParcial($pdo, $updateData);
        if ($result !== false) {
            echo json_encode(['success' => true, 'message' => 'Sede actualizada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se realizaron cambios']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

function actualizarSedeParcial(PDO $pdo, array $data)
{
    $idSede = $data['idSede'] ?? null;
    if (!$idSede) {
        throw new Exception("ID de sede no proporcionado");
    }

    // Construir el SET dinámicamente solo con los campos que vienen
    $campos = [];
    $params = [];

    $camposPermitidos = [
        'idDepartamento',
        'idProvincia',
        'idDistrito',
        'idSituacionEstablecimiento',
        'direccion',
        'idEstadoRenipress',
        'idInstitucionRenipress',
        'idTipoRenipress',
        'idClasificacionRenipress',
        'categorizacion',
        'inicioActividad'
    ];

    foreach ($camposPermitidos as $campo) {
        if (array_key_exists($campo, $data)) {
            $campos[] = "$campo = ?";
            $params[] = $data[$campo];
        }
    }

    if (empty($campos)) {
        return 0; // No hay nada que actualizar
    }

    $params[] = $idSede;
    $sql = "UPDATE sede SET " . implode(", ", $campos) . " WHERE idSede = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}