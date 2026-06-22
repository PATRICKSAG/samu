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
function obtenerExpedientePorId(PDO $pdo, $idExpediente)
{
    $sql = "SELECT * FROM expediente WHERE idExpediente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idExpediente]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
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
?>