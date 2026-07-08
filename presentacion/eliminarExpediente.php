<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dExpediente.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: formExpedienteUFREMID.php?mensaje=" . urlencode("ID no válido"));
    exit;
}
$id = intval($_GET['id']);
$area = $_GET['area'] ?? 'UFREMID';  // por defecto UFREMID

// Validar que el área sea permitida (solo UFREMID, UFRESA O UFRESBIT)
if (!in_array($area, ['UFREMID', 'UFRESA','UFRESBIT'])) {
    $area = 'UFREMID';
}

$pdo = Database::getConexion();
try {
    eliminarExpediente($pdo, $id);
    $mensaje = "Expediente eliminado correctamente.";
} catch (Exception $e) {
    $mensaje = "Error al eliminar: " . $e->getMessage();
}

// Redirigir al formulario correspondiente
header("Location: formExpediente{$area}.php?mensaje=" . urlencode($mensaje));
exit;