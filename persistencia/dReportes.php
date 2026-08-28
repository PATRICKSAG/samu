<?php
// persistencia/dReportes.php

/**
 * Reporte 1: General de Expedientes
 */
function reporteExpedientesGeneral(PDO $pdo, $filtros = [])
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE e.areaOrigen 
                END AS areaOrigen,
                e.fechaInspeccion,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESA', 'UFREMID') THEN CAT.NOMBRE 
                    ELSE NULL 
                END AS Categoria,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN TR.nombre
                    ELSE NULL 
                END AS TipoIpress,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN CR.nombre
                    ELSE NULL 
                END AS ClasificacionIpress,
                e.estadoExpediente,
                e.responsable,
                e.falsificado,
                (SELECT nombre FROM tipoExpediente WITH(NOLOCK) WHERE idTipoExpediente = e.idTipoExpediente) AS tipoExpediente,
                s.nombre AS sedeNombre,
                s.direccion AS sedeDireccion,
                s.numeroEstacion,
                est.ruc,
                est.razonSocial,
                d.nombre AS distrito,
                p.nombre AS provincia,
                dep.nombre AS departamento
            FROM expediente e WITH(NOLOCK)
            LEFT JOIN sede s WITH(NOLOCK) ON e.idSede = s.idSede
            LEFT JOIN establecimiento est WITH(NOLOCK) ON s.idEstablecimiento = est.idEstablecimiento
            LEFT JOIN distrito d WITH(NOLOCK) ON s.idDistrito = d.idDistrito
            LEFT JOIN provincia p WITH(NOLOCK) ON d.idProvincia = p.idProvincia
            LEFT JOIN departamento dep WITH(NOLOCK) ON p.idDepartamento = dep.idDepartamento
            LEFT JOIN categoria CAT WITH(NOLOCK) ON s.idCategoria = CAT.idCategoria
            LEFT JOIN tipoRenipress TR WITH(NOLOCK) ON TR.idTipoRenipress = S.idTipoRenipress
            LEFT JOIN clasificacionRenipress CR WITH(NOLOCK) ON CR.idClasificacionRenipress = S.idClasificacionRenipress
            WHERE 1=1";

    $params = [];
    if (!empty($filtros['area'])) {
        $sql .= " AND e.areaOrigen = ?";
        $params[] = $filtros['area'];
    }
    if (!empty($filtros['estado'])) {
        $sql .= " AND e.estadoExpediente = ?";
        $params[] = $filtros['estado'];
    }
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND e.fechaInspeccion >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND e.fechaInspeccion <= ?";
        $params[] = $filtros['fecha_hasta'];
    }

    $sql .= " ORDER BY e.fechaInspeccion DESC, e.idExpediente DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reporte 2: Plazos Críticos (Vencidos o Próximos a vencer)
 */
function reportePlazosCriticos(PDO $pdo, $filtros = [])
{
    $hoy = date('Y-m-d');
    $limite = date('Y-m-d', strtotime('+7 days'));

    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE e.areaOrigen 
                END AS areaOrigen,
                e.responsable,
                p.evento,
                p.fechaOrigen,
                p.fechaVencimiento,
                p.estado,
                p.fechaCumplimiento,
                DATEDIFF(day, GETDATE(), p.fechaVencimiento) AS dias_restantes
            FROM expediente_plazos p
            INNER JOIN expediente e ON p.idExpediente = e.idExpediente
            WHERE p.fechaCumplimiento IS NULL
              AND p.fechaVencimiento IS NOT NULL
              AND p.fechaVencimiento <= ? 
              AND p.fechaVencimiento >= ?";

    $params = [$limite, $hoy];

    if (!empty($filtros['area'])) {
        $sql .= " AND e.areaOrigen = ?";
        $params[] = $filtros['area'];
    }
    if (!empty($filtros['evento'])) {
        $sql .= " AND p.evento = ?";
        $params[] = $filtros['evento'];
    }

    $sql .= " ORDER BY p.fechaVencimiento ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reporte 3: Cumplimiento DIGEMID (UFREMID)
 */
function reporteDigemid(PDO $pdo, $filtros = [])
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                e.fechaInspeccion,
                e.responsable,
                ed.idEquipoDCVS,
                (SELECT nombre FROM EquipoDCVSUFREMID WHERE idEquipo = ed.idEquipoDCVS) AS equipo,
                (SELECT nombre FROM ActividadAbrevUFREMID WHERE idActividad = ed.idActividadAbrev) AS actividad,
                (SELECT nombre FROM TipoActividadUFREMID WHERE idTipoActividad = ed.idTipoActividad) AS tipoActividad,
                ed.condicionEjecucion,
                ed.atendidoPor,
                ed.horarioAtencionQF,
                ed.permanenciaQF,
                ed.cumplimientoBPOF,
                ed.bpd,
                ed.bpa,
                ed.bpf,
                ed.bpsf,
                ed.bpdt,
                ed.cumplimientoBPA,
                ed.cumplimientoBPDyT,
                ed.cumplimientoBPF,
                ed.productosIncautados,
                (SELECT medida FROM medidasSeguridadDIGEMID WHERE idExpediente = e.idExpediente) AS medidaSeguridad
            FROM expediente e
            INNER JOIN expediente_digemid ed ON e.idExpediente = ed.idExpediente
            WHERE e.areaOrigen = 'UFREMID'";

    $params = [];
    if (!empty($filtros['equipo'])) {
        $sql .= " AND ed.idEquipoDCVS = ?";
        $params[] = $filtros['equipo'];
    }
    if (!empty($filtros['cumplimiento'])) {
        $sql .= " AND ed.cumplimientoBPOF = ?";
        $params[] = $filtros['cumplimiento'];
    }
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND e.fechaInspeccion >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND e.fechaInspeccion <= ?";
        $params[] = $filtros['fecha_hasta'];
    }

    $sql .= " ORDER BY e.fechaInspeccion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reporte 4: Sedes por Ubicación y Actividad
 */
function reporteSedes(PDO $pdo, $filtros = [])
{
    $sql = "SELECT 
                s.idSede,
                s.nombre AS sedeNombre,
                s.numeroEstacion,
                s.direccion,
                s.telefono,
                s.horarioFuncionamiento,
                s.tieneQuimicoFarmaceutico AS qf,
                CASE 
                    WHEN s.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE s.areaOrigen 
                END AS areaOrigen,
                s.categorizacion,
                s.inicioActividad,
                est.ruc,
                est.razonSocial,
                c.nombre AS categoria,
                se.nombre AS situacionEstablecimiento,
                sd.nombre AS situacionDigemid,
                dep.nombre AS departamento,
                p.nombre AS provincia,
                d.nombre AS distrito,
                (SELECT COUNT(*) FROM expediente WHERE idSede = s.idSede) AS totalExpedientes,
                (SELECT MAX(fechaInspeccion) FROM expediente WHERE idSede = s.idSede) AS ultimaInspeccion
            FROM sede s
            LEFT JOIN establecimiento est ON s.idEstablecimiento = est.idEstablecimiento
            LEFT JOIN categoria c ON s.idCategoria = c.idCategoria
            LEFT JOIN situacion_establecimiento se ON s.idSituacionEstablecimiento = se.idSituacionEstablecimiento
            LEFT JOIN situacion_digemid sd ON s.idSituacionDigemid = sd.idSituacionDigemid
            LEFT JOIN departamento dep ON s.idDepartamento = dep.idDepartamento
            LEFT JOIN provincia p ON s.idProvincia = p.idProvincia
            LEFT JOIN distrito d ON s.idDistrito = d.idDistrito
            WHERE s.activo = 1";

    $params = [];
    if (!empty($filtros['departamento'])) {
        $sql .= " AND s.idDepartamento = ?";
        $params[] = $filtros['departamento'];
    }
    if (!empty($filtros['provincia'])) {
        $sql .= " AND s.idProvincia = ?";
        $params[] = $filtros['provincia'];
    }
    if (!empty($filtros['distrito'])) {
        $sql .= " AND s.idDistrito = ?";
        $params[] = $filtros['distrito'];
    }
    if (!empty($filtros['area'])) {
        $sql .= " AND s.areaOrigen = ?";
        $params[] = $filtros['area'];
    }

    $sql .= " ORDER BY s.idSede";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reporte 5: Medidas de Seguridad (MS y DIGEMID)
 */
function reporteMedidasSeguridad(PDO $pdo, $filtros = [])
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE e.areaOrigen 
                END AS areaOrigen,
                e.fechaInspeccion,
                s.nombre AS sedeNombre,
                est.ruc,
                est.razonSocial,
                -- Medidas de Seguridad (MS)
                ms.fechaDescargoActa,
                ms.oficioOtorgaDeniegaPlazo,
                ms.idSituacionDigemidSeleccionada,
                (SELECT nombre FROM situacion_digemid WHERE idSituacionDigemid = ms.idSituacionDigemidSeleccionada) AS situacionSeleccionada,
                ms.docElevaNulidad,
                ms.resuelveNulidad,
                ms.informeTecnicoInspeccion,
                ms.nCertificadoBuenasPracticas,
                ms.fechaInicioCertificadoBP,
                ms.fechaFinCertificadoBP,
                ms.rgrRatificaCierreTemporal,
                ms.fechaNotificacionRGRCierre,
                ms.descargoApelacion,
                ms.nDocResuelveRecurso,
                ms.rsgLevantamientoCierre,
                ms.fechaNotificacionRSGLevantamiento,
                ms.cierreDefinitivo,
                ms.fechaNotificacionCierreDefinitivo,
                -- Medida DIGEMID (desde la tabla medidasSeguridadDIGEMID)
                (SELECT medida FROM medidasSeguridadDIGEMID WHERE idExpediente = e.idExpediente) AS medidaDigemid
            FROM expediente e
            LEFT JOIN sede s ON e.idSede = s.idSede
            LEFT JOIN establecimiento est ON s.idEstablecimiento = est.idEstablecimiento
            LEFT JOIN expediente_ms ms ON e.idExpediente = ms.idExpediente
            WHERE 1=1";

    $params = [];
    if (!empty($filtros['area'])) {
        $sql .= " AND e.areaOrigen = ?";
        $params[] = $filtros['area'];
    }
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND e.fechaInspeccion >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND e.fechaInspeccion <= ?";
        $params[] = $filtros['fecha_hasta'];
    }
    // Filtro por tipo de medida
    if (!empty($filtros['tipo_medida'])) {
        switch ($filtros['tipo_medida']) {
            case 'descargo':
                $sql .= " AND ms.fechaDescargoActa IS NOT NULL";
                break;
            case 'cierre':
                $sql .= " AND ms.rgrRatificaCierreTemporal IS NOT NULL";
                break;
            case 'definitivo':
                $sql .= " AND ms.cierreDefinitivo IS NOT NULL";
                break;
            case 'digemid':
                // Filtrar solo aquellos que tienen medidaDigemid no nula
                $sql .= " AND EXISTS (SELECT 1 FROM medidasSeguridadDIGEMID WHERE idExpediente = e.idExpediente)";
                break;
            default:
                // Si es "todos" no agregamos filtro adicional
                break;
        }
    }

    $sql .= " ORDER BY e.fechaInspeccion DESC, e.idExpediente DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reporte 6: Fase Instructora (FI)
 */
function reporteFaseInstructora(PDO $pdo, $filtros = [])
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE e.areaOrigen 
                END AS areaOrigen,
                e.fechaInspeccion,
                e.responsable,
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
                fi.fechaCreacion AS fechaRegistroFI,
                -- Plazos asociados
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
            INNER JOIN expediente e ON fi.idExpediente = e.idExpediente
            WHERE 1=1";

    $params = [];
    if (!empty($filtros['area'])) {
        $sql .= " AND e.areaOrigen = ?";
        $params[] = $filtros['area'];
    }
    if (!empty($filtros['tipo_evento'])) {
        $sql .= " AND fi.tipoEvento = ?";
        $params[] = $filtros['tipo_evento'];
    }
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND fi.fechaNotificacionInicioPAS >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND fi.fechaNotificacionInicioPAS <= ?";
        $params[] = $filtros['fecha_hasta'];
    }

    $sql .= " ORDER BY e.fechaInspeccion DESC, fi.idExpedienteFI DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reporte 7: Fase Sancionadora (FS) y Pagos
 */
function reporteFaseSancionadora(PDO $pdo, $filtros = [])
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE e.areaOrigen 
                END AS areaOrigen,
                e.fechaInspeccion,
                e.responsable,
                fs.idExpedienteFS,
                fs.oficioTrasladaIFI,
                fs.fechaNotificacionIFI,
                fs.fechaDescargoIFI,
                fs.nResolucionSancion,
                fs.nInfraccion,
                fs.sancionImpuesta,
                fs.fechaNotificacionSancion,
                fs.recursoInterpuestoSancion,
                fs.fechaRecursoSancion,
                fs.pagoApela,
                fs.resolucionRecursoSancion,
                fs.resultadoRecurso,
                fs.fechaNotificacionRecursoSancion,
                fs.resolucionConsentida,
                fs.fechaNotificacionConsentida,
                fs.oficioElevaApelacion,
                fs.resolucionApelacion,
                fs.fechaNotificacionApelacion,
                fs.pagaDemandaContenciosa,
                fs.oficioSolicitaInfoProcurador,
                fs.estadoContencioso,
                fs.observacionesContencioso,
                -- Plazos
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
                -- Pagos (suma total)
                COALESCE((SELECT SUM(monto) FROM expediente_pagos WHERE idExpedienteFS = fs.idExpedienteFS), 0) AS totalPagos
            FROM expediente_fs fs
            INNER JOIN expediente e ON fs.idExpediente = e.idExpediente
            WHERE 1=1";

    $params = [];
    if (!empty($filtros['area'])) {
        $sql .= " AND e.areaOrigen = ?";
        $params[] = $filtros['area'];
    }
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND fs.fechaNotificacionIFI >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND fs.fechaNotificacionIFI <= ?";
        $params[] = $filtros['fecha_hasta'];
    }
    if (!empty($filtros['tipo_sancion'])) {
        $sql .= " AND fs.sancionImpuesta = ?";
        $params[] = $filtros['tipo_sancion'];
    }

    $sql .= " ORDER BY e.fechaInspeccion DESC, fs.idExpedienteFS DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reporte 8: Judicializados
 */
function reporteJudicializados(PDO $pdo, $filtros = [])
{
    $sql = "SELECT 
                e.idExpediente,
                e.numeroActa,
                CASE 
                    WHEN e.areaOrigen IN ('UFRESBIT') THEN 'UFRESBYT'
                    ELSE e.areaOrigen 
                END AS areaOrigen,
                e.fechaInspeccion,
                e.estadoExpediente,
                e.responsable,
                e.judicializado,
                e.observacion,
                s.nombre AS sedeNombre,
                est.ruc,
                est.razonSocial
            FROM expediente e
            LEFT JOIN sede s ON e.idSede = s.idSede
            LEFT JOIN establecimiento est ON s.idEstablecimiento = est.idEstablecimiento
            WHERE e.judicializado IS NOT NULL AND e.judicializado != ''";

    $params = [];
    if (!empty($filtros['area'])) {
        $sql .= " AND e.areaOrigen = ?";
        $params[] = $filtros['area'];
    }
    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND e.fechaInspeccion >= ?";
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND e.fechaInspeccion <= ?";
        $params[] = $filtros['fecha_hasta'];
    }

    $sql .= " ORDER BY e.fechaInspeccion DESC, e.idExpediente DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}