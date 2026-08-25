<?php
//dRenipress.php
function listarEstadosRenipress(PDO $pdo)
{
    $sql = "SELECT id_estado, descripcion FROM estadoRenipress ORDER BY descripcion";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarInstitucionesRenipress(PDO $pdo)
{
    $sql = "SELECT idInsticionRenipress, nombre FROM InsticionesRenipress ORDER BY nombre"; 
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarTiposRenipress(PDO $pdo)
{
    $sql = "SELECT idTipoRenipress, nombre FROM tipoRenipress ORDER BY nombre";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarClasificacionesPorTipo(PDO $pdo, $idTipo)
{
    $sql = "SELECT c.idClasificacionRenipress, c.nombre 
            FROM clasificacionRenipress c 
            WHERE c.idTipoRenipress = ? 
            ORDER BY c.nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idTipo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Si se necesita listar todas las clasificaciones (sin filtrar) para algún caso
function listarClasificacionesRenipress(PDO $pdo)
{
    $sql = "SELECT idClasificacionRenipress, nombre FROM clasificacionRenipress ORDER BY nombre";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Si se recibe una petición POST con idTipo, devolver clasificaciones en JSON
if (isset($_POST['idTipo'])) {
    include_once(__DIR__ . '/conexion.php');
    $pdo = Database::getConexion();
    $idTipo = $_POST['idTipo'];
    $clasificaciones = listarClasificacionesPorTipo($pdo, $idTipo);
    header('Content-Type: application/json');
    echo json_encode($clasificaciones);
    exit;
}