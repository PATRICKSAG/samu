<?php
// persistencia/dExpediente.php

function getPlazosArea($area)
{
    $plazos = [
        'UFREMID'  => [
            'descargoActa'      => 7,
            'descargoPAS'       => 5,
            'caducidadPAS'      => 9,
            'descargoIFI'       => 5,
            'resolucionSancion' => 15,
            'consentida'        => 15,
        ],
        'UFRESA'   => [
            'descargoActa'      => 10,
            'descargoPAS'       => 10,
            'caducidadPAS'      => 9,
            'descargoIFI'       => 10,
            'resolucionSancion' => 15,
            'consentida'        => 15,
        ],
        'UFRESBIT' => [
            'descargoActa'      => 25,
            'descargoPAS'       => 5,
            'caducidadPAS'      => 9,
            'descargoIFI'       => 5,
            'resolucionSancion' => 15,
            'consentida'        => 15,
        ],
    ];
    return $plazos[$area] ?? $plazos['UFREMID'];
}

function listarExpedientes(PDO $pdo)
{
    $sql = "SELECT e.*,
                   s.numeroEstacion,
                   s.nombre as nombreSede,
                   est.razonSocial,
                   est.ruc as RUC,
                   s.direccion,
                   d.nombre as nombreDistrito,
                   p.nombre as nombreProvincia
            FROM expediente e
            LEFT JOIN sede s ON e.idSede = s.idSede
            LEFT JOIN establecimiento est ON s.idEstablecimiento = est.idEstablecimiento
            LEFT JOIN distrito d ON s.idDistrito = d.idDistrito
            LEFT JOIN provincia p ON d.idProvincia = p.idProvincia
            ORDER BY e.fechaInspeccion DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertarExpediente(PDO $pdo, array $data, $area = 'UFREMID')
{
    $pdo->beginTransaction();
    try {
        // Insertar en expediente
        $sql = "INSERT INTO expediente (
                    idSede, numeroActa, fechaInspeccion, estadoExpediente,
                    idTipoExpediente, codigoUfremid, responsable, numeroFolios,
                    observacion, judicializado, falsificado, areaOrigen,
                    fechaCreacion, fechaModificacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['idSede'],
            $data['numeroActa'],
            $data['fechaInspeccion'] ?? null,
            $data['estadoExpediente'],
            $data['idTipoExpediente'] ?? null,
            $data['codigoUFREMID'] ?? null,
            $data['responsable'] ?? null,
            $data['numeroFolios'] ?? null,
            $data['observacion'] ?? null,
            $data['judicializado'] ?? null,
            $data['falsificado'] ?? 0,
            $area,
        ]);
        $idExpediente = $pdo->lastInsertId();

        // Insertar MS si hay datos
        if (tieneDatosMS($data)) {
            $sqlMS = "INSERT INTO expediente_ms (
                        idExpediente, fechaDescargoActa, oficioOtorgaDeniegaPlazo,
                        idSituacionDigemidSeleccionada, docElevaNulidad, resuelveNulidad,
                        informeTecnicoInspeccion, nCertificadoBuenasPracticas,
                        fechaInicioCertificadoBP, fechaFinCertificadoBP,
                        rgrRatificaCierreTemporal, fechaNotificacionRGRCierre,
                        descargoApelacion, nDocResuelveRecurso,
                        rsgLevantamientoCierre, fechaNotificacionRSGLevantamiento,
                        cierreDefinitivo, fechaNotificacionCierreDefinitivo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtMS = $pdo->prepare($sqlMS);
            $stmtMS->execute([
                $idExpediente,
                $data['fechaDescargoActa'] ?? null,
                $data['oficioOtorgaDeniegaPlazo'] ?? null,
                $data['idSituacionDigemidSeleccionada'] ?? null,
                $data['docElevaNulidad'] ?? null,
                $data['resuelveNulidad'] ?? null,
                $data['informeTecnicoInspeccion'] ?? null,
                $data['nCertificadoBuenasPracticas'] ?? null,
                $data['fechaInicioCertificadoBP'] ?? null,
                $data['fechaFinCertificadoBP'] ?? null,
                $data['rgrRatificaCierreTemporal'] ?? null,
                $data['fechaNotificacionRGRCierre'] ?? null,
                $data['descargoApelacion'] ?? null,
                $data['nDocResuelveRecurso'] ?? null,
                $data['rsgLevantamientoCierre'] ?? null,
                $data['fechaNotificacionRSGLevantamiento'] ?? null,
                $data['cierreDefinitivo'] ?? null,
                $data['fechaNotificacionCierreDefinitivo'] ?? null,
            ]);
        }

        // Actualizar estado de sede si se seleccionó
        if (!empty($data['idSituacionDigemidSeleccionada']) && $area != 'UFRESBIT') {
            $sqlUpdateSede = "UPDATE sede SET idSituacionDigemid = ? WHERE idSede = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdateSede);
            $stmtUpdate->execute([$data['idSituacionDigemidSeleccionada'], $data['idSede']]);
        }
        if (!empty($data['idEstadoRenipressSeleccionado']) && $area == 'UFRESBIT') {
            $sqlUpdateSede = "UPDATE sede SET idEstadoRenipress = ? WHERE idSede = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdateSede);
            $stmtUpdate->execute([$data['idEstadoRenipressSeleccionado'], $data['idSede']]);
        }

        // Insertar plazo de descargo del acta (si hay fechaInspeccion y plazo > 0)
        if (!empty($data['fechaInspeccion'])) {
            $plazos = getPlazosArea($area);
            $dias = $plazos['descargoActa'];
            if ($dias > 0) {
                $fechaVencimiento = sumarDiasHabiles($pdo, $data['fechaInspeccion'], $dias);
                if ($fechaVencimiento) {
                    $sqlPlazo = "INSERT INTO expediente_plazos (
                                    idExpediente, evento, fechaOrigen, plazo, unidad,
                                    fechaVencimiento, estado, alarmaEnviada
                                ) VALUES (?, 'DESCARGO_ACTA', ?, ?, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                    $stmtPlazo = $pdo->prepare($sqlPlazo);
                    $stmtPlazo->execute([$idExpediente, $data['fechaInspeccion'], $dias, $fechaVencimiento]);
                }
            }
        }

        // Guardar datos Digemid (si existen)
        if (!empty($data['idEquipoDCVS']) || !empty($data['atendidoPor'])) {
            guardarExpedienteDigemid($pdo, $idExpediente, $data);
        }

        // Guardar medida de seguridad (si existe)
        if (!empty($data['medidaSeguridad'])) {
            guardarMedidaSeguridad($pdo, $idExpediente, $data['medidaSeguridad']);
        }

        $pdo->commit();
        return $idExpediente;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function actualizarExpediente(PDO $pdo, array $data, $area = 'UFREMID')
{
    $pdo->beginTransaction();
    try {
        $sql = "UPDATE expediente SET
                    idSede = ?,
                    numeroActa = ?,
                    fechaInspeccion = ?,
                    estadoExpediente = ?,
                    idTipoExpediente = ?,
                    codigoUfremid = ?,
                    responsable = ?,
                    numeroFolios = ?,
                    observacion = ?,
                    judicializado = ?,
                    falsificado = ?,
                    fechaModificacion = GETDATE()
                WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['idSede'],
            $data['numeroActa'],
            $data['fechaInspeccion'] ?? null,
            $data['estadoExpediente'],
            $data['idTipoExpediente'] ?? null,
            $data['codigoUFREMID'] ?? null,
            $data['responsable'] ?? null,
            $data['numeroFolios'] ?? null,
            $data['observacion'] ?? null,
            $data['judicializado'] ?? null,
            $data['falsificado'] ?? 0,
            $data['idExpediente'],
        ]);

        // Actualizar MS
        $sqlDeleteMS = "DELETE FROM expediente_ms WHERE idExpediente = ?";
        $stmtDeleteMS = $pdo->prepare($sqlDeleteMS);
        $stmtDeleteMS->execute([$data['idExpediente']]);

        if (tieneDatosMS($data)) {
            $sqlMS = "INSERT INTO expediente_ms (
                        idExpediente, fechaDescargoActa, oficioOtorgaDeniegaPlazo,
                        idSituacionDigemidSeleccionada, docElevaNulidad, resuelveNulidad,
                        informeTecnicoInspeccion, nCertificadoBuenasPracticas,
                        fechaInicioCertificadoBP, fechaFinCertificadoBP,
                        rgrRatificaCierreTemporal, fechaNotificacionRGRCierre,
                        descargoApelacion, nDocResuelveRecurso,
                        rsgLevantamientoCierre, fechaNotificacionRSGLevantamiento,
                        cierreDefinitivo, fechaNotificacionCierreDefinitivo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtMS = $pdo->prepare($sqlMS);
            $stmtMS->execute([
                $data['idExpediente'],
                $data['fechaDescargoActa'] ?? null,
                $data['oficioOtorgaDeniegaPlazo'] ?? null,
                $data['idSituacionDigemidSeleccionada'] ?? null,
                $data['docElevaNulidad'] ?? null,
                $data['resuelveNulidad'] ?? null,
                $data['informeTecnicoInspeccion'] ?? null,
                $data['nCertificadoBuenasPracticas'] ?? null,
                $data['fechaInicioCertificadoBP'] ?? null,
                $data['fechaFinCertificadoBP'] ?? null,
                $data['rgrRatificaCierreTemporal'] ?? null,
                $data['fechaNotificacionRGRCierre'] ?? null,
                $data['descargoApelacion'] ?? null,
                $data['nDocResuelveRecurso'] ?? null,
                $data['rsgLevantamientoCierre'] ?? null,
                $data['fechaNotificacionRSGLevantamiento'] ?? null,
                $data['cierreDefinitivo'] ?? null,
                $data['fechaNotificacionCierreDefinitivo'] ?? null,
            ]);
        }

        // Actualizar sede
        if (!empty($data['idSituacionDigemidSeleccionada']) && $area != 'UFRESBIT') {
            $sqlUpdateSede = "UPDATE sede SET idSituacionDigemid = ? WHERE idSede = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdateSede);
            $stmtUpdate->execute([$data['idSituacionDigemidSeleccionada'], $data['idSede']]);
        }
        if (!empty($data['idEstadoRenipressSeleccionado']) && $area == 'UFRESBIT') {
            $sqlUpdateSede = "UPDATE sede SET idEstadoRenipress = ? WHERE idSede = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdateSede);
            $stmtUpdate->execute([$data['idEstadoRenipressSeleccionado'], $data['idSede']]);
        }

        // Recalcular plazo de descargo del acta
        $sqlDeletePlazo = "DELETE FROM expediente_plazos WHERE idExpediente = ? AND evento = 'DESCARGO_ACTA'";
        $stmtDeletePlazo = $pdo->prepare($sqlDeletePlazo);
        $stmtDeletePlazo->execute([$data['idExpediente']]);

        if (!empty($data['fechaInspeccion'])) {
            $plazos = getPlazosArea($area);
            $dias = $plazos['descargoActa'];
            if ($dias > 0) {
                $fechaVencimiento = sumarDiasHabiles($pdo, $data['fechaInspeccion'], $dias);
                if ($fechaVencimiento) {
                    $sqlPlazo = "INSERT INTO expediente_plazos (
                                    idExpediente, evento, fechaOrigen, plazo, unidad,
                                    fechaVencimiento, estado, alarmaEnviada
                                ) VALUES (?, 'DESCARGO_ACTA', ?, ?, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                    $stmtPlazo = $pdo->prepare($sqlPlazo);
                    $stmtPlazo->execute([$data['idExpediente'], $data['fechaInspeccion'], $dias, $fechaVencimiento]);
                }
            }
        }

        // Guardar datos Digemid
        if (!empty($data['idEquipoDCVS']) || !empty($data['atendidoPor']) || !empty($data['condicionEjecucion'])) {
            guardarExpedienteDigemid($pdo, $data['idExpediente'], $data);
        }

        // Guardar medida de seguridad
        if (isset($data['medidaSeguridad'])) {
            guardarMedidaSeguridad($pdo, $data['idExpediente'], $data['medidaSeguridad']);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function eliminarExpediente(PDO $pdo, $idExpediente)
{
    $pdo->beginTransaction();
    try {
        eliminarExpedienteDigemid($pdo, $idExpediente);
        eliminarMedidaSeguridad($pdo, $idExpediente);
        $sql = "DELETE FROM expediente_pagos WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente_plazos WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente_fs WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente_fi WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente_ms WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function obtenerExpedienteCompleto(PDO $pdo, $idExpediente)
{
    $sql = "SELECT e.*, ms.*, dg.*
            FROM expediente e
            LEFT JOIN expediente_ms ms ON e.idExpediente = ms.idExpediente
            LEFT JOIN expediente_digemid dg ON e.idExpediente = dg.idExpediente
            WHERE e.idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        $data['medidaSeguridad'] = obtenerMedidaSeguridad($pdo, $idExpediente);
    }
    return $data;
}

function listarExpedientesUFREMID(PDO $pdo)
{
    $sql = "SELECT
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                C.nombre as categoria,
                e.estadoExpediente,
                e.responsable,
                CONCAT(s.nombre, ' - ', s.direccion) AS nombreSede
            FROM expediente e WITH(NOLOCK)
                LEFT JOIN sede S WITH(NOLOCK) ON E.idSede = S.idSede
                LEFT JOIN categoria C WITH(NOLOCK) ON S.idCategoria = C.idCategoria
            WHERE e.areaOrigen = 'UFREMID'
            ORDER BY e.fechaInspeccion DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerExpediente(PDO $pdo, $idExpediente)
{
    $sql = "SELECT
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                e.estadoExpediente,
                e.responsable,
                e.areaOrigen,
                (SELECT CONCAT(s.nombre, ' - ', s.direccion) FROM sede s WHERE s.idSede = e.idSede) AS nombreSede
            FROM expediente e
            WHERE e.idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function listarExpedientesPorArea(PDO $pdo, $area)
{
    $sql = "SELECT
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                e.estadoExpediente,
                e.responsable,
                (SELECT CONCAT(s.nombre, ' - ', s.direccion) FROM sede s WHERE s.idSede = e.idSede) AS nombreSede
            FROM expediente e
            WHERE e.areaOrigen = ?
            ORDER BY e.idExpediente DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$area]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarExpedienteFI(PDO $pdo, $idExpediente)
{
    $sql = "SELECT
                fi.idExpedienteFI,
                fi.tipoEvento,
                fi.informeTecnicoInicioPAS,
                fi.fechaInformeTecnico,
                fi.oficioIniciaPAS,
                fi.fechaNotificacionInicioPAS,
                fi.fechaDescargoPresentado,
                fi.documentoElevaEscrito,
                fi.informeLegalCaducidad,
                fi.resolucionCaducidad,
                fi.recursoInterpuesto,
                fi.resolucionRecurso,
                fi.fechaNotificacionRecurso,
                fi.informeFinalInstruccion,
                fi.fechaCreacion,
                (SELECT TOP 1 estado FROM expediente_plazos
                 WHERE idExpediente = fi.idExpediente AND evento = 'DESCARGO_PAS'
                 AND idExpedienteFI = fi.idExpedienteFI ORDER BY idPlazo DESC) AS estadoDescargo,
                (SELECT TOP 1 fechaVencimiento FROM expediente_plazos
                 WHERE idExpediente = fi.idExpediente AND evento = 'DESCARGO_PAS'
                 AND idExpedienteFI = fi.idExpedienteFI ORDER BY idPlazo DESC) AS fechaVencimientoDescargo,
                (SELECT TOP 1 estado FROM expediente_plazos
                 WHERE idExpediente = fi.idExpediente AND evento = 'CADUCIDAD_PAS'
                 AND idExpedienteFI = fi.idExpedienteFI ORDER BY idPlazo DESC) AS estadoCaducidad,
                (SELECT TOP 1 fechaVencimiento FROM expediente_plazos
                 WHERE idExpediente = fi.idExpediente AND evento = 'CADUCIDAD_PAS'
                 AND idExpedienteFI = fi.idExpedienteFI ORDER BY idPlazo DESC) AS fechaVencimientoCaducidad
            FROM expediente_fi fi
            WHERE fi.idExpediente = ?
            ORDER BY fi.idExpedienteFI DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        if ($row['fechaDescargoPresentado'] == '1900-01-01') {
            $row['fechaDescargoPresentado'] = null;
        }
        if ($row['fechaNotificacionInicioPAS'] == '1900-01-01') {
            $row['fechaNotificacionInicioPAS'] = null;
        }
    }
    return $rows;
}

function obtenerExpedienteFI(PDO $pdo, $idExpedienteFI)
{
    $sql = "SELECT * FROM expediente_fi WHERE idExpedienteFI = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFI]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sumarDiasHabiles(PDO $pdo, $fechaInicio, $dias)
{
    if (empty($fechaInicio) || $dias <= 0) {
        return null;
    }

    $fecha = new DateTime($fechaInicio);
    $contador = 0;
    while ($contador < $dias) {
        $fecha->modify('+1 day');
        $diaSemana = $fecha->format('N');
        $sql = "SELECT COUNT(*) FROM FeriadosPeru WHERE Fecha = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha->format('Y-m-d')]);
        $esFeriado = $stmt->fetchColumn() > 0;
        if ($diaSemana < 6 && !$esFeriado) {
            $contador++;
        }
    }
    return $fecha->format('Y-m-d');
}

function sumarMeses($fechaInicio, $meses)
{
    if (empty($fechaInicio) || $meses <= 0) {
        return null;
    }

    $fecha = new DateTime($fechaInicio);
    $fecha->modify("+$meses months");
    return $fecha->format('Y-m-d');
}

function insertarExpedienteFI(PDO $pdo, array $data, $area = 'UFREMID')
{
    $idExpediente = $data['idExpediente'];
    $tipoEvento = $data['tipoEvento'] ?? 'INICIO';
    $informeTecnicoInicioPAS = $data['informeTecnicoInicioPAS'] ?? null;
    $fechaInformeTecnico = $data['fechaInformeTecnico'] ?? null;
    $oficioIniciaPAS = $data['oficioIniciaPAS'] ?? null;
    $fechaNotificacionInicioPAS = $data['fechaNotificacionInicioPAS'] ?? null;
    $fechaDescargoPresentado = !empty($data['fechaDescargoPresentado']) ? $data['fechaDescargoPresentado'] : null;
    $documentoElevaEscrito = $data['documentoElevaEscrito'] ?? null;
    $informeLegalCaducidad = $data['informeLegalCaducidad'] ?? null;
    $resolucionCaducidad = $data['resolucionCaducidad'] ?? null;
    $recursoInterpuesto = $data['recursoInterpuesto'] ?? null;
    $resolucionRecurso = $data['resolucionRecurso'] ?? null;
    $fechaNotificacionRecurso = $data['fechaNotificacionRecurso'] ?? null;
    $informeFinalInstruccion = $data['informeFinalInstruccion'] ?? null;

    $plazos = getPlazosArea($area);
    $diasDescargo = $plazos['descargoPAS'];
    $mesesCaducidad = $plazos['caducidadPAS'];

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO expediente_fi (
                    idExpediente, tipoEvento, informeTecnicoInicioPAS, fechaInformeTecnico,
                    oficioIniciaPAS, fechaNotificacionInicioPAS, fechaDescargoPresentado,
                    documentoElevaEscrito, informeLegalCaducidad, resolucionCaducidad,
                    recursoInterpuesto, resolucionRecurso, fechaNotificacionRecurso,
                    informeFinalInstruccion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idExpediente, $tipoEvento, $informeTecnicoInicioPAS, $fechaInformeTecnico,
            $oficioIniciaPAS, $fechaNotificacionInicioPAS, $fechaDescargoPresentado,
            $documentoElevaEscrito, $informeLegalCaducidad, $resolucionCaducidad,
            $recursoInterpuesto, $resolucionRecurso, $fechaNotificacionRecurso,
            $informeFinalInstruccion,
        ]);
        $idExpedienteFI = $pdo->lastInsertId();

        if (!empty($fechaNotificacionInicioPAS)) {
            $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $fechaNotificacionInicioPAS, $diasDescargo);
            if ($fechaVencimientoDescargo) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'DESCARGO_PAS', ?, ?, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $fechaNotificacionInicioPAS, $diasDescargo, $fechaVencimientoDescargo]);
            }

            $fechaVencimientoCaducidad = sumarMeses($fechaNotificacionInicioPAS, $mesesCaducidad);
            if ($fechaVencimientoCaducidad) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'CADUCIDAD_PAS', ?, ?, 'MESES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $fechaNotificacionInicioPAS, $mesesCaducidad, $fechaVencimientoCaducidad]);
            }
        }

        $pdo->commit();
        return $idExpedienteFI;
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function actualizarExpedienteFI(PDO $pdo, $idExpedienteFI, $nuevaFechaNotificacion, $nuevaFechaDescargo = null, $area = 'UFREMID')
{
    $plazos = getPlazosArea($area);
    $diasDescargo = $plazos['descargoPAS'];
    $mesesCaducidad = $plazos['caducidadPAS'];

    try {
        $pdo->beginTransaction();

        $sql = "SELECT idExpediente FROM expediente_fi WHERE idExpedienteFI = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpedienteFI]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new Exception("Registro FI no encontrado");
        }
        $idExpediente = $row['idExpediente'];

        if (empty($nuevaFechaDescargo) || $nuevaFechaDescargo == '1900-01-01') {
            $nuevaFechaDescargo = null;
        }

        $sqlUpdate = "UPDATE expediente_fi SET
                        fechaNotificacionInicioPAS = ?,
                        fechaDescargoPresentado = ?
                      WHERE idExpedienteFI = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([$nuevaFechaNotificacion, $nuevaFechaDescargo, $idExpedienteFI]);

        $sqlDelete = "DELETE FROM expediente_plazos WHERE idExpedienteFI = ? AND evento IN ('DESCARGO_PAS', 'CADUCIDAD_PAS')";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([$idExpedienteFI]);

        $fechaVencimientoDescargo = null;
        if (!empty($nuevaFechaNotificacion)) {
            $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $nuevaFechaNotificacion, $diasDescargo);
            if ($fechaVencimientoDescargo) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'DESCARGO_PAS', ?, ?, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $nuevaFechaNotificacion, $diasDescargo, $fechaVencimientoDescargo]);
            }

            $fechaVencimientoCaducidad = sumarMeses($nuevaFechaNotificacion, $mesesCaducidad);
            if ($fechaVencimientoCaducidad) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'CADUCIDAD_PAS', ?, ?, 'MESES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $nuevaFechaNotificacion, $mesesCaducidad, $fechaVencimientoCaducidad]);
            }
        }

        if (!empty($nuevaFechaDescargo) && !empty($fechaVencimientoDescargo)) {
            $estado = (strtotime($nuevaFechaDescargo) <= strtotime($fechaVencimientoDescargo)) ? 'CUMPLIDO' : 'VENCIDO';
            $sqlUpdateEstado = "UPDATE expediente_plazos SET estado = ?, fechaCumplimiento = ?
                                WHERE idExpedienteFI = ? AND evento = 'DESCARGO_PAS'";
            $stmtEstado = $pdo->prepare($sqlUpdateEstado);
            $stmtEstado->execute([$estado, $nuevaFechaDescargo, $idExpedienteFI]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function obtenerOCrearFS(PDO $pdo, $idExpedienteFI)
{
    $sql = "SELECT * FROM expediente_fs WHERE idExpedienteFI = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFI]);
    $fs = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fs) {
        return $fs;
    }

    $sqlFI = "SELECT idExpediente FROM expediente_fi WHERE idExpedienteFI = ?";
    $stmtFI = $pdo->prepare($sqlFI);
    $stmtFI->execute([$idExpedienteFI]);
    $rowFI = $stmtFI->fetch();
    if (!$rowFI) {
        return null;
    }
    $idExpediente = $rowFI['idExpediente'];
    $sqlInsert = "INSERT INTO expediente_fs (idExpediente, idExpedienteFI) VALUES (?, ?)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([$idExpediente, $idExpedienteFI]);
    $idFS = $pdo->lastInsertId();

    $sqlGet = "SELECT * FROM expediente_fs WHERE idExpedienteFS = ?";
    $stmtGet = $pdo->prepare($sqlGet);
    $stmtGet->execute([$idFS]);
    return $stmtGet->fetch(PDO::FETCH_ASSOC);
}

function listarExpedienteFS(PDO $pdo, $idExpedienteFI)
{
    $sql = "SELECT
                fs.*,
                (SELECT TOP 1 estado FROM expediente_plazos
                 WHERE idExpediente = fs.idExpediente AND evento = 'DESCARGO_IFI'
                 AND idExpedienteFS = fs.idExpedienteFS ORDER BY idPlazo DESC) AS estadoDescargoIFI,
                (SELECT TOP 1 fechaVencimiento FROM expediente_plazos
                 WHERE idExpediente = fs.idExpediente AND evento = 'DESCARGO_IFI'
                 AND idExpedienteFS = fs.idExpedienteFS ORDER BY idPlazo DESC) AS fechaVencimientoDescargoIFI,
                (SELECT TOP 1 estado FROM expediente_plazos
                 WHERE idExpediente = fs.idExpediente AND evento = 'RECURSO_SANCION'
                 AND idExpedienteFS = fs.idExpedienteFS ORDER BY idPlazo DESC) AS estadoRecursoSancion,
                (SELECT TOP 1 fechaVencimiento FROM expediente_plazos
                 WHERE idExpediente = fs.idExpediente AND evento = 'RECURSO_SANCION'
                 AND idExpedienteFS = fs.idExpedienteFS ORDER BY idPlazo DESC) AS fechaVencimientoRecursoSancion,
                (SELECT TOP 1 estado FROM expediente_plazos
                 WHERE idExpediente = fs.idExpediente AND evento = 'CUMPLIMIENTO_CONSENTIDA'
                 AND idExpedienteFS = fs.idExpedienteFS ORDER BY idPlazo DESC) AS estadoCumplimientoConsentida,
                (SELECT TOP 1 fechaVencimiento FROM expediente_plazos
                 WHERE idExpediente = fs.idExpediente AND evento = 'CUMPLIMIENTO_CONSENTIDA'
                 AND idExpedienteFS = fs.idExpedienteFS ORDER BY idPlazo DESC) AS fechaVencimientoCumplimientoConsentida
            FROM expediente_fs fs
            WHERE fs.idExpedienteFI = ?
            ORDER BY fs.idExpedienteFS DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFI]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerExpedienteFS(PDO $pdo, $idExpedienteFS)
{
    $sql = "SELECT * FROM expediente_fs WHERE idExpedienteFS = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFS]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarExpedienteFS(PDO $pdo, array $data)
{
    $idExpedienteFS = $data['idExpedienteFS'] ?? null;
    $idExpedienteFI = $data['idExpedienteFI'] ?? null;
    $oficioTrasladaIFI = $data['oficioTrasladaIFI'] ?? null;
    $fechaNotificacionIFI = $data['fechaNotificacionIFI'] ?? null;
    $fechaDescargoIFI = $data['fechaDescargoIFI'] ?? null;
    $nResolucionSancion = $data['nResolucionSancion'] ?? null;
    $nInfraccion = $data['nInfraccion'] ?? null;
    $sancionImpuesta = $data['sancionImpuesta'] ?? null;
    $fechaNotificacionSancion = $data['fechaNotificacionSancion'] ?? null;
    $recursoInterpuestoSancion = $data['recursoInterpuestoSancion'] ?? null;
    $fechaRecursoSancion = $data['fechaRecursoSancion'] ?? null;
    $pagoApela = $data['pagoApela'] ?? null;
    $resolucionRecursoSancion = $data['resolucionRecursoSancion'] ?? null;
    $resultadoRecurso = $data['resultadoRecurso'] ?? null;
    $fechaNotificacionRecursoSancion = $data['fechaNotificacionRecursoSancion'] ?? null;
    $resolucionConsentida = $data['resolucionConsentida'] ?? null;
    $fechaNotificacionConsentida = $data['fechaNotificacionConsentida'] ?? null;
    $oficioElevaApelacion = $data['oficioElevaApelacion'] ?? null;
    $resolucionApelacion = $data['resolucionApelacion'] ?? null;
    $fechaNotificacionApelacion = $data['fechaNotificacionApelacion'] ?? null;
    $pagaDemandaContenciosa = $data['pagaDemandaContenciosa'] ?? null;
    $oficioSolicitaInfoProcurador = $data['oficioSolicitaInfoProcurador'] ?? null;
    $estadoContencioso = $data['estadoContencioso'] ?? null;
    $observacionesContencioso = $data['observacionesContencioso'] ?? null;

    try {
        $pdo->beginTransaction();

        $sqlFI = "SELECT idExpediente FROM expediente_fi WHERE idExpedienteFI = ?";
        $stmtFI = $pdo->prepare($sqlFI);
        $stmtFI->execute([$idExpedienteFI]);
        $rowFI = $stmtFI->fetch();
        if (!$rowFI) {
            throw new Exception("Expediente FI no encontrado");
        }
        $idExpediente = $rowFI['idExpediente'];

        if ($idExpedienteFS) {
            $sql = "UPDATE expediente_fs SET
                        oficioTrasladaIFI = ?,
                        fechaNotificacionIFI = ?,
                        fechaDescargoIFI = ?,
                        nResolucionSancion = ?,
                        nInfraccion = ?,
                        sancionImpuesta = ?,
                        fechaNotificacionSancion = ?,
                        recursoInterpuestoSancion = ?,
                        fechaRecursoSancion = ?,
                        pagoApela = ?,
                        resolucionRecursoSancion = ?,
                        resultadoRecurso = ?,
                        fechaNotificacionRecursoSancion = ?,
                        resolucionConsentida = ?,
                        fechaNotificacionConsentida = ?,
                        oficioElevaApelacion = ?,
                        resolucionApelacion = ?,
                        fechaNotificacionApelacion = ?,
                        pagaDemandaContenciosa = ?,
                        oficioSolicitaInfoProcurador = ?,
                        estadoContencioso = ?,
                        observacionesContencioso = ?
                    WHERE idExpedienteFS = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $oficioTrasladaIFI, $fechaNotificacionIFI, $fechaDescargoIFI,
                $nResolucionSancion, $nInfraccion, $sancionImpuesta,
                $fechaNotificacionSancion, $recursoInterpuestoSancion, $fechaRecursoSancion,
                $pagoApela, $resolucionRecursoSancion, $resultadoRecurso,
                $fechaNotificacionRecursoSancion, $resolucionConsentida, $fechaNotificacionConsentida,
                $oficioElevaApelacion, $resolucionApelacion, $fechaNotificacionApelacion,
                $pagaDemandaContenciosa, $oficioSolicitaInfoProcurador,
                $estadoContencioso, $observacionesContencioso,
                $idExpedienteFS,
            ]);
        } else {
            $sql = "INSERT INTO expediente_fs (
                        idExpediente, idExpedienteFI, oficioTrasladaIFI, fechaNotificacionIFI,
                        fechaDescargoIFI, nResolucionSancion, nInfraccion, sancionImpuesta,
                        fechaNotificacionSancion, recursoInterpuestoSancion, fechaRecursoSancion,
                        pagoApela, resolucionRecursoSancion, resultadoRecurso,
                        fechaNotificacionRecursoSancion, resolucionConsentida, fechaNotificacionConsentida,
                        oficioElevaApelacion, resolucionApelacion, fechaNotificacionApelacion,
                        pagaDemandaContenciosa, oficioSolicitaInfoProcurador, estadoContencioso,
                        observacionesContencioso
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $idExpediente, $idExpedienteFI, $oficioTrasladaIFI, $fechaNotificacionIFI,
                $fechaDescargoIFI, $nResolucionSancion, $nInfraccion, $sancionImpuesta,
                $fechaNotificacionSancion, $recursoInterpuestoSancion, $fechaRecursoSancion,
                $pagoApela, $resolucionRecursoSancion, $resultadoRecurso,
                $fechaNotificacionRecursoSancion, $resolucionConsentida, $fechaNotificacionConsentida,
                $oficioElevaApelacion, $resolucionApelacion, $fechaNotificacionApelacion,
                $pagaDemandaContenciosa, $oficioSolicitaInfoProcurador, $estadoContencioso,
                $observacionesContencioso,
            ]);
            $idExpedienteFS = $pdo->lastInsertId();
        }

        $sqlDelete = "DELETE FROM expediente_plazos WHERE idExpedienteFS = ? AND evento IN ('DESCARGO_IFI', 'RECURSO_SANCION', 'CUMPLIMIENTO_CONSENTIDA')";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([$idExpedienteFS]);

        if (!empty($fechaNotificacionIFI)) {
            $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $fechaNotificacionIFI, 5);
            if ($fechaVencimientoDescargo) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFS, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'DESCARGO_IFI', ?, 5, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFS, $fechaNotificacionIFI, $fechaVencimientoDescargo]);
                if (!empty($fechaDescargoIFI)) {
                    $estado = (strtotime($fechaDescargoIFI) <= strtotime($fechaVencimientoDescargo)) ? 'CUMPLIDO' : 'VENCIDO';
                    $sqlUpdate = "UPDATE expediente_plazos SET estado = ?, fechaCumplimiento = ?
                                  WHERE idExpedienteFS = ? AND evento = 'DESCARGO_IFI'";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->execute([$estado, $fechaDescargoIFI, $idExpedienteFS]);
                }
            }
        }

        if (!empty($fechaNotificacionSancion)) {
            $fechaVencimientoRecurso = sumarDiasHabiles($pdo, $fechaNotificacionSancion, 15);
            if ($fechaVencimientoRecurso) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFS, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'RECURSO_SANCION', ?, 15, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFS, $fechaNotificacionSancion, $fechaVencimientoRecurso]);
            }
        }

        if (!empty($fechaNotificacionConsentida)) {
            $fechaVencimientoCumplimiento = sumarDiasHabiles($pdo, $fechaNotificacionConsentida, 15);
            if ($fechaVencimientoCumplimiento) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFS, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'CUMPLIMIENTO_CONSENTIDA', ?, 15, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFS, $fechaNotificacionConsentida, $fechaVencimientoCumplimiento]);
            }
        }

        $pdo->commit();
        return $idExpedienteFS;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function listarPagosPorFS(PDO $pdo, $idExpedienteFS)
{
    $sql = "SELECT * FROM expediente_pagos WHERE idExpedienteFS = ? ORDER BY idExpedientePago DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFS]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertarPago(PDO $pdo, array $data)
{
    $sql = "INSERT INTO expediente_pagos (idExpediente, idExpedienteFS, tipoPago, numeroComprobante, fechaPago, monto, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['idExpediente'],
        $data['idExpedienteFS'],
        $data['tipoPago'],
        $data['numeroComprobante'],
        $data['fechaPago'],
        $data['monto'],
        $data['observaciones'],
    ]);
    return $pdo->lastInsertId();
}

function eliminarPago(PDO $pdo, $idExpedientePago)
{
    $sql = "DELETE FROM expediente_pagos WHERE idExpedientePago = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedientePago]);
    return $stmt->rowCount();
}

function listarExpedientesUFRESA(PDO $pdo)
{
    $sql = "SELECT
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                C.nombre as categoria,
                e.estadoExpediente,
                e.responsable,
                CONCAT(s.nombre, ' - ', s.direccion) AS nombreSede
            FROM expediente e WITH(NOLOCK)
                LEFT JOIN sede S WITH(NOLOCK) ON E.idSede = S.idSede
                LEFT JOIN categoria C WITH(NOLOCK) ON S.idCategoria = C.idCategoria
            WHERE e.areaOrigen = 'UFRESA'
            ORDER BY e.fechaInspeccion DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarExpedientesUFRESBIT(PDO $pdo)
{
    $sql = "SELECT
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                e.estadoExpediente,
                e.responsable,
                (SELECT CONCAT(s.nombre, ' - ', s.direccion) FROM sede s WHERE s.idSede = e.idSede) AS nombreSede
            FROM expediente e
            WHERE e.areaOrigen = 'UFRESBIT'
            ORDER BY e.fechaInspeccion DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerPlazosCriticos(PDO $pdo, $limite = 7, $area = null, $estado = null)
{
    $hoy = new DateTime();
    $estadosActivos = ['EN PROCESO', 'ENVIADO AL EJECUTOR'];

    // Construir la consulta base
    $sql = "SELECT
                p.idPlazo,
                p.idExpediente,
                p.evento,
                p.fechaOrigen,
                p.fechaVencimiento,
                p.fechaCumplimiento,
                p.estado AS estadoGuardado,
                e.numeroActa,
                e.areaOrigen,
                e.estadoExpediente,
                e.responsable
            FROM expediente_plazos p
            INNER JOIN expediente e ON p.idExpediente = e.idExpediente
            WHERE p.fechaVencimiento IS NOT NULL
              AND p.fechaCumplimiento IS NULL
              AND e.estadoExpediente IN ('" . implode("','", $estadosActivos) . "')";

    // Aplicar filtro por área si se especifica
    if ($area) {
        $sql .= " AND e.areaOrigen = '" . addslashes($area) . "'";
    }

    $sql .= " ORDER BY 
                CASE WHEN p.fechaVencimiento < GETDATE() THEN 0 ELSE 1 END,
                p.fechaVencimiento ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $plazos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $criticos = [];
    $contador = 0;

    foreach ($plazos as $p) {
        $fechaVenc = new DateTime($p['fechaVencimiento']);
        $diferencia = $hoy->diff($fechaVenc)->days;

        if ($hoy > $fechaVenc) {
            $estadoPlazo = 'VENCIDO';
        } elseif ($diferencia <= 12) {
            $estadoPlazo = 'PROXIMO_VENCER';
        } else {
            continue; // descartar plazos lejanos
        }

        // Aplicar filtro por estado si se especifica
        if ($estado && $estadoPlazo != $estado) {
            continue;
        }

        $criticos[] = [
            'idExpediente' => $p['idExpediente'],
            'numeroActa' => $p['numeroActa'],
            'areaOrigen' => $p['areaOrigen'],
            'evento' => $p['evento'],
            'fechaVencimiento' => $p['fechaVencimiento'],
            'dias' => $diferencia,
            'estado' => $estadoPlazo,
            'responsable' => $p['responsable'] ?? 'Sin responsable',
            'idPlazo' => $p['idPlazo']
        ];
        $contador++;
    }

    // Limitar el número de resultados (si `$limite` > 0)
    if ($limite > 0 && count($criticos) > $limite) {
        $criticos = array_slice($criticos, 0, $limite);
    }

    return [
        'total' => $contador, // total real (sin límite)
        'lista' => $criticos
    ];
}
// ============================================
// FUNCIONES PARA EQUIPO/ACTIVIDAD/TIPO
// ============================================
function listarEquiposDCVS(PDO $pdo)
{
    $sql = "SELECT idEquipo, nombre FROM EquipoDCVSUFREMID WHERE activo = 1 ORDER BY nombre";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarActividadesPorEquipo(PDO $pdo, $idEquipo)
{
    $sql = "SELECT idActividad, nombre FROM ActividadAbrevUFREMID WHERE idEquipo = ? AND activo = 1 ORDER BY nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEquipo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarTiposActividadPorActividad(PDO $pdo, $idActividad)
{
    $sql = "SELECT idTipoActividad, nombre FROM TipoActividadUFREMID WHERE idActividad = ? AND activo = 1 ORDER BY nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idActividad]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// FUNCIONES PARA expediente_digemid
// ============================================
function guardarExpedienteDigemid(PDO $pdo, $idExpediente, $data)
{
    $sqlCheck = "SELECT idExpedienteDigemid FROM expediente_digemid WHERE idExpediente = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$idExpediente]);
    $existe = $stmtCheck->fetch();

    if ($existe) {
        $sql = "UPDATE expediente_digemid SET
                    idEquipoDCVS = ?,
                    idActividadAbrev = ?,
                    idTipoActividad = ?,
                    condicionEjecucion = ?,
                    atendidoPor = ?,
                    horarioAtencionQF = ?,
                    permanenciaQF = ?,
                    cumplimientoBPOF = ?,
                    cumplimientoBPA = ?,
                    cumplimientoBPDyT = ?,
                    cumplimientoBPF = ?,
                    productosIncautados = ?,
                    bpd = ?,
                    bpa = ?,
                    bpf = ?,
                    bpsf = ?,
                    bpdt = ?,
                    fechaModificacion = GETDATE()
                WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['idEquipoDCVS'] ?? null,
            $data['idActividadAbrev'] ?? null,
            $data['idTipoActividad'] ?? null,
            $data['condicionEjecucion'] ?? null,
            $data['atendidoPor'] ?? null,
            $data['horarioAtencionQF'] ?? null,
            $data['permanenciaQF'] ?? null,
            $data['cumplimientoBPOF'] ?? null,
            $data['cumplimientoBPA'] ?? null,
            $data['cumplimientoBPDyT'] ?? null,
            $data['cumplimientoBPF'] ?? null,
            $data['productosIncautados'] ?? null,
            $data['bpd'] ?? null,
            $data['bpa'] ?? null,
            $data['bpf'] ?? null,
            $data['bpsf'] ?? null,
            $data['bpdt'] ?? null,
            $idExpediente
        ]);
    } else {
        $sql = "INSERT INTO expediente_digemid (
                    idExpediente, idEquipoDCVS, idActividadAbrev, idTipoActividad,
                    condicionEjecucion, atendidoPor, horarioAtencionQF, permanenciaQF,
                    cumplimientoBPOF, cumplimientoBPA, cumplimientoBPDyT, cumplimientoBPF,
                    productosIncautados, bpd, bpa, bpf, bpsf, bpdt,
                    fechaCreacion, fechaModificacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $idExpediente,
            $data['idEquipoDCVS'] ?? null,
            $data['idActividadAbrev'] ?? null,
            $data['idTipoActividad'] ?? null,
            $data['condicionEjecucion'] ?? null,
            $data['atendidoPor'] ?? null,
            $data['horarioAtencionQF'] ?? null,
            $data['permanenciaQF'] ?? null,
            $data['cumplimientoBPOF'] ?? null,
            $data['cumplimientoBPA'] ?? null,
            $data['cumplimientoBPDyT'] ?? null,
            $data['cumplimientoBPF'] ?? null,
            $data['productosIncautados'] ?? null,
            $data['bpd'] ?? null,
            $data['bpa'] ?? null,
            $data['bpf'] ?? null,
            $data['bpsf'] ?? null,
            $data['bpdt'] ?? null
        ]);
    }
}

function obtenerExpedienteDigemid(PDO $pdo, $idExpediente)
{
    $sql = "SELECT * FROM expediente_digemid WHERE idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function eliminarExpedienteDigemid(PDO $pdo, $idExpediente)
{
    $sql = "DELETE FROM expediente_digemid WHERE idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
}

// ============================================
// FUNCIONES PARA medidasSeguridadDIGEMID
// ============================================
function guardarMedidaSeguridad(PDO $pdo, $idExpediente, $medida)
{
    $sqlDelete = "DELETE FROM medidasSeguridadDIGEMID WHERE idExpediente = ?";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([$idExpediente]);

    if (!empty($medida)) {
        $sql = "INSERT INTO medidasSeguridadDIGEMID (idExpediente, medida) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente, $medida]);
    }
}

function obtenerMedidaSeguridad(PDO $pdo, $idExpediente)
{
    $sql = "SELECT medida FROM medidasSeguridadDIGEMID WHERE idExpediente = ? AND activo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['medida'] : null;
}

function eliminarMedidaSeguridad(PDO $pdo, $idExpediente)
{
    $sql = "DELETE FROM medidasSeguridadDIGEMID WHERE idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
}
function tieneDatosMS($data) {
    $msFields = [
        'fechaDescargoActa', 'oficioOtorgaDeniegaPlazo', 'idSituacionDigemidSeleccionada',
        'docElevaNulidad', 'resuelveNulidad', 'informeTecnicoInspeccion',
        'nCertificadoBuenasPracticas', 'fechaInicioCertificadoBP', 'fechaFinCertificadoBP',
        'rgrRatificaCierreTemporal', 'fechaNotificacionRGRCierre',
        'descargoApelacion', 'nDocResuelveRecurso',
        'rsgLevantamientoCierre', 'fechaNotificacionRSGLevantamiento',
        'cierreDefinitivo', 'fechaNotificacionCierreDefinitivo'
    ];
    foreach ($msFields as $field) {
        if (!empty($data[$field])) {
            return true;
        }
    }
    return false;
}

// ============================================
// ENDPOINTS AJAX
// ============================================
if (isset($_POST['action'])) {
    include_once(__DIR__ . '/conexion.php');
    $pdo = Database::getConexion();

    if ($_POST['action'] === 'listarActividades' && isset($_POST['idEquipo'])) {
        $idEquipo = intval($_POST['idEquipo']);
        $data = listarActividadesPorEquipo($pdo, $idEquipo);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    if ($_POST['action'] === 'listarTiposActividad' && isset($_POST['idActividad'])) {
        $idActividad = intval($_POST['idActividad']);
        $data = listarTiposActividadPorActividad($pdo, $idActividad);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

