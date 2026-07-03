<?php

function calcularDiasHabiles(PDO $pdo, $fechaInicio, $dias)
{
    // Suma $dias hábiles a $fechaInicio, excluyendo fines de semana y feriados de la tabla FeriadosPeru
    $fecha = new DateTime($fechaInicio);
    $i = 0;
    $feriados = obtenerFeriados($pdo);
    while ($i < $dias) {
        $fecha->modify('+1 day');
        // Verificar si es fin de semana (sábado o domingo)
        $diaSemana = $fecha->format('N');
        if ($diaSemana >= 6) {
            continue;
        }
        // Verificar si es feriado
        $fechaStr = $fecha->format('Y-m-d');
        if (in_array($fechaStr, $feriados)) {
            continue;
        }
        $i++;
    }
    return $fecha->format('Y-m-d');
}

function obtenerFeriados(PDO $pdo)
{
    $sql = "SELECT Fecha FROM FeriadosPeru";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $result;
}

function insertarPlazo(PDO $pdo, $idExpediente, $evento, $fechaOrigen, $plazo, $unidad, $fechaVencimiento, $estado = 'VIGENTE')
{
    $sql = "INSERT INTO expediente_plazos (idExpediente, evento, fechaOrigen, plazo, unidad, fechaVencimiento, estado, alarmaEnviada)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente, $evento, $fechaOrigen, $plazo, $unidad, $fechaVencimiento, $estado]);
    return $pdo->lastInsertId();
}

function actualizarEstadoPlazos(PDO $pdo, $idExpediente)
{
    // Actualizar estados de plazos según fecha actual
    $hoy = date('Y-m-d');
    $sql = "UPDATE expediente_plazos 
            SET estado = CASE 
                WHEN fechaVencimiento < ? THEN 'VENCIDO'
                WHEN fechaVencimiento = ? THEN 'VENCE_HOY'
                WHEN DATEDIFF(day, GETDATE(), fechaVencimiento) <= 3 THEN 'PROXIMO_VENCER'
                ELSE 'VIGENTE'
            END
            WHERE idExpediente = ? AND estado NOT IN ('CUMPLIDO', 'VENCIDO')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hoy, $hoy, $idExpediente]);
}