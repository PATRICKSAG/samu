<?php
function listarTiposExpediente(PDO $pdo)
{
    $sql = "SELECT idTipoExpediente, nombre, descripcion FROM tipoExpediente  ORDER BY idTipoExpediente";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}