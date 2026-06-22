<?php
include_once(__DIR__ . '/../persistencia/conexion.php');


function listarDistritos(PDO $pdo)
{
    $sql = "SELECT * FROM distrito WHERE activo = 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    return $data;
}

function listarDistritosPorProvincia(PDO $pdo, $idProvincia)
{
    $sql = "SELECT idDistrito, nombre FROM distrito WHERE idProvincia = ?  AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idProvincia]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
if (isset($_POST['idProvincia'])) {
    $pdo = Database::getConexion();
    $idProvincia = $_POST['idProvincia'];
   
    $distritos = listarDistritosPorProvincia($pdo, $idProvincia);

    header('Content-Type: application/json');
    echo json_encode($distritos);
    exit;
}