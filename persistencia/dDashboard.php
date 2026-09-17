<?php
// persistencia/dDashboard.php
// Funciones públicas para el dashboard (sin autenticación)

/**
 * Lista los expedientes de un área en un año con datos de sede/establecimiento.
 */
function listarExpedientesDetalle(PDO $pdo, $area, $anio)
{
    $anio = (int)$anio;

    if ($area === 'UFREMID') {
        $sql = "SELECT 
                    e.idExpediente, e.numeroActa, e.fechaInspeccion, e.estadoExpediente,
                    e.responsable, e.falsificado, e.judicializado,
                    s.nombre AS sedeNombre, s.direccion AS sedeDireccion,
                    est.ruc, est.razonSocial,
                    te.nombre + ' - ' + te.descripcion AS categoria,
                    CASE 
                        WHEN te.nombre = 'I' THEN 'Inspecciones reglamentarias a oficinas farmacéuticas públicas y privadas'
                        WHEN te.nombre = 'V' THEN 'Inspección por verificación a EE.FF. y No FF.'
                        WHEN te.nombre = 'IVP' THEN 'Inspección de verificación de registro y cumplimiento al SIFP'
                        WHEN te.nombre = 'BPA' THEN 'Inspecciones reglamentarias a droguerías y almacenes'
                        WHEN te.nombre = 'BPDT' THEN 'Certificación BPA, Distribución y Transporte'
                        WHEN te.nombre = 'PROA' THEN 'Verificación de antimicrobianos (PROA)'
                        WHEN te.nombre = 'LG' THEN 'Verificación de medicamentos genéricos en DCI'
                        WHEN te.nombre = 'FARES' THEN 'Inspecciones reglamentarias en FARES'
                        WHEN te.nombre = 'A.V.' THEN 'Inspección por verificación a EE.FF.'
                        WHEN te.nombre = 'BPOF' THEN 'Buenas Prácticas de Oficina Farmacéutica'
                        WHEN te.nombre = 'BPF' THEN 'Buenas Prácticas de Farmacovigilancia'
                        ELSE 'Otras'
                    END AS actividadOperativa
                FROM expediente e WITH(NOLOCK)
                LEFT JOIN sede s WITH(NOLOCK) ON e.idSede = s.idSede
                LEFT JOIN establecimiento est WITH(NOLOCK) ON s.idEstablecimiento = est.idEstablecimiento
                LEFT JOIN tipoExpediente te WITH(NOLOCK) ON e.idTipoExpediente = te.idTipoExpediente
                WHERE e.areaOrigen = 'UFREMID'
                  AND YEAR(e.fechaInspeccion) = ?
                ORDER BY e.fechaInspeccion DESC, e.idExpediente DESC";
    } elseif ($area === 'UFRESA') {
        $sql = "SELECT 
                    e.idExpediente, e.numeroActa, e.fechaInspeccion, e.estadoExpediente,
                    e.responsable, e.falsificado, e.judicializado,
                    s.nombre AS sedeNombre, s.direccion AS sedeDireccion,
                    est.ruc, est.razonSocial,
                    cat.nombre AS categoria,
                    CASE 
                        WHEN cat.nombre = 'Agua de consumo' THEN 'Fiscalización de calidad del agua para consumo humano'
                        WHEN cat.nombre = 'ALIMENTOS Y BEBIDAS DE CONSUMO HUMANO' THEN 'Fiscalización en inocuidad alimentaria'
                        WHEN cat.nombre = 'Piscinas' THEN 'Fiscalización de piscinas de uso colectivo'
                        WHEN cat.nombre = 'Juguetes y útiles' THEN 'Fiscalización de juguetes y útiles de escritorio'
                        WHEN cat.nombre = 'Empresas de saneamiento' THEN 'Fiscalización a empresas de saneamiento ambiental'
                        WHEN cat.nombre = 'Residuos sólidos, establecimientos de salud y apoyo' THEN 'Fiscalización en residuos sólidos'
                        WHEN cat.nombre = 'SERVICIOS FUNERARIOS' THEN 'Fiscalización a servicios funerarios'
                        ELSE 'Otras'
                    END AS actividadOperativa
                FROM expediente e WITH(NOLOCK)
                LEFT JOIN sede s WITH(NOLOCK) ON e.idSede = s.idSede
                LEFT JOIN establecimiento est WITH(NOLOCK) ON s.idEstablecimiento = est.idEstablecimiento
                LEFT JOIN categoria cat WITH(NOLOCK) ON s.idCategoria = cat.idCategoria
                WHERE e.areaOrigen = 'UFRESA'
                  AND YEAR(e.fechaInspeccion) = ?
                ORDER BY e.fechaInspeccion DESC, e.idExpediente DESC";
    } else { // UFRESBIT
        $sql = "SELECT 
                    e.idExpediente, e.numeroActa, e.fechaInspeccion, e.estadoExpediente,
                    e.responsable, e.falsificado, e.judicializado,
                    s.nombre AS sedeNombre, s.direccion AS sedeDireccion,
                    est.ruc, est.razonSocial,
                    cr.nombre AS categoria,
                    tr.nombre AS actividadOperativa
                FROM expediente e WITH(NOLOCK)
                LEFT JOIN sede s WITH(NOLOCK) ON e.idSede = s.idSede
                LEFT JOIN establecimiento est WITH(NOLOCK) ON s.idEstablecimiento = est.idEstablecimiento
                LEFT JOIN clasificacionRenipress cr WITH(NOLOCK) ON s.idClasificacionRenipress = cr.idClasificacionRenipress
                LEFT JOIN tipoRenipress tr WITH(NOLOCK) ON s.idTipoRenipress = tr.idTipoRenipress
                WHERE e.areaOrigen = 'UFRESBIT'
                  AND YEAR(e.fechaInspeccion) = ?
                ORDER BY e.fechaInspeccion DESC, e.idExpediente DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$anio]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Conteo de expedientes por área y mes (para KPIs).
 */
function contarExpedientesPorAreaAnio(PDO $pdo, $anio)
{
    $sql = "SELECT areaOrigen, COUNT(*) AS total
            FROM expediente WITH(NOLOCK)
            WHERE YEAR(fechaInspeccion) = ?
            GROUP BY areaOrigen";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$anio]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = ['UFREMID' => 0, 'UFRESA' => 0, 'UFRESBIT' => 0];
    foreach ($rows as $r) {
        if (isset($resultado[$r['areaOrigen']])) {
            $resultado[$r['areaOrigen']] = (int)$r['total'];
        }
    }
    return $resultado;
}