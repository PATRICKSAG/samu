<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dEstablecimiento.php');
// VERIFICACIÓN DE SESIÓN (AGREGAR ESTO)
include_once(__DIR__ . '/auth_check.php');


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: formEstablecimiento.php?mensaje=" . urlencode("ID no válido"));
    exit;
}

$id = intval($_GET['id']);
$pdo = Database::getConexion();
$resultado = eliminarEstablecimiento($pdo, $id);

$mensaje = $resultado ? "Establecimiento eliminado correctamente." : "Error al eliminar el establecimiento.";
header("Location: formEstablecimiento.php?mensaje=" . urlencode($mensaje));
exit;