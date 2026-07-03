<?php
// persistencia/dExpediente.php

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

function insertarExpediente(PDO $pdo, array $data)
{
    $campos = [
        'idSede', 'codigoUfremid', 'judicializado', 'responsable', 'observacion',
        'numeroFolios', 'numeroActa', 'fechaInspeccion', 'msfechaDescargoDeActa',
        'msinformeTecnicoInspeccion', 'msinformeTecnicoInspeccionFecha',
        'msNCertificadoBuenasPracticas', 'mscertificadoBuenasPracticasFechaInicio',
        'mscertificadoBuenasPracticasFechaFin', 'msresolucionCierreTemporal',
        'msnotificacionDeResolucionCierreTemporalFecha', 'msdescargoDeResolucionDeCierre',
        'msresolucionLevantamientoCierre', 'msnotificacionLevantamientoCierreFecha',
        'fiOficioInicioPas', 'fiOficioInicioPasFechaNotificacion', 'fiFechaDescargo5Dias',
        'fiCaducidadOficio', 'fiCaducidadFecha', 'fiOficioElevaResolverNulidad',
        'fiOficioElevaResolverNulidadFecha', 'fiRespuestaNulidad', 'fiRespuestaNulidadFecha',
        'fsInformeFinalQf', 'fsInformeFinalQfFecha', 'fsOficioEmitidoGeresaSgrs',
        'fsOficioEmitidoGeresaSgrsFechaNotificacion', 'fsFechaDescargo5Dias',
        'fsOficioElevaResolverNulidad', 'fsOficioElevaResolverNulidadFecha',
        'fsRespuestaNulidad', 'fsRespuestaNulidadFecha', 'tipodeInspeccion',
        'numeroCertificadoBuenasPracticas', 'fechaInicioCertificacionBuenasPracticas',
        'fechaFinCertificacionBuenasPracticas', 'registroCierreTemporal',
        'fechaNotificacionMedidasControl', 'fechaRecursoInterpuesto',
        'registroSuspensionCierre', 'fechaNotificacionSuspension', 'idPersonal',
        'codigoSubgerencia', 'areaOrigen', 'idTipoExpediente'
    ];
    $placeholders = array_fill(0, count($campos), '?');
    $values = [];
    foreach ($campos as $campo) {
        $values[] = isset($data[$campo]) && $data[$campo] !== '' ? $data[$campo] : null;
    }
    // Agregar fechas de creación y modificación (sin activo)
    $campos[] = 'fechaCreacion';
    $campos[] = 'fechaModificacion';
    $placeholders[] = '?';
    $placeholders[] = '?';
    $values[] = date('Y-m-d H:i:s');
    $values[] = date('Y-m-d H:i:s');

    $sql = "INSERT INTO expediente (" . implode(',', $campos) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    return $pdo->lastInsertId();
}

function actualizarExpediente(PDO $pdo, array $data)
{
    $idExpediente = $data['idExpediente'];
    $campos = [
        'idSede', 'codigoUfremid', 'judicializado', 'responsable', 'observacion',
        'numeroFolios', 'numeroActa', 'fechaInspeccion', 'msfechaDescargoDeActa',
        'msinformeTecnicoInspeccion', 'msinformeTecnicoInspeccionFecha',
        'msNCertificadoBuenasPracticas', 'mscertificadoBuenasPracticasFechaInicio',
        'mscertificadoBuenasPracticasFechaFin', 'msresolucionCierreTemporal',
        'msnotificacionDeResolucionCierreTemporalFecha', 'msdescargoDeResolucionDeCierre',
        'msresolucionLevantamientoCierre', 'msnotificacionLevantamientoCierreFecha',
        'fiOficioInicioPas', 'fiOficioInicioPasFechaNotificacion', 'fiFechaDescargo5Dias',
        'fiCaducidadOficio', 'fiCaducidadFecha', 'fiOficioElevaResolverNulidad',
        'fiOficioElevaResolverNulidadFecha', 'fiRespuestaNulidad', 'fiRespuestaNulidadFecha',
        'fsInformeFinalQf', 'fsInformeFinalQfFecha', 'fsOficioEmitidoGeresaSgrs',
        'fsOficioEmitidoGeresaSgrsFechaNotificacion', 'fsFechaDescargo5Dias',
        'fsOficioElevaResolverNulidad', 'fsOficioElevaResolverNulidadFecha',
        'fsRespuestaNulidad', 'fsRespuestaNulidadFecha', 'tipodeInspeccion',
        'numeroCertificadoBuenasPracticas', 'fechaInicioCertificacionBuenasPracticas',
        'fechaFinCertificacionBuenasPracticas', 'registroCierreTemporal',
        'fechaNotificacionMedidasControl', 'fechaRecursoInterpuesto',
        'registroSuspensionCierre', 'fechaNotificacionSuspension', 'idPersonal',
        'codigoSubgerencia', 'areaOrigen', 'idTipoExpediente'
    ];
    $sets = [];
    $values = [];
    foreach ($campos as $campo) {
        $sets[] = "$campo = ?";
        $values[] = isset($data[$campo]) && $data[$campo] !== '' ? $data[$campo] : null;
    }
    $sets[] = "fechaModificacion = ?";
    $values[] = date('Y-m-d H:i:s');
    $values[] = $idExpediente;

    $sql = "UPDATE expediente SET " . implode(',', $sets) . " WHERE idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    return $stmt->rowCount();
}

function eliminarExpediente(PDO $pdo, $idExpediente)
{
    $sql = "DELETE FROM expediente WHERE idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    return $stmt->rowCount();
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
    if (empty($fechaInicio)) return null;
    $fecha = new DateTime($fechaInicio);
    $contador = 0;
    while ($contador < $dias) {
        $fecha->modify('+1 day');
        $diaSemana = $fecha->format('N'); // 1=Lunes, 7=Domingo
        // Verificar si es feriado (tabla FeriadosPeru)
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
    if (empty($fechaInicio)) return null;
    $fecha = new DateTime($fechaInicio);
    $fecha->modify("+$meses months");
    return $fecha->format('Y-m-d');
}

/**
 * Inserta un nuevo registro FI y genera los plazos
 */
function insertarExpedienteFI(PDO $pdo, array $data)
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
            // Plazo de descargo (5 días hábiles)
            $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $fechaNotificacionInicioPAS, 5);
            if ($fechaVencimientoDescargo) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'DESCARGO_PAS', ?, 5, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $fechaNotificacionInicioPAS, $fechaVencimientoDescargo]);
            }

            // Plazo de caducidad (9 meses)
            $fechaVencimientoCaducidad = sumarMeses($fechaNotificacionInicioPAS, 9);
            if ($fechaVencimientoCaducidad) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'CADUCIDAD_PAS', ?, 9, 'MESES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $fechaNotificacionInicioPAS, $fechaVencimientoCaducidad]);
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
function actualizarExpedienteFI(PDO $pdo, $idExpedienteFI, $nuevaFechaNotificacion, $nuevaFechaDescargo = null)
{
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
            // Plazo de descargo (5 días hábiles)
            $fechaVencimientoDescargo = sumarDiasHabiles($pdo, $nuevaFechaNotificacion, 5);
            if ($fechaVencimientoDescargo) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'DESCARGO_PAS', ?, 5, 'DIAS_HABILES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $nuevaFechaNotificacion, $fechaVencimientoDescargo]);
            }

            // Plazo de caducidad (9 meses)
            $fechaVencimientoCaducidad = sumarMeses($nuevaFechaNotificacion, 9);
            if ($fechaVencimientoCaducidad) {
                $sqlPlazo = "INSERT INTO expediente_plazos (
                                idExpediente, idExpedienteFI, evento, fechaOrigen, plazo, unidad,
                                fechaVencimiento, estado, alarmaEnviada
                            ) VALUES (?, ?, 'CADUCIDAD_PAS', ?, 9, 'MESES', ?, 'VIGENTE', 0)";
                $stmtPlazo = $pdo->prepare($sqlPlazo);
                $stmtPlazo->execute([$idExpediente, $idExpedienteFI, $nuevaFechaNotificacion, $fechaVencimientoCaducidad]);
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
?>

