<?php

function listarCategorias(PDO $pdo)
{
    $sql = "SELECT * FROM categoria WHERE activo = 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    return $data;
}
function listarCategoriasPorArea(PDO $pdo, $area)
{
    $sql = "SELECT * FROM categoria WHERE activo = 1";
    if ($area == 'UFREMID') {
        $sql .= " AND idCategoria <= 8";
    } elseif ($area == 'UFRESA') {
        $sql .= " AND idCategoria >= 9";
    } else {
        $sql .= " AND 1=0"; // no mostrar ninguna
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_POST['action']) && $_POST['action'] == 'listarPorArea') {
    include_once(__DIR__ . '/conexion.php');
    $pdo = Database::getConexion();
    $area = $_POST['area'];
    $categorias = listarCategoriasPorArea($pdo, $area);
    header('Content-Type: application/json');
    echo json_encode($categorias);
    exit;
}