<?php
// persistencia/dExpediente.php
function getPlazosArea($area) {
    $plazos = [
        'UFREMID' => [
            'descargoActa' => 7,
            'descargoPAS' => 5,
            'caducidadPAS' => 9,
            'descargoIFI' => 5,
            'resolucionSancion' => 15,
            'consentida' => 15,
        ],
        'UFRESA' => [
            'descargoActa' => 10,
            'descargoPAS' => 10,
            'caducidadPAS' => 9,
            'descargoIFI' => 10,
            'resolucionSancion' => 15,
            'consentida' => 15,
        ],
        'UFRESBIT' => [
            'descargoActa' => 25,
            'descargoPAS' => 5,
            'caducidadPAS' => 9,
            'descargoIFI' => 5,
            'resolucionSancion' => 15,
            'consentida' => 15,
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
            ORDER BY e.idExpediente DESC";
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
            $area
        ]);
        $idExpediente = $pdo->lastInsertId();

        // Insertar MS si hay datos
        if (!empty($data['fechaDescargoActa']) || !empty($data['oficioOtorgaDeniegaPlazo']) || !empty($data['idSituacionDigemidSeleccionada'])) {
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
                $data['fechaNotificacionCierreDefinitivo'] ?? null
            ]);
        }

        // Actualizar estado de sede si se seleccionó
        if (!empty($data['idSituacionDigemidSeleccionada'])) {
            $sqlUpdateSede = "UPDATE sede SET idSituacionDigemid = ? WHERE idSede = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdateSede);
            $stmtUpdate->execute([$data['idSituacionDigemidSeleccionada'], $data['idSede']]);
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
        // Actualizar expediente
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
            $data['idExpediente']
        ]);

        // Actualizar MS (eliminar y volver a insertar para simplificar)
        $sqlDeleteMS = "DELETE FROM expediente_ms WHERE idExpediente = ?";
        $stmtDeleteMS = $pdo->prepare($sqlDeleteMS);
        $stmtDeleteMS->execute([$data['idExpediente']]);

        if (!empty($data['fechaDescargoActa']) || !empty($data['oficioOtorgaDeniegaPlazo']) || !empty($data['idSituacionDigemidSeleccionada'])) {
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
                $data['fechaNotificacionCierreDefinitivo'] ?? null
            ]);
        }

        // Actualizar sede si se cambió estado
        if (!empty($data['idSituacionDigemidSeleccionada'])) {
            $sqlUpdateSede = "UPDATE sede SET idSituacionDigemid = ? WHERE idSede = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdateSede);
            $stmtUpdate->execute([$data['idSituacionDigemidSeleccionada'], $data['idSede']]);
        }

        // Recalcular plazo de descargo del acta: eliminar antiguo y crear nuevo
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
        $sql = "DELETE FROM expediente_plazos WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente_pagos WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente_fi WHERE idExpediente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpediente]);

        $sql = "DELETE FROM expediente_fs WHERE idExpediente = ?";
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
    $sql = "SELECT e.*, ms.* 
            FROM expediente e
            LEFT JOIN expediente_ms ms ON e.idExpediente = ms.idExpediente
            WHERE e.idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function listarExpedientesUFREMID(PDO $pdo)
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                e.estadoExpediente,
                e.responsable,
                (SELECT CONCAT(s.nombre, ' - ', s.direccion) FROM sede s WHERE s.idSede = e.idSede) AS nombreSede
            FROM expediente e
            WHERE e.areaOrigen = 'UFREMID'   
            ORDER BY e.idExpediente DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Obtiene datos básicos de un expediente
 */
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
/**
 * Lista todos los registros de FI de un expediente
 */
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
                -- Calcular estados de plazos desde expediente_plazos
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
    // Convertir fechas vacías a NULL para que PHP las muestre como ''
    foreach ($rows as &$row) {
        if ($row['fechaDescargoPresentado'] == '1900-01-01') {
            $row['fechaDescargoPresentado'] = null;
        }
        // También si otras fechas tienen 1900-01-01, las limpiamos
        if ($row['fechaNotificacionInicioPAS'] == '1900-01-01') {
            $row['fechaNotificacionInicioPAS'] = null;
        }
        // ... puedes agregar más si es necesario
    }
    return $rows;
}

/**
 * Obtiene un registro FI específico para edición
 */
function obtenerExpedienteFI(PDO $pdo, $idExpedienteFI)
{
    $sql = "SELECT * FROM expediente_fi WHERE idExpedienteFI = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFI]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Calcula días hábiles (excluyendo sábados, domingos y feriados)
 * Usa la tabla FeriadosPeru
 */
function sumarDiasHabiles(PDO $pdo, $fechaInicio, $dias)
{
    if (empty($fechaInicio) || $dias <= 0) return null;
    $fecha = new DateTime($fechaInicio);
    $contador = 0;
    while ($contador < $dias) {
        $fecha->modify('+1 day');
        $diaSemana = $fecha->format('N');
        // Verificar feriado
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

/**
 * Calcula fecha de vencimiento sumando meses (calendario)
 */
function sumarMeses($fechaInicio, $meses)
{
    if (empty($fechaInicio) || $meses <= 0) return null;
    $fecha = new DateTime($fechaInicio);
    $fecha->modify("+$meses months");
    return $fecha->format('Y-m-d');
}

/**
 * Inserta un nuevo registro FI y genera los plazos
 */
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

        // 1. Insertar en expediente_fi
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
            $informeFinalInstruccion
        ]);
        $idExpedienteFI = $pdo->lastInsertId();

        // 2. Generar plazos si hay fecha de notificación
        if (!empty($fechaNotificacionInicioPAS)) {
            // Plazo de descargo
            $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $fechaNotificacionInicioPAS, $diasDescargo);
            if ($fechaVencimientoDescargo) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'DESCARGO_PAS', ?, ?, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                // Ahora pasamos 5 valores: idExpediente, idExpedienteFI, fechaOrigen, plazo, fechaVencimiento
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $fechaNotificacionInicioPAS, $diasDescargo, $fechaVencimientoDescargo]);
            }

            // Plazo de caducidad
            $fechaVencimientoCaducidad = sumarMeses($fechaNotificacionInicioPAS, $mesesCaducidad);
            if ($fechaVencimientoCaducidad) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'CADUCIDAD_PAS', ?, ?, 'MESES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                // Ahora pasamos 5 valores: idExpediente, idExpedienteFI, fechaOrigen, plazo, fechaVencimiento
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

/**
 * Actualiza solo la fecha de notificación de un registro FI y recalcula plazos
 */
function actualizarExpedienteFI(PDO $pdo, $idExpedienteFI, $nuevaFechaNotificacion, $nuevaFechaDescargo = null, $area = 'UFREMID')
{
    $plazos = getPlazosArea($area);
    $diasDescargo = $plazos['descargoPAS'];
    $mesesCaducidad = $plazos['caducidadPAS'];
    try {
        $pdo->beginTransaction();

        // Obtener idExpediente
        $sql = "SELECT idExpediente FROM expediente_fi WHERE idExpedienteFI = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idExpedienteFI]);
        $row = $stmt->fetch();
        if (!$row) throw new Exception("Registro FI no encontrado");
        $idExpediente = $row['idExpediente'];

        // Limpiar la fecha de descargo: si es vacía o 1900-01-01, la convertimos a NULL
        if (empty($nuevaFechaDescargo) || $nuevaFechaDescargo == '1900-01-01') {
            $nuevaFechaDescargo = null;
        }

        // Actualizar fechas (notificación y descargo)
        $sqlUpdate = "UPDATE expediente_fi SET 
                        fechaNotificacionInicioPAS = ?,
                        fechaDescargoPresentado = ?
                      WHERE idExpedienteFI = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([$nuevaFechaNotificacion, $nuevaFechaDescargo, $idExpedienteFI]);

        // Eliminar plazos antiguos asociados a este registro FI
        $sqlDelete = "DELETE FROM expediente_plazos WHERE idExpedienteFI = ? AND evento IN ('DESCARGO_PAS', 'CADUCIDAD_PAS')";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([$idExpedienteFI]);

        // Generar nuevos plazos si hay fecha de notificación
        $fechaVencimientoDescargo = null;
        if (!empty($nuevaFechaNotificacion)) {
    // Plazo de descargo
        $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $nuevaFechaNotificacion, $diasDescargo);
        if ($fechaVencimientoDescargo) {
            $sqlPlazo = "INSERT INTO expediente_plazos (
                            idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                            fechaVencimiento, estado, alarmaEnviada
                        ) VALUES (?, ?, 'DESCARGO_PAS', ?, ?, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
            $stmtPlazo = $pdo->prepare($sqlPlazo);
            // Ahora 5 valores
            $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $nuevaFechaNotificacion, $diasDescargo, $fechaVencimientoDescargo]);
        }

        // Plazo de caducidad
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

        // Actualizar el estado del plazo de descargo SOLO si se proporcionó una fecha de descargo válida
        if (!empty($nuevaFechaDescargo) && !empty($fechaVencimientoDescargo)) {
            // Si la fecha de descargo es menor o igual a la fecha de vencimiento, se considera CUMPLIDO
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
// 1. Obtener/crear FS para un FI
function obtenerOCrearFS(PDO $pdo, $idExpedienteFI)
{
    // Buscar si ya existe FS para este FI
    $sql = "SELECT * FROM expediente_fs WHERE idExpedienteFI = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFI]);
    $fs = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fs) {
        return $fs;
    }
    // Si no existe, crear un registro vacío (solo con idExpedienteFI e idExpediente)
    // Primero obtener idExpediente desde FI
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
    // Obtener el registro recién creado
    $sqlGet = "SELECT * FROM expediente_fs WHERE idExpedienteFS = ?";
    $stmtGet = $pdo->prepare($sqlGet);
    $stmtGet->execute([$idFS]);
    return $stmtGet->fetch(PDO::FETCH_ASSOC);
}
// 2. Listar FS (con plazos)
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
//3. Obtener un registro FS por ID
function obtenerExpedienteFS(PDO $pdo, $idExpedienteFS)
{
    $sql = "SELECT * FROM expediente_fs WHERE idExpedienteFS = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpedienteFS]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
//4. Guardar/Actualizar FS
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

        // Obtener idExpediente desde FI
        $sqlFI = "SELECT idExpediente FROM expediente_fi WHERE idExpedienteFI = ?";
        $stmtFI = $pdo->prepare($sqlFI);
        $stmtFI->execute([$idExpedienteFI]);
        $rowFI = $stmtFI->fetch();
        if (!$rowFI) {
            throw new Exception("Expediente FI no encontrado");
        }
        $idExpediente = $rowFI['idExpediente'];

        if ($idExpedienteFS) {
            // Actualizar
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
                $idExpedienteFS
            ]);
        } else {
            // Insertar
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
                $observacionesContencioso
            ]);
            $idExpedienteFS = $pdo->lastInsertId();
        }

        // Recalcular plazos: eliminar plazos antiguos de este FS
        $sqlDelete = "DELETE FROM expediente_plazos WHERE idExpedienteFS = ? AND evento IN ('DESCARGO_IFI', 'RECURSO_SANCION', 'CUMPLIMIENTO_CONSENTIDA')";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([$idExpedienteFS]);

        // Generar nuevos plazos si hay fecha de notificación IFI
        if (!empty($fechaNotificacionIFI)) {
            $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $fechaNotificacionIFI, 5);
            if ($fechaVencimientoDescargo) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFS, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'DESCARGO_IFI', ?, 5, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFS, $fechaNotificacionIFI, $fechaVencimientoDescargo]);
                // Si hay fecha de descargo, actualizar estado
                if (!empty($fechaDescargoIFI)) {
                    $estado = (strtotime($fechaDescargoIFI) <= strtotime($fechaVencimientoDescargo)) ? 'CUMPLIDO' : 'VENCIDO';
                    $sqlUpdate = "UPDATE expediente_plazos SET estado = ?, fechaCumplimiento = ? 
                                  WHERE idExpedienteFS = ? AND evento = 'DESCARGO_IFI'";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->execute([$estado, $fechaDescargoIFI, $idExpedienteFS]);
                }
            }
        }

        // Generar plazo para recurso de sanción (15 días hábiles desde fechaNotificacionSancion)
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

        // Generar plazo para cumplimiento de consentida (15 días hábiles desde fechaNotificacionConsentida)
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
//5. Funciones para Pagos
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
        $data['observaciones']
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
// Función para UFRESA
function listarExpedientesUFRESA(PDO $pdo)
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                e.estadoExpediente,
                e.responsable,
                (SELECT CONCAT(s.nombre, ' - ', s.direccion) FROM sede s WHERE s.idSede = e.idSede) AS nombreSede
            FROM expediente e
            WHERE e.areaOrigen = 'UFRESA'   
            ORDER BY e.idExpediente DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

