<?php
// presentacion/eliminarExpediente.php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dExpediente.php');

if (isset($_GET['id'])) {
    $pdo = Database::getConexion();
    eliminarExpediente($pdo, $_GET['id']);
    header("Location: formExpediente.php?mensaje=Expediente eliminado correctamente");
    exit;
} else {
    header("Location: formExpediente.php");
    exit;
}