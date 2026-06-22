<?php

function listarSituacionesDigemid(PDO $pdo)
{
    $sql = "SELECT * FROM situacion_digemid WHERE activo = 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    return $data;
}
