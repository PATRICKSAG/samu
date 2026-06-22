<?php

function listarEstablecimientos(PDO $pdo)
{
    $sql = "SELECT * FROM establecimiento where activo = 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    return $data;
}

function insertarEstablecimiento(PDO $pdo, array $data)
{
    $ruc = $data['ruc'];
    $razonSocial = $data['razonSocial'];
    $responsableLegal = $data['responsableLegal'];
    $cargoRepresentanteLegal = $data ['cargoRepresentanteLegal'];
    $informal = $data['informal'];
    $sql = "INSERT INTO establecimiento (ruc, razonSocial, responsableLegal, cargoRepresentanteLegal, informal) VALUES(?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ruc, $razonSocial, $responsableLegal,$cargoRepresentanteLegal, $informal]);
    return $pdo->lastInsertId();
}

function actualizarEstablecimiento(PDO $pdo, array $data)
{
    $idEstablecimiento = $data['idEstablecimiento'];
    $ruc = $data['ruc'];
    $razonSocial = $data['razonSocial'];
    $responsableLegal = $data['responsableLegal'];
    $cargoRepresentanteLegal = $data ['cargoRepresentanteLegal'];
    $informal = $data['informal'];

    $sql = "UPDATE establecimiento SET ruc = ?, razonSocial = ?, responsableLegal = ?, cargoRepresentanteLegal = ?, informal = ? WHERE idEstablecimiento = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ruc, $razonSocial, $responsableLegal,$cargoRepresentanteLegal, $informal, $idEstablecimiento]);
    return $pdo->lastInsertId();
}
