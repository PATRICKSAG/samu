<?php

function listarDepartamentos(PDO $pdo)
{
    $sql = "SELECT * FROM departamento WHERE activo = 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    return $data;
}
