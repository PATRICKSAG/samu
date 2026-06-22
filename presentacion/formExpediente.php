<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
$pdo = Database::getConexion();

include_once(__DIR__ . '/../persistencia/dExpediente.php');
include_once(__DIR__ . '/../persistencia/dSede.php');
include_once(__DIR__ . '/../persistencia/dTipoExpediente.php');

$tiposExpediente = listarTiposExpediente($pdo);
$sedes = listarSedes($pdo);

$mensaje = '';
$mensajeError = '';

// Procesar guardado/actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGuardar'])) {
    $data = $_POST;
    $numeroActa = trim($data['numeroActa'] ?? '');
    $idExpedienteActual = $data['idExpediente'] ?? null;

    // Validar campo requerido
    if (empty($numeroActa)) {
        $mensajeError = "El número de acta es obligatorio.";
    } elseif (empty($data['areaOrigen'])) {
        $mensajeError = "El área de origen es obligatoria.";
    } elseif (empty($data['idTipoExpediente'])) {
        $mensajeError = "El tipo de expediente es obligatorio.";
    } else {
        // Verificar unicidad de numeroActa (excepto en edición)
        $sql = "SELECT idExpediente FROM expediente WHERE numeroActa = ?";
        $params = [$numeroActa];
        if ($idExpedienteActual) {
            $sql .= " AND idExpediente != ?";
            $params[] = $idExpedienteActual;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $existente = $stmt->fetch();

        if ($existente) {
            $mensajeError = "Ya existe un expediente con el número de acta '$numeroActa'. Por favor, ingrese otro número.";
        } else {
            // Guardar
            if (!empty($idExpedienteActual)) {
                actualizarExpediente($pdo, $data);
                $mensaje = "Expediente actualizado correctamente.";
            } else {
                $idNuevo = insertarExpediente($pdo, $data);
                $mensaje = "Expediente creado con ID: $idNuevo";
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?mensaje=" . urlencode($mensaje));
            exit;
        }
    }
}

// Obtener datos para editar
$expedienteEditar = null;
if (isset($_GET['editar'])) {
    $idEditar = intval($_GET['editar']);
    $expedienteEditar = obtenerExpedientePorId($pdo, $idEditar);
}

// Listar expedientes
$expedientes = listarExpedientes($pdo);

$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Expedientes</title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <?php include 'select2-css.php'; ?>
    <style>
        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
        }
        .form-section {
            margin-top: 20px;
        }
        .required-star {
            color: red;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-3">
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($mensajeError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($mensajeError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><?= $expedienteEditar ? 'Editar Expediente' : 'Nuevo Expediente' ?></h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($expedienteEditar): ?>
                        <input type="hidden" name="idExpediente" value="<?= $expedienteEditar['idExpediente'] ?>">
                    <?php endif; ?>

                    <!-- Datos básicos (principales) -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="idSede" class="form-label">Sede <span class="required-star">*</span></label>
                            <select name="idSede" id="idSede" class="form-select select2-auto" required>
                                <option value="">Seleccione una sede</option>
                                <?php foreach ($sedes as $sede): ?>
                                    <option value="<?= $sede['idSede'] ?>" 
                                        <?= ($expedienteEditar && $expedienteEditar['idSede'] == $sede['idSede']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sede['idSede'] . ' - ' . $sede['razonSocial'] . ' - ' . $sede['direccion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="numeroActa" class="form-label">Número de Acta <span class="required-star">*</span></label>
                            <input required type="text" class="form-control" name="numeroActa" id="numeroActa" value="<?= $expedienteEditar['numeroActa'] ?? $_POST['numeroActa'] ?? '' ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="fechaInspeccion" class="form-label">Fecha Inspección</label>
                            <input type="date" class="form-control" name="fechaInspeccion" id="fechaInspeccion" value="<?= $expedienteEditar['fechaInspeccion'] ?? $_POST['fechaInspeccion'] ?? '' ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="areaOrigen" class="form-label">Área Origen <span class="required-star">*</span></label>
                            <select name="areaOrigen" id="areaOrigen" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="UFRESA" <?= ($expedienteEditar && $expedienteEditar['areaOrigen'] == 'UFRESA') ? 'selected' : '' ?>>UFRESA</option>
                                <option value="UFREMID" <?= ($expedienteEditar && $expedienteEditar['areaOrigen'] == 'UFREMID') ? 'selected' : '' ?>>UFREMID</option>
                                <option value="UFRESBIT" <?= ($expedienteEditar && $expedienteEditar['areaOrigen'] == 'UFRESBIT') ? 'selected' : '' ?>>UFRESBIT</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="idTipoExpediente" class="form-label">Tipo Expediente UFREMID<span class="required-star">*</span></label>
                            <select name="idTipoExpediente" id="idTipoExpediente" class="form-select select2-auto" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($tiposExpediente as $tipo): ?>
                                    <option value="<?= $tipo['idTipoExpediente'] ?>" <?= ($expedienteEditar && $expedienteEditar['idTipoExpediente'] == $tipo['idTipoExpediente']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tipo['nombre'] . ' - ' . $tipo['descripcion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="codigoUfremid" class="form-label">Código UFREMID</label>
                            <input type="text" class="form-control" name="codigoUfremid" id="codigoUfremid" value="<?= $expedienteEditar['codigoUfremid'] ?? $_POST['codigoUfremid'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="judicializado" class="form-label">Judicializado</label>
                            <input type="text" class="form-control" name="judicializado" id="judicializado" value="<?= $expedienteEditar['judicializado'] ?? $_POST['judicializado'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="responsable" class="form-label">Responsable</label>
                            <input type="text" class="form-control" name="responsable" id="responsable" value="<?= $expedienteEditar['responsable'] ?? $_POST['responsable'] ?? '' ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea class="form-control" name="observacion" id="observacion" rows="2"><?= $expedienteEditar['observacion'] ?? $_POST['observacion'] ?? '' ?></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="numeroFolios" class="form-label">Número de Folios</label>
                            <input type="text" class="form-control" name="numeroFolios" id="numeroFolios" value="<?= $expedienteEditar['numeroFolios'] ?? $_POST['numeroFolios'] ?? '' ?>">
                        </div>
                    </div>

                    <!-- Acordeón para campos adicionales -->
                    <div class="accordion" id="accordionExpediente">
                        <!-- Sección MS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingMS">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMS" aria-expanded="false" aria-controls="collapseMS">
                                    Medidas de Seguridad (MS)
                                </button>
                            </h2>
                            <div id="collapseMS" class="accordion-collapse collapse" aria-labelledby="headingMS" data-bs-parent="#accordionExpediente">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><label>Fecha Descargo Acta</label><input type="date" name="msfechaDescargoDeActa" class="form-control" value="<?= $expedienteEditar['msfechaDescargoDeActa'] ?? $_POST['msfechaDescargoDeActa'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Informe Técnico Inspección</label><input type="text" name="msinformeTecnicoInspeccion" class="form-control" value="<?= $expedienteEditar['msinformeTecnicoInspeccion'] ?? $_POST['msinformeTecnicoInspeccion'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Informe Técnico</label><input type="date" name="msinformeTecnicoInspeccionFecha" class="form-control" value="<?= $expedienteEditar['msinformeTecnicoInspeccionFecha'] ?? $_POST['msinformeTecnicoInspeccionFecha'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>N° Certificado Buenas Prácticas</label><input type="text" name="msNCertificadoBuenasPracticas" class="form-control" value="<?= $expedienteEditar['msNCertificadoBuenasPracticas'] ?? $_POST['msNCertificadoBuenasPracticas'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Inicio Certificado BP</label><input type="date" name="mscertificadoBuenasPracticasFechaInicio" class="form-control" value="<?= $expedienteEditar['mscertificadoBuenasPracticasFechaInicio'] ?? $_POST['mscertificadoBuenasPracticasFechaInicio'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Fin Certificado BP</label><input type="date" name="mscertificadoBuenasPracticasFechaFin" class="form-control" value="<?= $expedienteEditar['mscertificadoBuenasPracticasFechaFin'] ?? $_POST['mscertificadoBuenasPracticasFechaFin'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Resolución Cierre Temporal</label><input type="text" name="msresolucionCierreTemporal" class="form-control" value="<?= $expedienteEditar['msresolucionCierreTemporal'] ?? $_POST['msresolucionCierreTemporal'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Notificación Cierre Temporal</label><input type="date" name="msnotificacionDeResolucionCierreTemporalFecha" class="form-control" value="<?= $expedienteEditar['msnotificacionDeResolucionCierreTemporalFecha'] ?? $_POST['msnotificacionDeResolucionCierreTemporalFecha'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Descargo Resolución Cierre</label><input type="text" name="msdescargoDeResolucionDeCierre" class="form-control" value="<?= $expedienteEditar['msdescargoDeResolucionDeCierre'] ?? $_POST['msdescargoDeResolucionDeCierre'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Resolución Levantamiento Cierre</label><input type="text" name="msresolucionLevantamientoCierre" class="form-control" value="<?= $expedienteEditar['msresolucionLevantamientoCierre'] ?? $_POST['msresolucionLevantamientoCierre'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Notificación Levantamiento</label><input type="date" name="msnotificacionLevantamientoCierreFecha" class="form-control" value="<?= $expedienteEditar['msnotificacionLevantamientoCierreFecha'] ?? $_POST['msnotificacionLevantamientoCierreFecha'] ?? '' ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección FI -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFI">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFI" aria-expanded="false" aria-controls="collapseFI">
                                    Fiscalización (FI)
                                </button>
                            </h2>
                            <div id="collapseFI" class="accordion-collapse collapse" aria-labelledby="headingFI" data-bs-parent="#accordionExpediente">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><label>Oficio Inicio PAS</label><input type="text" name="fiOficioInicioPas" class="form-control" value="<?= $expedienteEditar['fiOficioInicioPas'] ?? $_POST['fiOficioInicioPas'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Notificación Oficio Inicio</label><input type="date" name="fiOficioInicioPasFechaNotificacion" class="form-control" value="<?= $expedienteEditar['fiOficioInicioPasFechaNotificacion'] ?? $_POST['fiOficioInicioPasFechaNotificacion'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Descargo (5 días)</label><input type="date" name="fiFechaDescargo5Dias" class="form-control" value="<?= $expedienteEditar['fiFechaDescargo5Dias'] ?? $_POST['fiFechaDescargo5Dias'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Caducidad Oficio</label><input type="text" name="fiCaducidadOficio" class="form-control" value="<?= $expedienteEditar['fiCaducidadOficio'] ?? $_POST['fiCaducidadOficio'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Caducidad</label><input type="date" name="fiCaducidadFecha" class="form-control" value="<?= $expedienteEditar['fiCaducidadFecha'] ?? $_POST['fiCaducidadFecha'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Oficio Eleva Resolver Nulidad</label><input type="text" name="fiOficioElevaResolverNulidad" class="form-control" value="<?= $expedienteEditar['fiOficioElevaResolverNulidad'] ?? $_POST['fiOficioElevaResolverNulidad'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Oficio Nulidad</label><input type="date" name="fiOficioElevaResolverNulidadFecha" class="form-control" value="<?= $expedienteEditar['fiOficioElevaResolverNulidadFecha'] ?? $_POST['fiOficioElevaResolverNulidadFecha'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Respuesta Nulidad</label><input type="text" name="fiRespuestaNulidad" class="form-control" value="<?= $expedienteEditar['fiRespuestaNulidad'] ?? $_POST['fiRespuestaNulidad'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Respuesta Nulidad</label><input type="date" name="fiRespuestaNulidadFecha" class="form-control" value="<?= $expedienteEditar['fiRespuestaNulidadFecha'] ?? $_POST['fiRespuestaNulidadFecha'] ?? '' ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección FS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFS">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFS" aria-expanded="false" aria-controls="collapseFS">
                                    Fase Sancionadora (FS)
                                </button>
                            </h2>
                            <div id="collapseFS" class="accordion-collapse collapse" aria-labelledby="headingFS" data-bs-parent="#accordionExpediente">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><label>Informe Final QF</label><input type="text" name="fsInformeFinalQf" class="form-control" value="<?= $expedienteEditar['fsInformeFinalQf'] ?? $_POST['fsInformeFinalQf'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Informe Final</label><input type="date" name="fsInformeFinalQfFecha" class="form-control" value="<?= $expedienteEditar['fsInformeFinalQfFecha'] ?? $_POST['fsInformeFinalQfFecha'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Oficio Emitido Geresa/SGRS</label><input type="text" name="fsOficioEmitidoGeresaSgrs" class="form-control" value="<?= $expedienteEditar['fsOficioEmitidoGeresaSgrs'] ?? $_POST['fsOficioEmitidoGeresaSgrs'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Notificación Oficio</label><input type="date" name="fsOficioEmitidoGeresaSgrsFechaNotificacion" class="form-control" value="<?= $expedienteEditar['fsOficioEmitidoGeresaSgrsFechaNotificacion'] ?? $_POST['fsOficioEmitidoGeresaSgrsFechaNotificacion'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Descargo (5 días)</label><input type="text" name="fsFechaDescargo5Dias" class="form-control" value="<?= $expedienteEditar['fsFechaDescargo5Dias'] ?? $_POST['fsFechaDescargo5Dias'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Oficio Eleva Nulidad</label><input type="text" name="fsOficioElevaResolverNulidad" class="form-control" value="<?= $expedienteEditar['fsOficioElevaResolverNulidad'] ?? $_POST['fsOficioElevaResolverNulidad'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Oficio Nulidad</label><input type="date" name="fsOficioElevaResolverNulidadFecha" class="form-control" value="<?= $expedienteEditar['fsOficioElevaResolverNulidadFecha'] ?? $_POST['fsOficioElevaResolverNulidadFecha'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Respuesta Nulidad</label><input type="text" name="fsRespuestaNulidad" class="form-control" value="<?= $expedienteEditar['fsRespuestaNulidad'] ?? $_POST['fsRespuestaNulidad'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Respuesta Nulidad</label><input type="date" name="fsRespuestaNulidadFecha" class="form-control" value="<?= $expedienteEditar['fsRespuestaNulidadFecha'] ?? $_POST['fsRespuestaNulidadFecha'] ?? '' ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Otros campos (sin repetir areaOrigen y tipo) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOtros">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOtros" aria-expanded="false" aria-controls="collapseOtros">
                                    Otros Datos
                                </button>
                            </h2>
                            <div id="collapseOtros" class="accordion-collapse collapse" aria-labelledby="headingOtros" data-bs-parent="#accordionExpediente">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><label>Tipo de Inspección</label><input type="text" name="tipodeInspeccion" class="form-control" value="<?= $expedienteEditar['tipodeInspeccion'] ?? $_POST['tipodeInspeccion'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>N° Certificado Buenas Prácticas</label><input type="text" name="numeroCertificadoBuenasPracticas" class="form-control" value="<?= $expedienteEditar['numeroCertificadoBuenasPracticas'] ?? $_POST['numeroCertificadoBuenasPracticas'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Inicio Certificación BP</label><input type="date" name="fechaInicioCertificacionBuenasPracticas" class="form-control" value="<?= $expedienteEditar['fechaInicioCertificacionBuenasPracticas'] ?? $_POST['fechaInicioCertificacionBuenasPracticas'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Fin Certificación BP</label><input type="date" name="fechaFinCertificacionBuenasPracticas" class="form-control" value="<?= $expedienteEditar['fechaFinCertificacionBuenasPracticas'] ?? $_POST['fechaFinCertificacionBuenasPracticas'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Registro Cierre Temporal</label><input type="text" name="registroCierreTemporal" class="form-control" value="<?= $expedienteEditar['registroCierreTemporal'] ?? $_POST['registroCierreTemporal'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Notificación Medidas Control</label><input type="date" name="fechaNotificacionMedidasControl" class="form-control" value="<?= $expedienteEditar['fechaNotificacionMedidasControl'] ?? $_POST['fechaNotificacionMedidasControl'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Recurso Interpuesto</label><input type="date" name="fechaRecursoInterpuesto" class="form-control" value="<?= $expedienteEditar['fechaRecursoInterpuesto'] ?? $_POST['fechaRecursoInterpuesto'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Registro Suspensión Cierre</label><input type="text" name="registroSuspensionCierre" class="form-control" value="<?= $expedienteEditar['registroSuspensionCierre'] ?? $_POST['registroSuspensionCierre'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Fecha Notificación Suspensión</label><input type="date" name="fechaNotificacionSuspension" class="form-control" value="<?= $expedienteEditar['fechaNotificacionSuspension'] ?? $_POST['fechaNotificacionSuspension'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>ID Personal</label><input type="number" name="idPersonal" class="form-control" value="<?= $expedienteEditar['idPersonal'] ?? $_POST['idPersonal'] ?? '' ?>"></div>
                                        <div class="col-md-6 mb-2"><label>Código Subgerencia</label><input type="text" name="codigoSubgerencia" class="form-control" value="<?= $expedienteEditar['codigoSubgerencia'] ?? $_POST['codigoSubgerencia'] ?? '' ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="btnGuardar" class="btn btn-success">Guardar Expediente</button>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Cancelar / Nuevo</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado de expedientes -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h4>Expedientes Registrados</h4>
            </div>
            <div class="card-body">
                <table id="tablaExpedientes" class="table table-striped table-bordered" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Sede</th>
                            <th>RUC</th>
                            <th>Razón Social</th>
                            <th>Nº Acta</th>
                            <th>Fecha Inspección</th>
                            <th>Dirección</th>
                            <th>Distrito</th>
                            <th>Provincia</th>
                            <th>Área</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expedientes as $exp): ?>
                        <tr>
                            <td><?= $exp['idExpediente'] ?></td>
                            <td><?= htmlspecialchars($exp['numeroEstacion'] . ' - ' . $exp['nombreSede']) ?></td>
                            <td><?= htmlspecialchars($exp['RUC'] ?? '') ?></td>
                            <td><?= htmlspecialchars($exp['razonSocial'] ?? '') ?></td>
                            <td><?= htmlspecialchars($exp['numeroActa'] ?? '') ?></td>
                            <td><?= $exp['fechaInspeccion'] ?? '' ?></td>
                            <td><?= htmlspecialchars($exp['direccion'] ?? '') ?></td>
                            <td><?= htmlspecialchars($exp['nombreDistrito'] ?? '') ?></td>
                            <td><?= htmlspecialchars($exp['nombreProvincia'] ?? '') ?></td>
                            <td><?= htmlspecialchars($exp['areaOrigen'] ?? '') ?></td>
                            <td>
                                <a href="?editar=<?= $exp['idExpediente'] ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="javascript:void(0)" onclick="eliminar(<?= $exp['idExpediente'] ?>)" class="btn btn-sm btn-danger">Eliminar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'boostrap-js.php'; ?>
    <?php include 'datatable-js.php'; ?>
    <?php include 'select2-js.php'; ?>

    <script>
        $(document).ready(function() {
            $('#tablaExpedientes').DataTable({
                // language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
            });
        });

        function eliminar(id) {
            if (confirm('¿Está seguro de eliminar este expediente?')) {
                window.location.href = 'eliminarExpediente.php?id=' + id;
            }
        }

        $(document).ready(function () {
            if ($.fn.select2) {
                $('.select2-auto').select2({
                    width: '100%',
                    placeholder: 'Buscar...',
                    allowClear: true
                });
            } else {
                console.warn("Select2 no está cargado.");
            }
        });
    </script>
</body>
</html>