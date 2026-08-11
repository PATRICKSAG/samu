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
                e.areaOrigen,
                e.fechaInspeccion,
                e.estadoExpediente,
                e.responsable,
                e.falsificado,
                (SELECT nombre FROM tipoExpediente WHERE idTipoExpediente = e.idTipoExpediente) AS tipoExpediente,
                s.nombre AS sedeNombre,
                s.direccion AS sedeDireccion,
                s.numeroEstacion,
                est.ruc,
                est.razonSocial,
                d.nombre AS distrito,
                p.nombre AS provincia,
                dep.nombre AS departamento
            FROM expediente e
            LEFT JOIN sede s ON e.idSede = s.idSede
            LEFT JOIN establecimiento est ON s.idEstablecimiento = est.idEstablecimiento
            LEFT JOIN distrito d ON s.idDistrito = d.idDistrito
            LEFT JOIN provincia p ON d.idProvincia = p.idProvincia
            LEFT JOIN departamento dep ON p.idDepartamento = dep.idDepartamento
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
                e.areaOrigen,
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
                s.areaOrigen,
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