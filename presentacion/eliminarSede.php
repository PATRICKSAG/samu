<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dSede.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: formSede.php?mensaje=" . urlencode("ID de sede no proporcionado."));
    exit;
}

$idSede = intval($_GET['id']);

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $filasAfectadas = eliminarSede($pdo, $idSede);
    if ($filasAfectadas > 0) {
        $mensaje = "Sede eliminada correctamente.";
    } else {
        $mensaje = "No se encontró la sede o ya estaba eliminada.";
    }
} catch (PDOException $e) {
    $mensaje = "Error al eliminar: " . $e->getMessage();
}

header("Location: formSede.php?mensaje=" . urlencode($mensaje));
exit;