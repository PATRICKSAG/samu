
<?php
include_once(__DIR__ . '/../persistencia/conexion.php');

function listarProvincias(PDO $pdo)
{
    $sql = "SELECT * FROM provincia WHERE activo = 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    return $data;
}


function listarProvinciasPorDepartamento(PDO $pdo, $idDepartamento)
{
    $sql = "SELECT idProvincia, nombre FROM provincia WHERE idDepartamento = ? AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idDepartamento]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
if (isset($_POST['idDepartamento'])) {
    $pdo = Database::getConexion();
    $idDepartamento = $_POST['idDepartamento'];
   
    $provincias = listarProvinciasPorDepartamento($pdo, $idDepartamento);

    header('Content-Type: application/json');
    echo json_encode($provincias);
    exit;
}
