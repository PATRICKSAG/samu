<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dSede.php');
include_once(__DIR__ . '/../persistencia/dEstablecimiento.php');
include_once(__DIR__ . '/../persistencia/dSituacionDigemid.php');
include_once(__DIR__ . '/../persistencia/dExpediente.php');

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$idExpediente = 0;
$idSede = '';
$numeroActa = '';
$fechaInspeccion = '';
$estadoExpediente = '';
$responsable = '';
$numeroFolios = '';
$observacion = '';
$judicializado = '';
$falsificado = 0;

$fechaDescargoActa = '';
$oficioOtorgaDeniegaPlazo = '';
$idSituacionDigemidSeleccionada = '';
$docElevaNulidad = '';
$resuelveNulidad = '';
$informeTecnicoInspeccion = '';
$nCertificadoBuenasPracticas = '';
$fechaInicioCertificadoBP = '';
$fechaFinCertificadoBP = '';
$rgrRatificaCierreTemporal = '';
$fechaNotificacionRGRCierre = '';
$descargoApelacion = '';
$nDocResuelveRecurso = '';
$rsgLevantamientoCierre = '';
$fechaNotificacionRSGLevantamiento = '';
$cierreDefinitivo = '';
$fechaNotificacionCierreDefinitivo = '';

$mensaje = '';
$mensajeError = '';
$esEdicion = false;

// Cargar datos si hay editar
$idEditar = isset($_GET['editar']) ? intval($_GET['editar']) : 0;
if ($idEditar > 0) {
    $expData = obtenerExpedienteCompleto($pdo, $idEditar);
    if ($expData) {
        $esEdicion = true;
        $idExpediente = intval($expData['idExpediente']);
        $idSede = $expData['idSede'];
        $numeroActa = $expData['numeroActa'];
        $fechaInspeccion = $expData['fechaInspeccion'];
        $estadoExpediente = $expData['estadoExpediente'];
        $responsable = $expData['responsable'] ?? '';
        $numeroFolios = $expData['numeroFolios'] ?? '';
        $observacion = $expData['observacion'] ?? '';
        $judicializado = $expData['judicializado'] ?? '';
        $falsificado = $expData['falsificado'] ?? 0;
        $fechaDescargoActa = $expData['fechaDescargoActa'] ?? '';
        $oficioOtorgaDeniegaPlazo = $expData['oficioOtorgaDeniegaPlazo'] ?? '';
        $idSituacionDigemidSeleccionada = $expData['idSituacionDigemidSeleccionada'] ?? '';
        $docElevaNulidad = $expData['docElevaNulidad'] ?? '';
        $resuelveNulidad = $expData['resuelveNulidad'] ?? '';
        $informeTecnicoInspeccion = $expData['informeTecnicoInspeccion'] ?? '';
        $nCertificadoBuenasPracticas = $expData['nCertificadoBuenasPracticas'] ?? '';
        $fechaInicioCertificadoBP = $expData['fechaInicioCertificadoBP'] ?? '';
        $fechaFinCertificadoBP = $expData['fechaFinCertificadoBP'] ?? '';
        $rgrRatificaCierreTemporal = $expData['rgrRatificaCierreTemporal'] ?? '';
        $fechaNotificacionRGRCierre = $expData['fechaNotificacionRGRCierre'] ?? '';
        $descargoApelacion = $expData['descargoApelacion'] ?? '';
        $nDocResuelveRecurso = $expData['nDocResuelveRecurso'] ?? '';
        $rsgLevantamientoCierre = $expData['rsgLevantamientoCierre'] ?? '';
        $fechaNotificacionRSGLevantamiento = $expData['fechaNotificacionRSGLevantamiento'] ?? '';
        $cierreDefinitivo = $expData['cierreDefinitivo'] ?? '';
        $fechaNotificacionCierreDefinitivo = $expData['fechaNotificacionCierreDefinitivo'] ?? '';
    }
}

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGuardar'])) {
    // Obtener ID de GET (editar) o de POST (campo oculto) como respaldo
    $idPost = isset($_GET['editar']) ? intval($_GET['editar']) : (isset($_POST['idExpediente']) ? intval($_POST['idExpediente']) : 0);

    $idSede = $_POST['idSede'] ?? '';
    $numeroActa = trim($_POST['numeroActa'] ?? '');
    $fechaInspeccion = $_POST['fechaInspeccion'] ?? '';
    $estadoExpediente = $_POST['estadoExpediente'] ?? '';
    $responsable = $_POST['responsable'] ?? '';
    $numeroFolios = $_POST['numeroFolios'] ?? '';
    $observacion = $_POST['observacion'] ?? '';
    $judicializado = $_POST['judicializado'] ?? '';
    $falsificado = isset($_POST['falsificado']) ? 1 : 0;
    $fechaDescargoActa = $_POST['fechaDescargoActa'] ?? '';
    $oficioOtorgaDeniegaPlazo = $_POST['oficioOtorgaDeniegaPlazo'] ?? '';
    $idSituacionDigemidSeleccionada = $_POST['idSituacionDigemidSeleccionada'] ?? '';
    $docElevaNulidad = $_POST['docElevaNulidad'] ?? '';
    $resuelveNulidad = $_POST['resuelveNulidad'] ?? '';
    $informeTecnicoInspeccion = $_POST['informeTecnicoInspeccion'] ?? '';
    $nCertificadoBuenasPracticas = $_POST['nCertificadoBuenasPracticas'] ?? '';
    $fechaInicioCertificadoBP = $_POST['fechaInicioCertificadoBP'] ?? '';
    $fechaFinCertificadoBP = $_POST['fechaFinCertificadoBP'] ?? '';
    $rgrRatificaCierreTemporal = $_POST['rgrRatificaCierreTemporal'] ?? '';
    $fechaNotificacionRGRCierre = $_POST['fechaNotificacionRGRCierre'] ?? '';
    $descargoApelacion = $_POST['descargoApelacion'] ?? '';
    $nDocResuelveRecurso = $_POST['nDocResuelveRecurso'] ?? '';
    $rsgLevantamientoCierre = $_POST['rsgLevantamientoCierre'] ?? '';
    $fechaNotificacionRSGLevantamiento = $_POST['fechaNotificacionRSGLevantamiento'] ?? '';
    $cierreDefinitivo = $_POST['cierreDefinitivo'] ?? '';
    $fechaNotificacionCierreDefinitivo = $_POST['fechaNotificacionCierreDefinitivo'] ?? '';

    $errores = [];
    if (empty($idSede)) $errores[] = "La sede es requerida.";
    if (empty($numeroActa)) $errores[] = "El número de acta es requerido.";
    if (empty($estadoExpediente)) $errores[] = "El estado del expediente es requerido.";

    if (empty($errores)) {
        $data = [
            'idSede' => $idSede,
            'numeroActa' => $numeroActa,
            'fechaInspeccion' => $fechaInspeccion,
            'estadoExpediente' => $estadoExpediente,
            'responsable' => $responsable,
            'numeroFolios' => $numeroFolios,
            'observacion' => $observacion,
            'judicializado' => $judicializado,
            'falsificado' => $falsificado,
            'fechaDescargoActa' => $fechaDescargoActa,
            'oficioOtorgaDeniegaPlazo' => $oficioOtorgaDeniegaPlazo,
            'idSituacionDigemidSeleccionada' => $idSituacionDigemidSeleccionada,
            'docElevaNulidad' => $docElevaNulidad,
            'resuelveNulidad' => $resuelveNulidad,
            'informeTecnicoInspeccion' => $informeTecnicoInspeccion,
            'nCertificadoBuenasPracticas' => $nCertificadoBuenasPracticas,
            'fechaInicioCertificadoBP' => $fechaInicioCertificadoBP,
            'fechaFinCertificadoBP' => $fechaFinCertificadoBP,
            'rgrRatificaCierreTemporal' => $rgrRatificaCierreTemporal,
            'fechaNotificacionRGRCierre' => $fechaNotificacionRGRCierre,
            'descargoApelacion' => $descargoApelacion,
            'nDocResuelveRecurso' => $nDocResuelveRecurso,
            'rsgLevantamientoCierre' => $rsgLevantamientoCierre,
            'fechaNotificacionRSGLevantamiento' => $fechaNotificacionRSGLevantamiento,
            'cierreDefinitivo' => $cierreDefinitivo,
            'fechaNotificacionCierreDefinitivo' => $fechaNotificacionCierreDefinitivo
        ];

        try {
            if ($idPost > 0) {
                $data['idExpediente'] = $idPost;
                actualizarExpediente($pdo, $data, 'UFRESA');
                $mensaje = "Expediente actualizado correctamente.";
            } else {
                $idNuevo = insertarExpediente($pdo, $data, 'UFRESA');
                $mensaje = "Expediente creado correctamente con ID: $idNuevo";
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?mensaje=" . urlencode($mensaje));
            exit;
        } catch (Exception $e) {
            $mensajeError = "Error al guardar: " . $e->getMessage();
        }
    } else {
        $mensajeError = implode("<br>", $errores);
    }
}

if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}

$sedes = listarSedes($pdo);
$situacionesDigemid = listarSituacionesDigemid($pdo);
$expedientes = listarExpedientesUFRESA($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Expedientes UFRESA</title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <?php include 'select2-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... mismos estilos ... */
        body { background-color: #f0f4fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-header { background: linear-gradient(135deg, #0b2a4a 0%, #1b4f8b 100%); color: white; padding: 30px 0 25px; border-radius: 0 0 40px 40px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .page-header h2 { font-weight: 700; margin: 0; }
        .page-header p { opacity: 0.85; margin: 5px 0 0; }
        .card-modern { border: none; border-radius: 24px; background: #ffffff; box-shadow: 0 8px 25px rgba(0,0,0,0.06); transition: transform 0.2s; }
        .card-modern:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .btn-primary-custom { background: #1b4f8b; border: none; border-radius: 50px; padding: 10px 30px; font-weight: 600; color: white; transition: 0.25s; }
        .btn-primary-custom:hover { background: #0f3b6b; transform: scale(1.02); }
        .btn-outline-secondary-custom { border: 2px solid #6c757d; color: #6c757d; border-radius: 50px; padding: 10px 30px; font-weight: 600; transition: 0.25s; background: transparent; }
        .btn-outline-secondary-custom:hover { background: #6c757d; color: white; }
        .form-control-modern { border-radius: 12px; border: 1px solid #dce3ed; padding: 10px 15px; transition: 0.2s; }
        .form-control-modern:focus { border-color: #1b4f8b; box-shadow: 0 0 0 3px rgba(27,79,139,0.15); }
        .form-label { font-weight: 500; color: #2c3e50; }
        .footer-custom { background: #0b2a4a; color: rgba(255,255,255,0.7); padding: 20px 0; border-radius: 40px 40px 0 0; margin-top: 40px; text-align: center; font-size: 0.9rem; }
        .footer-custom a { color: white; text-decoration: none; }
        .footer-custom a:hover { text-decoration: underline; }
        .icon-input { background: #e9f0fc; padding: 0 15px; border-radius: 12px 0 0 12px; display: flex; align-items: center; color: #1b4f8b; border: 1px solid #dce3ed; border-right: none; }
        .input-group-custom { display: flex; align-items: stretch; }
        .input-group-custom .form-control { border-radius: 0 12px 12px 0; border-left: none; }
        .select2-container--default .select2-selection--single { border-radius: 12px !important; border-color: #dce3ed !important; height: 44px !important; display: flex; align-items: center; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 42px !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px !important; }
        .table-modern { border-radius: 16px; overflow-x: auto !important; box-shadow: 0 5px 20px rgba(0,0,0,0.04); }
        .table-modern table { min-width: 1000px; width: 100%; margin-bottom: 0; }
        .table-modern thead { background: #0b2a4a; color: white; }
        .table-modern th { font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; white-space: nowrap; padding: 10px 6px; }
        .table-modern td { vertical-align: middle; padding: 8px 6px; }
        .badge-estado { padding: 4px 12px; border-radius: 30px; font-weight: 600; font-size: 0.7rem; }
        .badge-proceso { background: #ffc107; color: #212529; }
        .badge-cerrado { background: #28a745; color: white; }
        .badge-archivado { background: #6c757d; color: white; }
        .accordion-button:not(.collapsed) { background-color: #e7f1ff; color: #0b2a4a; }
        @media (max-width: 768px) { .page-header { padding: 20px 0; } .btn-primary-custom, .btn-outline-secondary-custom { width: 100%; margin-bottom: 5px; } }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Cabecera -->
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-file-alt me-2"></i>Expedientes UFRESA</h2>
            <p><i class="fas fa-plus-circle me-1"></i>Registro de expedientes del área UFRESA</p>
        </div>
    </div>

    <div class="container">
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($mensajeError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($mensajeError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card card-modern mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-pen-alt me-2"></i><?= $esEdicion ? 'Editar Expediente UFRESA' : 'Nuevo Expediente UFRESA' ?>
                </h5>
                <form method="POST" action="">
                    <input type="hidden" name="idExpediente" value="<?= $idExpediente ?>">

                    <!-- Sección 1: Datos Generales (sin tipo expediente ni código UFREMID) -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="idSede" class="form-label"><i class="fas fa-store me-1"></i>Sede <span class="text-danger">*</span></label>
                            <select name="idSede" id="idSede" class="form-select select2-auto" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($sedes as $sede): ?>
                                    <option value="<?= $sede['idSede'] ?>" <?= ($idSede == $sede['idSede']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sede['numeroEstacion'] . ' - ' . $sede['nombre'] . ' - ' . $sede['direccion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="numeroActa" class="form-label"><i class="fas fa-hashtag me-1"></i>N° de Acta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-modern" name="numeroActa" id="numeroActa" value="<?= htmlspecialchars($numeroActa ?? '') ?>" placeholder="Ingrese el número de acta" required>
                        </div>
                        <div class="col-md-4">
                            <label for="fechaInspeccion" class="form-label"><i class="fas fa-calendar-alt me-1"></i>Fecha de Inspección</label>
                            <input type="date" class="form-control form-control-modern" name="fechaInspeccion" id="fechaInspeccion" value="<?= $fechaInspeccion ?? '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="estadoExpediente" class="form-label"><i class="fas fa-info-circle me-1"></i>Estado del Expediente <span class="text-danger">*</span></label>
                            <select name="estadoExpediente" id="estadoExpediente" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="EN PROCESO" <?= ($estadoExpediente == 'EN PROCESO') ? 'selected' : '' ?>>EN PROCESO</option>
                                <option value="CERRADO" <?= ($estadoExpediente == 'CERRADO') ? 'selected' : '' ?>>CERRADO</option>
                                <option value="ARCHIVADO" <?= ($estadoExpediente == 'ARCHIVADO') ? 'selected' : '' ?>>ARCHIVADO</option>
                                <option value="ENVIADO AL EJECUTOR" <?= ($estadoExpediente == 'ENVIADO AL EJECUTOR') ? 'selected' : '' ?>>ENVIADO AL EJECUTOR</option>
                                <option value="OTRO" <?= ($estadoExpediente == 'OTRO') ? 'selected' : '' ?>>OTRO</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="divOtroEstado" style="<?= ($estadoExpediente == 'OTRO') ? '' : 'display:none;' ?>">
                            <label for="otroEstado" class="form-label">Especificar estado</label>
                            <input type="text" class="form-control form-control-modern" name="otroEstado" id="otroEstado" value="<?= htmlspecialchars($_POST['otroEstado'] ?? '') ?>" placeholder="Ingrese el estado">
                        </div>
                        <div class="col-md-6">
                            <label for="responsable" class="form-label"><i class="fas fa-user me-1"></i>Responsable</label>
                            <input type="text" class="form-control form-control-modern" name="responsable" id="responsable" value="<?= htmlspecialchars($responsable ?? '') ?>" placeholder="Nombre del responsable">
                        </div>
                        <div class="col-md-6">
                            <label for="numeroFolios" class="form-label"><i class="fas fa-file-alt me-1"></i>Número de Folios</label>
                            <input type="text" class="form-control form-control-modern" name="numeroFolios" id="numeroFolios" value="<?= htmlspecialchars($numeroFolios ?? '') ?>" placeholder="Cantidad de folios">
                        </div>
                        <div class="col-12">
                            <label for="observacion" class="form-label"><i class="fas fa-comment me-1"></i>Observaciones</label>
                            <textarea class="form-control form-control-modern" name="observacion" id="observacion" rows="2" placeholder="Observaciones generales"><?= htmlspecialchars($observacion ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="judicializado" class="form-label"><i class="fas fa-gavel me-1"></i>Judicializado</label>
                            <input type="text" class="form-control form-control-modern" name="judicializado" id="judicializado" value="<?= htmlspecialchars($judicializado ?? '') ?>" placeholder="Número o descripción">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="falsificado" id="falsificado" value="1" <?= $falsificado ? 'checked' : '' ?>>
                                <label class="form-check-label" for="falsificado">
                                    <i class="fas fa-exclamation-triangle me-1" style="color: #dc3545;"></i> ¿Es falsificado?
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 2: Medidas de Seguridad (MS) -->
                    <div class="accordion" id="accordionMS">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingMS">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMS" aria-expanded="false" aria-controls="collapseMS">
                                    <i class="fas fa-shield-alt me-2"></i> Medidas de Seguridad (MS) - Opcional
                                </button>
                            </h2>
                            <div id="collapseMS" class="accordion-collapse collapse" aria-labelledby="headingMS" data-bs-parent="#accordionMS">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="fechaDescargoActa" class="form-label">Descargo al Acta de Inspección (10 días) 
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="SOLICITUD DE AMPLIACION DE PLAZO, NULIDAD U OTROS"></i>
                                            </label>
                                            <input type="date" class="form-control form-control-modern" name="fechaDescargoActa" id="fechaDescargoActa" value="<?= $fechaDescargoActa ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="oficioOtorgaDeniegaPlazo" class="form-label">Oficio que otorga o deniega el plazo</label>
                                            <input type="text" class="form-control form-control-modern" name="oficioOtorgaDeniegaPlazo" id="oficioOtorgaDeniegaPlazo" value="<?= htmlspecialchars($oficioOtorgaDeniegaPlazo ?? '') ?>" placeholder="N° de oficio">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="idSituacionDigemidSeleccionada" class="form-label">Seleccionar Estado del Local</label>
                                            <select name="idSituacionDigemidSeleccionada" id="idSituacionDigemidSeleccionada" class="form-select">
                                                <option value="">-- Seleccionar --</option>
                                                <?php foreach ($situacionesDigemid as $sit): ?>
                                                    <option value="<?= $sit['idSituacionDigemid'] ?>" <?= ($idSituacionDigemidSeleccionada == $sit['idSituacionDigemid']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($sit['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Al guardar, se actualizará el estado de la sede seleccionada.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="docElevaNulidad" class="form-label">Doc. Eleva nulidad</label>
                                            <input type="text" class="form-control form-control-modern" name="docElevaNulidad" id="docElevaNulidad" value="<?= htmlspecialchars($docElevaNulidad ?? '') ?>" placeholder="N° de documento">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="resuelveNulidad" class="form-label">Resuelve nulidad</label>
                                            <input type="text" class="form-control form-control-modern" name="resuelveNulidad" id="resuelveNulidad" value="<?= htmlspecialchars($resuelveNulidad ?? '') ?>" placeholder="N° de resolución">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="informeTecnicoInspeccion" class="form-label">Informe técnico de Inspección</label>
                                            <input type="text" class="form-control form-control-modern" name="informeTecnicoInspeccion" id="informeTecnicoInspeccion" value="<?= htmlspecialchars($informeTecnicoInspeccion ?? '') ?>" placeholder="N° de informe">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nCertificadoBuenasPracticas" class="form-label">N° certificado buenas prácticas</label>
                                            <input type="text" class="form-control form-control-modern" name="nCertificadoBuenasPracticas" id="nCertificadoBuenasPracticas" value="<?= htmlspecialchars($nCertificadoBuenasPracticas ?? '') ?>" placeholder="N° de certificado">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaInicioCertificadoBP" class="form-label">Fecha Inicio Certificado B.P.
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="FECHA DE INICIO DE LA CERTIFICACIÓN BUENAS PRACTICAS"></i>
                                            </label>
                                            <input type="date" class="form-control form-control-modern" name="fechaInicioCertificadoBP" id="fechaInicioCertificadoBP" value="<?= $fechaInicioCertificadoBP ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaFinCertificadoBP" class="form-label">Fecha Fin Certificado B.P.
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="FECHA DE TERMINO DE LA CERTIFICACIÓN BUENAS PRACTICAS"></i>
                                            </label>
                                            <input type="date" class="form-control form-control-modern" name="fechaFinCertificadoBP" id="fechaFinCertificadoBP" value="<?= $fechaFinCertificadoBP ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rgrRatificaCierreTemporal" class="form-label">RGR. Ratifica Medida de Cierre Temporal</label>
                                            <input type="text" class="form-control form-control-modern" name="rgrRatificaCierreTemporal" id="rgrRatificaCierreTemporal" value="<?= htmlspecialchars($rgrRatificaCierreTemporal ?? '') ?>" placeholder="Ej. RGR. N° 0300-2018">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaNotificacionRGRCierre" class="form-label">Fecha de Notificación de la RGR. de Cierre temporal</label>
                                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionRGRCierre" id="fechaNotificacionRGRCierre" value="<?= $fechaNotificacionRGRCierre ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="descargoApelacion" class="form-label">Descargo y/o apelación</label>
                                            <input type="text" class="form-control form-control-modern" name="descargoApelacion" id="descargoApelacion" value="<?= htmlspecialchars($descargoApelacion ?? '') ?>" placeholder="Descripción o número">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nDocResuelveRecurso" class="form-label">N° Doc resuelve recurso</label>
                                            <input type="text" class="form-control form-control-modern" name="nDocResuelveRecurso" id="nDocResuelveRecurso" value="<?= htmlspecialchars($nDocResuelveRecurso ?? '') ?>" placeholder="N° de documento">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rsgLevantamientoCierre" class="form-label">RSG. de Levantamiento de cierre</label>
                                            <input type="text" class="form-control form-control-modern" name="rsgLevantamientoCierre" id="rsgLevantamientoCierre" value="<?= htmlspecialchars($rsgLevantamientoCierre ?? '') ?>" placeholder="Ej. RSG N° 200-2026">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaNotificacionRSGLevantamiento" class="form-label">Fecha de Notificación RSG. Levantamiento de cierre</label>
                                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionRSGLevantamiento" id="fechaNotificacionRSGLevantamiento" value="<?= $fechaNotificacionRSGLevantamiento ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cierreDefinitivo" class="form-label">Cierre definitivo</label>
                                            <input type="text" class="form-control form-control-modern" name="cierreDefinitivo" id="cierreDefinitivo" value="<?= htmlspecialchars($cierreDefinitivo ?? '') ?>" placeholder="Ej. RSG N 056-2026">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaNotificacionCierreDefinitivo" class="form-label">Fecha de notificación del cierre de envío</label>
                                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionCierreDefinitivo" id="fechaNotificacionCierreDefinitivo" value="<?= $fechaNotificacionCierreDefinitivo ?? '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button type="submit" name="btnGuardar" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i><?= $esEdicion ? 'Actualizar Expediente' : 'Guardar Expediente' ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary-custom" onclick="cancelar();">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado de expedientes -->
        <div class="card card-modern">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <h5 class="card-title fw-bold" style="color: #0b2a4a;">
                        <i class="fas fa-list me-2"></i>Expedientes UFRESA Registrados
                    </h5>
                </div>
                <div class="table-responsive table-modern">
                    <table id="tablaExpedientes" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Acta</th>
                                <th>Sede</th>
                                <th>Fecha Inspección</th>
                                <th>Estado</th>
                                <th>Responsable</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expedientes as $exp): ?>
                            <tr>
                                <td><?= $exp['idExpediente'] ?></td>
                                <td><?= htmlspecialchars($exp['numeroActa'] ?? '') ?></td>
                                <td><?= htmlspecialchars($exp['nombreSede'] ?? '') ?></td>
                                <td><?= $exp['fechaInspeccion'] ?? '' ?></td>
                                <td>
                                    <?php
                                    $estado = $exp['estadoExpediente'] ?? '';
                                    $badgeClass = 'badge-proceso';
                                    if ($estado == 'CERRADO') $badgeClass = 'badge-cerrado';
                                    elseif ($estado == 'ARCHIVADO') $badgeClass = 'badge-archivado';
                                    ?>
                                    <span class="badge-estado <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                                </td>
                                <td><?= htmlspecialchars($exp['responsable'] ?? '') ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cogs"></i> Acciones
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="formExpedienteFI.php?idExpediente=<?= $exp['idExpediente'] ?>"><i class="fas fa-gavel me-2"></i>Fase Instructora (FI)</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="?editar=<?= $exp['idExpediente'] ?>"><i class="fas fa-edit me-2"></i>Editar Expediente</a></li>
                                            <li><a class="dropdown-item text-danger" href="eliminarExpediente.php?id=<?= $exp['idExpediente'] ?>&area=UFRESA" onclick="return confirm('¿Está seguro de eliminar este expediente?')"><i class="fas fa-trash-alt me-2"></i>Eliminar</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Sub Gerencia de Regulación Sectorial - Todos los derechos reservados.</p>
        </div>
    </footer>

    <?php include 'boostrap-js.php'; ?>
    <?php include 'datatable-js.php'; ?>
    <?php include 'select2-js.php'; ?>

    <script>
        $(document).ready(function() {
            $('#tablaExpedientes').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true,
                order: [[0, 'desc']]
            });
            if ($.fn.select2) {
                $('.select2-auto').select2({
                    width: '100%',
                    placeholder: 'Buscar...',
                    allowClear: true
                });
            }
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    trigger: 'hover',
                    placement: 'top'
                });
            });
            $('#estadoExpediente').change(function() {
                if ($(this).val() === 'OTRO') {
                    $('#divOtroEstado').show();
                } else {
                    $('#divOtroEstado').hide();
                }
            });
        });
        function cancelar() {
            window.location.href = '<?php echo $_SERVER['PHP_SELF'] ?>';
        }
    </script>
</body>
</html>