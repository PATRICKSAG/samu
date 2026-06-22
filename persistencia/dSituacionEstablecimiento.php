<?php

function listarSituacionesEstablecimientos(PDO $pdo)
{
    $sql = "SELECT * FROM situacion_establecimiento WHERE activo = 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    return $data;
}
