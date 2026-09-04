<?php
include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../persistencia/conexion.php';
include_once __DIR__ . '/../persistencia/dSede.php';
include_once __DIR__ . '/../persistencia/dEstablecimiento.php';
include_once __DIR__ . '/../persistencia/dSituacionDigemid.php';
include_once __DIR__ . '/../persistencia/dTipoExpediente.php';
include_once __DIR__ . '/../persistencia/dExpediente.php';

// VERIFICACIÓN DE SESIÓN
include_once(__DIR__ . '/auth_check.php');

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Inicializar variables
$idExpediente     = 0;
$idSede           = '';
$numeroActa       = '';
$fechaInspeccion  = '';
$estadoExpediente = '';
$idTipoExpediente = '';
$codigoUFREMID    = '';
$responsable      = '';
$numeroFolios     = '';
$observacion      = '';
$judicializado    = '';
$falsificado      = 0;

// NUEVOS CAMPOS (sección principal)
$idEquipoDCVS         = '';
$idActividadAbrev     = '';
$idTipoActividad      = '';
$condicionEjecucion   = '';

// MS
$fechaDescargoActa                 = '';
$oficioOtorgaDeniegaPlazo          = '';
$idSituacionDigemidSeleccionada    = '';
$docElevaNulidad                   = '';
$resuelveNulidad                   = '';
$informeTecnicoInspeccion          = '';
$nCertificadoBuenasPracticas       = '';
$fechaInicioCertificadoBP          = '';
$fechaFinCertificadoBP             = '';
$rgrRatificaCierreTemporal         = '';
$fechaNotificacionRGRCierre        = '';
$descargoApelacion                 = '';
$nDocResuelveRecurso               = '';
$rsgLevantamientoCierre            = '';
$fechaNotificacionRSGLevantamiento = '';
$cierreDefinitivo                  = '';
$fechaNotificacionCierreDefinitivo = '';
$fechaEnvioFiscalia = '';
$nroDocumentoFiscalia = '';

// NUEVOS CAMPOS DIGEMID (acordeón)
$atendidoPor               = '';
$horarioAtencionQF         = '';
$permanenciaQF             = '';
$cumplimientoBPOF          = '';
$cumplimientoBPA           = '';
$cumplimientoBPDyT         = '';
$cumplimientoBPF           = '';
$productosIncautados       = '';
$bpd                       = '';
$bpa                       = '';
$bpf                       = '';
$bpsf                      = '';
$bpdt                      = '';
$medidaSeguridad           = '';

$mensaje      = '';
$mensajeError = '';
$esEdicion    = false;

// Detectar edición
$idEditar = isset($_GET['editar']) ? intval($_GET['editar']) : 0;
if ($idEditar > 0) {
    $expData = obtenerExpedienteCompleto($pdo, $idEditar);
    if ($expData) {
        $esEdicion        = true;
        $idExpediente     = intval($expData['idExpediente']);
        $idSede           = $expData['idSede'];
        $numeroActa       = $expData['numeroActa'];
        $fechaInspeccion  = $expData['fechaInspeccion'];
        $estadoExpediente = $expData['estadoExpediente'];
        $idTipoExpediente = $expData['idTipoExpediente'] ?? '';
        $codigoUFREMID    = $expData['codigoUfremid'] ?? '';
        $responsable      = $expData['responsable'] ?? '';
        $numeroFolios     = $expData['numeroFolios'] ?? '';
        $observacion      = $expData['observacion'] ?? '';
        $judicializado    = $expData['judicializado'] ?? '';
        $falsificado      = $expData['falsificado'] ?? 0;
        // NUEVOS CAMPOS
        $idEquipoDCVS       = $expData['idEquipoDCVS'] ?? '';
        $idActividadAbrev   = $expData['idActividadAbrev'] ?? '';
        $idTipoActividad    = $expData['idTipoActividad'] ?? '';
        $condicionEjecucion = $expData['condicionEjecucion'] ?? '';
        // MS
        $fechaDescargoActa                 = $expData['fechaDescargoActa'] ?? '';
        $oficioOtorgaDeniegaPlazo          = $expData['oficioOtorgaDeniegaPlazo'] ?? '';
        $idSituacionDigemidSeleccionada    = $expData['idSituacionDigemidSeleccionada'] ?? '';
        $docElevaNulidad                   = $expData['docElevaNulidad'] ?? '';
        $resuelveNulidad                   = $expData['resuelveNulidad'] ?? '';
        $informeTecnicoInspeccion          = $expData['informeTecnicoInspeccion'] ?? '';
        $nCertificadoBuenasPracticas       = $expData['nCertificadoBuenasPracticas'] ?? '';
        $fechaInicioCertificadoBP          = $expData['fechaInicioCertificadoBP'] ?? '';
        $fechaFinCertificadoBP             = $expData['fechaFinCertificadoBP'] ?? '';
        $rgrRatificaCierreTemporal         = $expData['rgrRatificaCierreTemporal'] ?? '';
        $fechaNotificacionRGRCierre        = $expData['fechaNotificacionRGRCierre'] ?? '';
        $descargoApelacion                 = $expData['descargoApelacion'] ?? '';
        $nDocResuelveRecurso               = $expData['nDocResuelveRecurso'] ?? '';
        $rsgLevantamientoCierre            = $expData['rsgLevantamientoCierre'] ?? '';
        $fechaNotificacionRSGLevantamiento = $expData['fechaNotificacionRSGLevantamiento'] ?? '';
        $cierreDefinitivo                  = $expData['cierreDefinitivo'] ?? '';
        $fechaNotificacionCierreDefinitivo = $expData['fechaNotificacionCierreDefinitivo'] ?? '';
        $fechaEnvioFiscalia                = $expData['fechaEnvioFiscalia'] ?? '';
        $nroDocumentoFiscalia              = $expData['nroDocumentoFiscalia'] ?? '';
        // DIGEMID
        $atendidoPor         = $expData['atendidoPor'] ?? '';
        $horarioAtencionQF   = $expData['horarioAtencionQF'] ?? '';
        $permanenciaQF       = $expData['permanenciaQF'] ?? '';
        $cumplimientoBPOF    = $expData['cumplimientoBPOF'] ?? '';
        $cumplimientoBPA     = $expData['cumplimientoBPA'] ?? '';
        $cumplimientoBPDyT   = $expData['cumplimientoBPDyT'] ?? '';
        $cumplimientoBPF     = $expData['cumplimientoBPF'] ?? '';
        $productosIncautados = $expData['productosIncautados'] ?? '';
        $bpd                 = $expData['bpd'] ?? '';
        $bpa                 = $expData['bpa'] ?? '';
        $bpf                 = $expData['bpf'] ?? '';
        $bpsf                = $expData['bpsf'] ?? '';
        $bpdt                = $expData['bpdt'] ?? '';
        $medidaSeguridad     = $expData['medidaSeguridad'] ?? '';
    }
}

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGuardar'])) {
    $idPost = isset($_GET['editar']) ? intval($_GET['editar']) : (isset($_POST['idExpediente']) ? intval($_POST['idExpediente']) : 0);

    // Recoger datos generales
    $idSede          = $_POST['idSede'] ?? '';
    $numeroActa      = trim($_POST['numeroActa'] ?? '');
    $fechaInspeccion = $_POST['fechaInspeccion'] ?? '';
    $estadoExpediente = $_POST['estadoExpediente'] ?? '';
    $idTipoExpediente = $_POST['idTipoExpediente'] ?? '';
    $codigoUFREMID   = $_POST['codigoUFREMID'] ?? '';
    $responsable     = $_POST['responsable'] ?? '';
    $numeroFolios    = $_POST['numeroFolios'] ?? '';
    $observacion     = $_POST['observacion'] ?? '';
    $judicializado   = $_POST['judicializado'] ?? '';
    $falsificado     = isset($_POST['falsificado']) ? 1 : 0;
    // Nuevos campos principales
    $idEquipoDCVS       = $_POST['idEquipoDCVS'] ?? '';
    $idActividadAbrev   = $_POST['idActividadAbrev'] ?? '';
    $idTipoActividad    = $_POST['idTipoActividad'] ?? '';
    $condicionEjecucion = $_POST['condicionEjecucion'] ?? '';
    // MS
    $fechaDescargoActa                 = $_POST['fechaDescargoActa'] ?? '';
    $oficioOtorgaDeniegaPlazo          = $_POST['oficioOtorgaDeniegaPlazo'] ?? '';
    $idSituacionDigemidSeleccionada    = $_POST['idSituacionDigemidSeleccionada'] ?? '';
    $docElevaNulidad                   = $_POST['docElevaNulidad'] ?? '';
    $resuelveNulidad                   = $_POST['resuelveNulidad'] ?? '';
    $informeTecnicoInspeccion          = $_POST['informeTecnicoInspeccion'] ?? '';
    $nCertificadoBuenasPracticas       = $_POST['nCertificadoBuenasPracticas'] ?? '';
    $fechaInicioCertificadoBP          = $_POST['fechaInicioCertificadoBP'] ?? '';
    $fechaFinCertificadoBP             = $_POST['fechaFinCertificadoBP'] ?? '';
    $rgrRatificaCierreTemporal         = $_POST['rgrRatificaCierreTemporal'] ?? '';
    $fechaNotificacionRGRCierre        = $_POST['fechaNotificacionRGRCierre'] ?? '';
    $descargoApelacion                 = $_POST['descargoApelacion'] ?? '';
    $nDocResuelveRecurso               = $_POST['nDocResuelveRecurso'] ?? '';
    $rsgLevantamientoCierre            = $_POST['rsgLevantamientoCierre'] ?? '';
    $fechaNotificacionRSGLevantamiento = $_POST['fechaNotificacionRSGLevantamiento'] ?? '';
    $cierreDefinitivo                  = $_POST['cierreDefinitivo'] ?? '';
    $fechaNotificacionCierreDefinitivo = $_POST['fechaNotificacionCierreDefinitivo'] ?? '';
    $fechaEnvioFiscalia                = $_POST['fechaEnvioFiscalia'] ?? '';
    $nroDocumentoFiscalia              = trim($_POST['nroDocumentoFiscalia'] ?? '');
    // Digemid
    $atendidoPor         = $_POST['atendidoPor'] ?? '';
    $horarioAtencionQF   = $_POST['horarioAtencionQF'] ?? '';
    $permanenciaQF       = $_POST['permanenciaQF'] ?? '';
    $cumplimientoBPOF    = $_POST['cumplimientoBPOF'] ?? '';
    $cumplimientoBPA     = $_POST['cumplimientoBPA'] ?? '';
    $cumplimientoBPDyT   = $_POST['cumplimientoBPDyT'] ?? '';
    $cumplimientoBPF     = $_POST['cumplimientoBPF'] ?? '';
    $productosIncautados = $_POST['productosIncautados'] ?? '';
    $bpd                 = $_POST['bpd'] ?? '';
    $bpa                 = $_POST['bpa'] ?? '';
    $bpf                 = $_POST['bpf'] ?? '';
    $bpsf                = $_POST['bpsf'] ?? '';
    $bpdt                = $_POST['bpdt'] ?? '';
    $medidaSeguridad     = $_POST['medidaSeguridad'] ?? '';

    // ============================================================
    // CONVERTIR CAMPOS VACÍOS A NULL (para evitar conflictos de FK)
    // ============================================================
    // Campos numéricos (IDs)
    $idTipoExpediente = ($idTipoExpediente === '' || $idTipoExpediente === null) ? null : (int)$idTipoExpediente;
    $idEquipoDCVS = ($idEquipoDCVS === '' || $idEquipoDCVS === null) ? null : (int)$idEquipoDCVS;
    $idActividadAbrev = ($idActividadAbrev === '' || $idActividadAbrev === null) ? null : (int)$idActividadAbrev;
    $idTipoActividad = ($idTipoActividad === '' || $idTipoActividad === null) ? null : (int)$idTipoActividad;
    $idSituacionDigemidSeleccionada = ($idSituacionDigemidSeleccionada === '' || $idSituacionDigemidSeleccionada === null) ? null : (int)$idSituacionDigemidSeleccionada;

    // Fechas vacías a NULL
    $fechaInspeccion = empty($fechaInspeccion) ? null : $fechaInspeccion;
    $fechaDescargoActa = empty($fechaDescargoActa) ? null : $fechaDescargoActa;
    $fechaInicioCertificadoBP = empty($fechaInicioCertificadoBP) ? null : $fechaInicioCertificadoBP;
    $fechaFinCertificadoBP = empty($fechaFinCertificadoBP) ? null : $fechaFinCertificadoBP;
    $fechaNotificacionRGRCierre = empty($fechaNotificacionRGRCierre) ? null : $fechaNotificacionRGRCierre;
    $fechaNotificacionRSGLevantamiento = empty($fechaNotificacionRSGLevantamiento) ? null : $fechaNotificacionRSGLevantamiento;
    $fechaNotificacionCierreDefinitivo = empty($fechaNotificacionCierreDefinitivo) ? null : $fechaNotificacionCierreDefinitivo;


    $errores = [];
    if (empty($idSede)) $errores[] = "La sede es requerida.";
    if (empty($numeroActa)) $errores[] = "El número de acta es requerido.";
    if (empty($estadoExpediente)) $errores[] = "El estado del expediente es requerido.";
    // Nuevos campos obligatorios
    if (empty($idEquipoDCVS)) $errores[] = "El equipo DCVS es requerido.";
    if (empty($idActividadAbrev)) $errores[] = "La actividad abreviada es requerida.";
    if (empty($idTipoActividad)) $errores[] = "El tipo de actividad es requerido.";
    if (empty($condicionEjecucion)) $errores[] = "La condición de ejecución es requerida.";

    if (empty($errores)) {
        $data = [
            'idSede'             => $idSede,
            'numeroActa'         => $numeroActa,
            'fechaInspeccion'    => $fechaInspeccion,
            'estadoExpediente'   => $estadoExpediente,
            'idTipoExpediente'   => $idTipoExpediente,
            'codigoUFREMID'      => $codigoUFREMID,
            'responsable'        => $responsable,
            'numeroFolios'       => $numeroFolios,
            'observacion'        => $observacion,
            'judicializado'      => $judicializado,
            'falsificado'        => $falsificado,
            // Nuevos
            'idEquipoDCVS'       => $idEquipoDCVS,
            'idActividadAbrev'   => $idActividadAbrev,
            'idTipoActividad'    => $idTipoActividad,
            'condicionEjecucion' => $condicionEjecucion,
            // MS
            'fechaDescargoActa'                 => $fechaDescargoActa,
            'oficioOtorgaDeniegaPlazo'          => $oficioOtorgaDeniegaPlazo,
            'idSituacionDigemidSeleccionada'    => $idSituacionDigemidSeleccionada,
            'docElevaNulidad'                   => $docElevaNulidad,
            'resuelveNulidad'                   => $resuelveNulidad,
            'informeTecnicoInspeccion'          => $informeTecnicoInspeccion,
            'nCertificadoBuenasPracticas'       => $nCertificadoBuenasPracticas,
            'fechaInicioCertificadoBP'          => $fechaInicioCertificadoBP,
            'fechaFinCertificadoBP'             => $fechaFinCertificadoBP,
            'rgrRatificaCierreTemporal'         => $rgrRatificaCierreTemporal,
            'fechaNotificacionRGRCierre'        => $fechaNotificacionRGRCierre,
            'descargoApelacion'                 => $descargoApelacion,
            'nDocResuelveRecurso'               => $nDocResuelveRecurso,
            'rsgLevantamientoCierre'            => $rsgLevantamientoCierre,
            'fechaNotificacionRSGLevantamiento' => $fechaNotificacionRSGLevantamiento,
            'cierreDefinitivo'                  => $cierreDefinitivo,
            'fechaNotificacionCierreDefinitivo' => $fechaNotificacionCierreDefinitivo,
            'fechaEnvioFiscalia'                => $fechaEnvioFiscalia,
            'nroDocumentoFiscalia'              => $nroDocumentoFiscalia,

            // Digemid
            'atendidoPor'         => $atendidoPor,
            'horarioAtencionQF'   => $horarioAtencionQF,
            'permanenciaQF'       => $permanenciaQF,
            'cumplimientoBPOF'    => $cumplimientoBPOF,
            'cumplimientoBPA'     => $cumplimientoBPA,
            'cumplimientoBPDyT'   => $cumplimientoBPDyT,
            'cumplimientoBPF'     => $cumplimientoBPF,
            'productosIncautados' => $productosIncautados,
            'bpd'                 => $bpd,
            'bpa'                 => $bpa,
            'bpf'                 => $bpf,
            'bpsf'                => $bpsf,
            'bpdt'                => $bpdt,
            'medidaSeguridad'     => $medidaSeguridad
        ];

        try {
            if ($idPost > 0) {
                $data['idExpediente'] = $idPost;
                actualizarExpediente($pdo, $data, 'UFREMID');
                $mensaje = "Expediente actualizado correctamente.";
            } else {
                $idNuevo = insertarExpediente($pdo, $data, 'UFREMID');
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

// Mensaje GET
if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}

// Obtener listas
$sedes              = listarSedes($pdo);
$tiposExpediente    = listarTiposExpediente($pdo);
$situacionesDigemid = listarSituacionesDigemid($pdo);
$expedientes        = listarExpedientesUFREMID($pdo);
$equiposDCVS        = listarEquiposDCVS($pdo);
$actividades        = []; // se cargarán vía AJAX
$tiposActividad     = []; // se cargarán vía AJAX
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Expedientes UFREMID</title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <?php include 'select2-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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

        /* MEJORA VISUAL DE ACORDEONES */
        .accordion-item {
            border: 2px solid #1b4f8b !important;
            border-radius: 12px !important;
            margin-bottom: 12px !important;
            overflow: hidden;
        }
        .accordion-item:has(.accordion-button:not(.collapsed)) {
            border-color: #0b2a4a !important;
            box-shadow: 0 0 0 4px rgba(27, 79, 139, 0.15);
        }
        .accordion-button {
            background: #eaf3ff !important;
            font-weight: 600 !important;
            color: #0b2a4a !important;
            padding: 14px 20px !important;
        }
        .accordion-button:not(.collapsed) {
            background: #1b4f8b !important;
            color: white !important;
        }
        .accordion-button:not(.collapsed) i {
            color: white !important;
        }
        .accordion-button:not(.collapsed)::after {
            filter: brightness(0) invert(1);
        }
        .accordion-button:focus {
            box-shadow: none !important;
            border-color: #1b4f8b !important;
        }
        .accordion-body {
            background: #f8faff !important;
            border-top: 1px solid #dce3ed;
            padding: 20px !important;
        }
        /* Subcampos BPOF */
        .subcampos-bpof {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #1b4f8b;
            margin-top: 10px;
            display: none;
        }

        @media (max-width: 768px) { .page-header { padding: 20px 0; } .btn-primary-custom, .btn-outline-secondary-custom { width: 100%; margin-bottom: 5px; } }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Cabecera -->
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-file-alt me-2"></i>Expedientes UFREMID</h2>
            <p><i class="fas fa-plus-circle me-1"></i>Registro de expedientes del área UFREMID</p>
        </div>
    </div>

    <div class="container">
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($mensajeError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($mensajeError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card card-modern mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-pen-alt me-2"></i><?php echo $esEdicion ? 'Editar Expediente UFREMID' : 'Nuevo Expediente UFREMID' ?>
                </h5>
                <form method="POST" action="">
                    <input type="hidden" name="idExpediente" value="<?php echo $idExpediente ?>">

                    <!-- Sección 1: Datos Generales -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="idSede" class="form-label"><i class="fas fa-store me-1"></i>Sede <span class="text-danger">*</span></label>
                            <select name="idSede" id="idSede" class="form-select select2-auto" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($sedes as $sede): ?>
                                    <option value="<?php echo $sede['idSede'] ?>" <?php echo ($idSede == $sede['idSede']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($sede['numeroEstacion'] . ' - ' . $sede['nombre'] . ' - ' . $sede['direccion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="numeroActa" class="form-label"><i class="fas fa-hashtag me-1"></i>N° de Acta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-modern" name="numeroActa" id="numeroActa" value="<?php echo htmlspecialchars($numeroActa ?? '') ?>" placeholder="Ingrese el número de acta" required>
                        </div>
                        <div class="col-md-4">
                            <label for="fechaInspeccion" class="form-label"><i class="fas fa-calendar-alt me-1"></i>Fecha de Inspección <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-modern" name="fechaInspeccion" id="fechaInspeccion" value="<?php echo $fechaInspeccion ?? '' ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="estadoExpediente" class="form-label"><i class="fas fa-info-circle me-1"></i>Estado del Expediente <span class="text-danger">*</span></label>
                            <select name="estadoExpediente" id="estadoExpediente" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="EN PROCESO" <?php echo ($estadoExpediente == 'EN PROCESO') ? 'selected' : '' ?>>EN PROCESO</option>
                                <option value="CERRADO" <?php echo ($estadoExpediente == 'CERRADO') ? 'selected' : '' ?>>CERRADO</option>
                                <option value="ARCHIVADO" <?php echo ($estadoExpediente == 'ARCHIVADO') ? 'selected' : '' ?>>ARCHIVADO</option>
                                <option value="ENVIADO AL EJECUTOR" <?php echo ($estadoExpediente == 'ENVIADO AL EJECUTOR') ? 'selected' : '' ?>>ENVIADO AL EJECUTOR</option>
                                <option value="ENVIADO A FISCALIA" <?php echo ($estadoExpediente == 'ENVIADO A FISCALIA') ? 'selected' : '' ?>>ENVIADO A FISCALIA</option>
                                <option value="PROCESO CONCLUIDO" <?php echo ($estadoExpediente == 'PROCESO CONCLUIDO') ? 'selected' : '' ?>>PROCESO CONCLUIDO</option>
                                <option value="OTRO" <?php echo ($estadoExpediente == 'OTRO') ? 'selected' : '' ?>>OTRO</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="divOtroEstado" style="<?php echo ($estadoExpediente == 'OTRO') ? '' : 'display:none;' ?>">
                            <label for="otroEstado" class="form-label">Especificar estado</label>
                            <input type="text" class="form-control form-control-modern" name="otroEstado" id="otroEstado" value="<?php echo htmlspecialchars($_POST['otroEstado'] ?? '') ?>" placeholder="Ingrese el estado">
                        </div>
                        <div class="col-md-6">
                            <label for="idTipoExpediente" class="form-label"><i class="fas fa-tag me-1"></i>Tipo Expediente UFREMID <span class="text-danger">*</span></label>
                            <select name="idTipoExpediente" id="idTipoExpediente" class="form-select select2-auto" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($tiposExpediente as $tipo): ?>
                                    <option value="<?php echo $tipo['idTipoExpediente'] ?>" <?php echo ($idTipoExpediente == $tipo['idTipoExpediente']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($tipo['nombre'] . ' - ' . $tipo['descripcion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="codigoUFREMID" class="form-label"><i class="fas fa-barcode me-1"></i>Código UFREMID</label>
                            <input type="text" class="form-control form-control-modern" name="codigoUFREMID" id="codigoUFREMID" value="<?php echo htmlspecialchars($codigoUFREMID ?? '') ?>" placeholder="Código interno UFREMID">
                        </div>
                        <div class="col-md-6">
                            <label for="responsable" class="form-label"><i class="fas fa-user me-1"></i>Inspector</label>
                            <input type="text" class="form-control form-control-modern" name="responsable" id="responsable" value="<?php echo htmlspecialchars($responsable ?? '') ?>" placeholder="Nombre del inspector">
                        </div>
                        <div class="col-md-6">
                            <label for="numeroFolios" class="form-label"><i class="fas fa-file-alt me-1"></i>Número de Folios</label>
                            <input type="text" class="form-control form-control-modern" name="numeroFolios" id="numeroFolios" value="<?php echo htmlspecialchars($numeroFolios ?? '') ?>" placeholder="Cantidad de folios">
                        </div>
                        <div class="col-12">
                            <label for="observacion" class="form-label"><i class="fas fa-comment me-1"></i>Observaciones</label>
                            <textarea class="form-control form-control-modern" name="observacion" id="observacion" rows="2" placeholder="Observaciones generales"><?php echo htmlspecialchars($observacion ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="judicializado" class="form-label"><i class="fas fa-gavel me-1"></i>Judicializado</label>
                            <input type="text" class="form-control form-control-modern" name="judicializado" id="judicializado" value="<?php echo htmlspecialchars($judicializado ?? '') ?>" placeholder="Número o descripción">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="falsificado" id="falsificado" value="1" <?php echo $falsificado ? 'checked' : '' ?>>
                                <label class="form-check-label" for="falsificado">
                                    <i class="fas fa-exclamation-triangle me-1" style="color: #dc3545;"></i> ¿Es falsificado?
                                </label>
                            </div>
                        </div>

                        <!-- NUEVOS CAMPOS OBLIGATORIOS (Equipo DCVS, Actividad, Tipo, Condición) -->
                        <div class="col-md-3">
                            <label for="idEquipoDCVS" class="form-label"><i class="fas fa-users me-1"></i>Equipo DCVS <span class="text-danger">*</span></label>
                            <select name="idEquipoDCVS" id="idEquipoDCVS" class="form-select select2-auto" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($equiposDCVS as $eq): ?>
                                    <option value="<?php echo $eq['idEquipo'] ?>" <?php echo ($idEquipoDCVS == $eq['idEquipo']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($eq['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="idActividadAbrev" class="form-label"><i class="fas fa-tasks me-1"></i>Actividad Abrev. <span class="text-danger">*</span></label>
                            <select name="idActividadAbrev" id="idActividadAbrev" class="form-select select2-auto" required>
                                <option value="">Primero seleccione Equipo DCVS</option>
                                <!-- Se llenará vía AJAX -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="idTipoActividad" class="form-label"><i class="fas fa-tag me-1"></i>Tipo de Actividad <span class="text-danger">*</span></label>
                            <select name="idTipoActividad" id="idTipoActividad" class="form-select select2-auto" required>
                                <option value="">Primero seleccione Actividad</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="condicionEjecucion" class="form-label"><i class="fas fa-check-circle me-1"></i>Condición Ejecución <span class="text-danger">*</span></label>
                            <select name="condicionEjecucion" id="condicionEjecucion" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="Efectiva" <?php echo ($condicionEjecucion == 'Efectiva') ? 'selected' : '' ?>>Efectiva</option>
                                <option value="No Efectiva" <?php echo ($condicionEjecucion == 'No Efectiva') ? 'selected' : '' ?>>No Efectiva</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- NUEVO ACORDEÓN: Datos de Digemid (antes de MS) -->
                    <div class="accordion" id="accordionDigemid">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingDigemid">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDigemid" aria-expanded="false" aria-controls="collapseDigemid">
                                    <i class="fas fa-hospital me-2"></i> Datos de DIGEMID
                                </button>
                            </h2>
                            <div id="collapseDigemid" class="accordion-collapse collapse" aria-labelledby="headingDigemid" data-bs-parent="#accordionDigemid">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="atendidoPor" class="form-label">Atendido por</label>
                                            <select name="atendidoPor" id="atendidoPor" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="Director Técnico" <?php echo ($atendidoPor == 'Director Técnico') ? 'selected' : '' ?>>Director Técnico</option>
                                                <option value="Q.F. Asistente registrado" <?php echo ($atendidoPor == 'Q.F. Asistente registrado') ? 'selected' : '' ?>>Q.F. Asistente registrado</option>
                                                <option value="Otros" <?php echo ($atendidoPor == 'Otros') ? 'selected' : '' ?>>Otros</option>
                                                <option value="N.A." <?php echo ($atendidoPor == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="horarioAtencionQF" class="form-label">Horario Atención Cubierto Por Q.F.</label>
                                            <select name="horarioAtencionQF" id="horarioAtencionQF" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="SI" <?php echo ($horarioAtencionQF == 'SI') ? 'selected' : '' ?>>SI</option>
                                                <option value="NO" <?php echo ($horarioAtencionQF == 'NO') ? 'selected' : '' ?>>NO</option>
                                                <option value="N.A." <?php echo ($horarioAtencionQF == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="permanenciaQF" class="form-label">Permanencia Q.F. (Durante Inspec.)</label>
                                            <select name="permanenciaQF" id="permanenciaQF" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="SI" <?php echo ($permanenciaQF == 'SI') ? 'selected' : '' ?>>SI</option>
                                                <option value="NO" <?php echo ($permanenciaQF == 'NO') ? 'selected' : '' ?>>NO</option>
                                                <option value="N.A." <?php echo ($permanenciaQF == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cumplimientoBPOF" class="form-label">Cumplimiento BPOF</label>
                                            <select name="cumplimientoBPOF" id="cumplimientoBPOF" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="SI" <?php echo ($cumplimientoBPOF == 'SI') ? 'selected' : '' ?>>SI</option>
                                                <option value="NO" <?php echo ($cumplimientoBPOF == 'NO') ? 'selected' : '' ?>>NO</option>
                                                <option value="N.A." <?php echo ($cumplimientoBPOF == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cumplimientoBPA" class="form-label">Cumplimiento BPA</label>
                                            <select name="cumplimientoBPA" id="cumplimientoBPA" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="SI" <?php echo ($cumplimientoBPA == 'SI') ? 'selected' : '' ?>>SI</option>
                                                <option value="NO" <?php echo ($cumplimientoBPA == 'NO') ? 'selected' : '' ?>>NO</option>
                                                <option value="N.A." <?php echo ($cumplimientoBPA == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cumplimientoBPDyT" class="form-label">Cumplimiento BPDyT</label>
                                            <select name="cumplimientoBPDyT" id="cumplimientoBPDyT" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="SI" <?php echo ($cumplimientoBPDyT == 'SI') ? 'selected' : '' ?>>SI</option>
                                                <option value="NO" <?php echo ($cumplimientoBPDyT == 'NO') ? 'selected' : '' ?>>NO</option>
                                                <option value="N.A." <?php echo ($cumplimientoBPDyT == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="cumplimientoBPF" class="form-label">Cumplimiento BPF</label>
                                            <select name="cumplimientoBPF" id="cumplimientoBPF" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="SI" <?php echo ($cumplimientoBPF == 'SI') ? 'selected' : '' ?>>SI</option>
                                                <option value="NO" <?php echo ($cumplimientoBPF == 'NO') ? 'selected' : '' ?>>NO</option>
                                                <option value="N.A." <?php echo ($cumplimientoBPF == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="productosIncautados" class="form-label">Productos Incautados</label>
                                            <select name="productosIncautados" id="productosIncautados" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="SI" <?php echo ($productosIncautados == 'SI') ? 'selected' : '' ?>>SI</option>
                                                <option value="NO" <?php echo ($productosIncautados == 'NO') ? 'selected' : '' ?>>NO</option>
                                                <option value="N.A." <?php echo ($productosIncautados == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="medidaSeguridad" class="form-label">Medidas de Seguridad</label>
                                            <select name="medidaSeguridad" id="medidaSeguridad" class="form-select">
                                                <option value="">Seleccionar</option>
                                                <option value="INMOVILIZACIÓN" <?php echo ($medidaSeguridad == 'INMOVILIZACIÓN') ? 'selected' : '' ?>>INMOVILIZACIÓN</option>
                                                <option value="INCAUTACIÓN" <?php echo ($medidaSeguridad == 'INCAUTACIÓN') ? 'selected' : '' ?>>INCAUTACIÓN</option>
                                                <option value="AISLAMIENTO DE PRODUCTOS E INSUMOS" <?php echo ($medidaSeguridad == 'AISLAMIENTO DE PRODUCTOS E INSUMOS') ? 'selected' : '' ?>>AISLAMIENTO DE PRODUCTOS E INSUMOS</option>
                                                <option value="RETIRO DE PRODUCTOS DEL MERCADO" <?php echo ($medidaSeguridad == 'RETIRO DE PRODUCTOS DEL MERCADO') ? 'selected' : '' ?>>RETIRO DE PRODUCTOS DEL MERCADO</option>
                                                <option value="DESTRUCCIÓN DE PRODUCTOS E INSUMOS" <?php echo ($medidaSeguridad == 'DESTRUCCIÓN DE PRODUCTOS E INSUMOS') ? 'selected' : '' ?>>DESTRUCCIÓN DE PRODUCTOS E INSUMOS</option>
                                                <option value="CIERRE TEMPORAL" <?php echo ($medidaSeguridad == 'CIERRE TEMPORAL') ? 'selected' : '' ?>>CIERRE TEMPORAL</option>
                                                <option value="EMISIÓN DE MENSAJES PUBLICITARIOS O ALERTAS" <?php echo ($medidaSeguridad == 'EMISIÓN DE MENSAJES PUBLICITARIOS O ALERTAS') ? 'selected' : '' ?>>EMISIÓN DE MENSAJES PUBLICITARIOS O ALERTAS</option>
                                                <option value="N.A." <?php echo ($medidaSeguridad == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Subcampos BPOF (se muestran si BPOF = SI o NO) -->
                                    <div class="subcampos-bpof" id="subcamposBPOF">
                                        <h6 class="fw-bold mb-3" style="color: #0b2a4a;"><i class="fas fa-layer-group me-2"></i>Detalle BPOF</h6>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label for="bpd" class="form-label">BPD</label>
                                                <select name="bpd" id="bpd" class="form-select">
                                                    <option value="">Seleccionar</option>
                                                    <option value="SI" <?php echo ($bpd == 'SI') ? 'selected' : '' ?>>SI</option>
                                                    <option value="NO" <?php echo ($bpd == 'NO') ? 'selected' : '' ?>>NO</option>
                                                    <option value="N.A." <?php echo ($bpd == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="bpa" class="form-label">BPA</label>
                                                <select name="bpa" id="bpa" class="form-select">
                                                    <option value="">Seleccionar</option>
                                                    <option value="SI" <?php echo ($bpa == 'SI') ? 'selected' : '' ?>>SI</option>
                                                    <option value="NO" <?php echo ($bpa == 'NO') ? 'selected' : '' ?>>NO</option>
                                                    <option value="N.A." <?php echo ($bpa == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="bpf" class="form-label">BPF</label>
                                                <select name="bpf" id="bpf" class="form-select">
                                                    <option value="">Seleccionar</option>
                                                    <option value="SI" <?php echo ($bpf == 'SI') ? 'selected' : '' ?>>SI</option>
                                                    <option value="NO" <?php echo ($bpf == 'NO') ? 'selected' : '' ?>>NO</option>
                                                    <option value="N.A." <?php echo ($bpf == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="bpsf" class="form-label">BPSF</label>
                                                <select name="bpsf" id="bpsf" class="form-select">
                                                    <option value="">Seleccionar</option>
                                                    <option value="SI" <?php echo ($bpsf == 'SI') ? 'selected' : '' ?>>SI</option>
                                                    <option value="NO" <?php echo ($bpsf == 'NO') ? 'selected' : '' ?>>NO</option>
                                                    <option value="N.A." <?php echo ($bpsf == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="bpdt" class="form-label">BPDT</label>
                                                <select name="bpdt" id="bpdt" class="form-select">
                                                    <option value="">Seleccionar</option>
                                                    <option value="SI" <?php echo ($bpdt == 'SI') ? 'selected' : '' ?>>SI</option>
                                                    <option value="NO" <?php echo ($bpdt == 'NO') ? 'selected' : '' ?>>NO</option>
                                                    <option value="N.A." <?php echo ($bpdt == 'N.A.') ? 'selected' : '' ?>>N.A.</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Sección 3: Medidas de Seguridad (MS) -->
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
                                            <label for="fechaDescargoActa" class="form-label">Descargo al Acta de Inspección (7 días)
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="SOLICITUD DE AMPLIACION DE PLAZO, NULIDAD U OTROS"></i>
                                            </label>
                                            <input type="date" class="form-control form-control-modern" name="fechaDescargoActa" id="fechaDescargoActa" value="<?php echo $fechaDescargoActa ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="oficioOtorgaDeniegaPlazo" class="form-label">Oficio que otorga o deniega el plazo</label>
                                            <input type="text" class="form-control form-control-modern" name="oficioOtorgaDeniegaPlazo" id="oficioOtorgaDeniegaPlazo" value="<?php echo htmlspecialchars($oficioOtorgaDeniegaPlazo ?? '') ?>" placeholder="N° de oficio">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="idSituacionDigemidSeleccionada" class="form-label">Seleccionar Estado del Local</label>
                                            <select name="idSituacionDigemidSeleccionada" id="idSituacionDigemidSeleccionada" class="form-select">
                                                <option value="">-- Seleccionar --</option>
                                                <?php foreach ($situacionesDigemid as $sit): ?>
                                                    <option value="<?php echo $sit['idSituacionDigemid'] ?>" <?php echo ($idSituacionDigemidSeleccionada == $sit['idSituacionDigemid']) ? 'selected' : '' ?>>
                                                        <?php echo htmlspecialchars($sit['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">Al guardar, se actualizará el estado de la sede seleccionada.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="docElevaNulidad" class="form-label">Doc. Eleva nulidad</label>
                                            <input type="text" class="form-control form-control-modern" name="docElevaNulidad" id="docElevaNulidad" value="<?php echo htmlspecialchars($docElevaNulidad ?? '') ?>" placeholder="N° de documento">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="resuelveNulidad" class="form-label">Resuelve nulidad</label>
                                            <input type="text" class="form-control form-control-modern" name="resuelveNulidad" id="resuelveNulidad" value="<?php echo htmlspecialchars($resuelveNulidad ?? '') ?>" placeholder="N° de resolución">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="informeTecnicoInspeccion" class="form-label">Informe técnico de Inspección</label>
                                            <input type="text" class="form-control form-control-modern" name="informeTecnicoInspeccion" id="informeTecnicoInspeccion" value="<?php echo htmlspecialchars($informeTecnicoInspeccion ?? '') ?>" placeholder="N° de informe">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nCertificadoBuenasPracticas" class="form-label">N° certificado buenas prácticas</label>
                                            <input type="text" class="form-control form-control-modern" name="nCertificadoBuenasPracticas" id="nCertificadoBuenasPracticas" value="<?php echo htmlspecialchars($nCertificadoBuenasPracticas ?? '') ?>" placeholder="N° de certificado">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaInicioCertificadoBP" class="form-label">Fecha Inicio Certificado B.P.
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="FECHA DE INICIO DE LA CERTIFICACIÓN BUENAS PRACTICAS"></i>
                                            </label>
                                            <input type="date" class="form-control form-control-modern" name="fechaInicioCertificadoBP" id="fechaInicioCertificadoBP" value="<?php echo $fechaInicioCertificadoBP ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaFinCertificadoBP" class="form-label">Fecha Fin Certificado B.P.
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="FECHA DE TERMINO DE LA CERTIFICACIÓN BUENAS PRACTICAS"></i>
                                            </label>
                                            <input type="date" class="form-control form-control-modern" name="fechaFinCertificadoBP" id="fechaFinCertificadoBP" value="<?php echo $fechaFinCertificadoBP ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rgrRatificaCierreTemporal" class="form-label">RGR. Ratifica Medida de Cierre Temporal</label>
                                            <input type="text" class="form-control form-control-modern" name="rgrRatificaCierreTemporal" id="rgrRatificaCierreTemporal" value="<?php echo htmlspecialchars($rgrRatificaCierreTemporal ?? '') ?>" placeholder="Ej. RGR. N° 0300-2018">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaNotificacionRGRCierre" class="form-label">Fecha de Notificación de la RGR. de Cierre temporal</label>
                                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionRGRCierre" id="fechaNotificacionRGRCierre" value="<?php echo $fechaNotificacionRGRCierre ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="descargoApelacion" class="form-label">Descargo y/o apelación</label>
                                            <input type="text" class="form-control form-control-modern" name="descargoApelacion" id="descargoApelacion" value="<?php echo htmlspecialchars($descargoApelacion ?? '') ?>" placeholder="Descripción o número">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nDocResuelveRecurso" class="form-label">N° Doc resuelve recurso</label>
                                            <input type="text" class="form-control form-control-modern" name="nDocResuelveRecurso" id="nDocResuelveRecurso" value="<?php echo htmlspecialchars($nDocResuelveRecurso ?? '') ?>" placeholder="N° de documento">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rsgLevantamientoCierre" class="form-label">RSG. de Levantamiento de cierre</label>
                                            <input type="text" class="form-control form-control-modern" name="rsgLevantamientoCierre" id="rsgLevantamientoCierre" value="<?php echo htmlspecialchars($rsgLevantamientoCierre ?? '') ?>" placeholder="Ej. RSG N° 200-2026">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaNotificacionRSGLevantamiento" class="form-label">Fecha de Notificación RSG. Levantamiento de cierre</label>
                                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionRSGLevantamiento" id="fechaNotificacionRSGLevantamiento" value="<?php echo $fechaNotificacionRSGLevantamiento ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cierreDefinitivo" class="form-label">Cierre definitivo</label>
                                            <input type="text" class="form-control form-control-modern" name="cierreDefinitivo" id="cierreDefinitivo" value="<?php echo htmlspecialchars($cierreDefinitivo ?? '') ?>" placeholder="Ej. RSG N 056-2026">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaNotificacionCierreDefinitivo" class="form-label">Fecha de notificación del cierre de envío</label>
                                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionCierreDefinitivo" id="fechaNotificacionCierreDefinitivo" value="<?php echo $fechaNotificacionCierreDefinitivo ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fechaEnvioFiscalia" class="form-label">Fecha envío a Fiscalía</label>
                                            <input type="date" class="form-control form-control-modern" name="fechaEnvioFiscalia" id="fechaEnvioFiscalia" value="<?php echo $fechaEnvioFiscalia ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nroDocumentoFiscalia" class="form-label">N° de documento envío a Fiscalía</label>
                                            <input type="text" class="form-control form-control-modern" name="nroDocumentoFiscalia" id="nroDocumentoFiscalia" value="<?php echo htmlspecialchars($nroDocumentoFiscalia ?? '') ?>" placeholder="N° de documento">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button type="submit" name="btnGuardar" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i><?php echo $esEdicion ? 'Actualizar Expediente' : 'Guardar Expediente' ?>
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
                        <i class="fas fa-list me-2"></i>Expedientes UFREMID Registrados
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
                                <th>Categoría</th>
                                <th>RUC</th>
                                <th>Estado</th>
                                <th>Inspector</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expedientes as $exp): ?>
                            <tr>
                                <td><?php echo $exp['idExpediente'] ?></td>
                                <td><?php echo htmlspecialchars($exp['numeroActa'] ?? '') ?></td>
                                <td><?php echo htmlspecialchars($exp['nombreSede'] ?? '') ?></td>
                                <td><?php echo $exp['fechaInspeccion'] ?? '' ?></td>
                                <td><?php echo htmlspecialchars($exp['categoria'] ?? '') ?></td>
                                <td><?php echo htmlspecialchars($exp['ruc'] ?? '') ?></td>
                                <td>
                                    <?php
                                        $estado     = $exp['estadoExpediente'] ?? '';
                                        $badgeClass = 'badge-proceso';
                                        if ($estado == 'CERRADO') {
                                            $badgeClass = 'badge-cerrado';
                                        } elseif ($estado == 'ARCHIVADO') {
                                            $badgeClass = 'badge-archivado';
                                        }
                                    ?>
                                    <span class="badge-estado <?php echo $badgeClass ?>"><?php echo htmlspecialchars($estado) ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($exp['responsable'] ?? '') ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cogs"></i> Acciones
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="formExpedienteFI.php?idExpediente=<?php echo $exp['idExpediente'] ?>&area=UFREMID"><i class="fas fa-gavel me-2"></i>Fase Instructora (FI)</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="?editar=<?php echo $exp['idExpediente'] ?>"><i class="fas fa-edit me-2"></i>Editar Expediente</a></li>
                                            <li><a class="dropdown-item text-danger" href="eliminarExpediente.php?id=<?php echo $exp['idExpediente'] ?>&area=UFREMID" onclick="return confirm('¿Está seguro de eliminar este expediente?')"><i class="fas fa-trash-alt me-2"></i>Eliminar</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="seguimiento.php?id=<?= $exp['idExpediente'] ?>"><i class="fas fa-chart-line me-2"></i>Seguimiento</a></li>
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
            <p class="mb-0">&copy; <?php echo date('Y') ?> Sub Gerencia de Regulación Sectorial - Todos los derechos reservados.</p>
        </div>
    </footer>

    <?php include 'boostrap-js.php'; ?>
    <?php include 'datatable-js.php'; ?>
    <?php include 'select2-js.php'; ?>

    <script>
        $(document).ready(function() {
            // DataTable
            $('#tablaExpedientes').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true,
                order: [[0, 'desc']]
            });

            // Select2
            if ($.fn.select2) {
                $('.select2-auto').select2({
                    width: '100%',
                    placeholder: 'Buscar...',
                    allowClear: true
                });
            }

            // Popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    trigger: 'hover',
                    placement: 'top'
                });
            });

            // Mostrar/ocultar campo "Otro estado"
            $('#estadoExpediente').change(function() {
                if ($(this).val() === 'OTRO') {
                    $('#divOtroEstado').show();
                } else {
                    $('#divOtroEstado').hide();
                }
            });

            // ============================================
            // DEPENDENCIAS: Equipo DCVS → Actividad → Tipo
            // ============================================
            $('#idEquipoDCVS').change(function() {
                const idEquipo = $(this).val();
                const $actividad = $('#idActividadAbrev');
                const $tipo = $('#idTipoActividad');

                $actividad.html('<option value="">Cargando...</option>');
                $tipo.html('<option value="">Primero seleccione Actividad</option>');

                if (idEquipo) {
                    $.ajax({
                        type: 'POST',
                        url: '../persistencia/dExpediente.php',
                        data: { action: 'listarActividades', idEquipo: idEquipo },
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">Seleccionar</option>';
                            $.each(data, function(index, item) {
                                options += '<option value="' + item.idActividad + '">' + item.nombre + '</option>';
                            });
                            $actividad.html(options);
                            // Si hay un valor guardado, seleccionarlo
                            <?php if ($esEdicion && !empty($idActividadAbrev)): ?>
                                $actividad.val('<?php echo $idActividadAbrev ?>').trigger('change');
                            <?php endif; ?>
                        },
                        error: function() {
                            alert('Error al cargar actividades');
                            $actividad.html('<option value="">Error al cargar</option>');
                        }
                    });
                } else {
                    $actividad.html('<option value="">Primero seleccione Equipo DCVS</option>');
                }
            });

            $('#idActividadAbrev').change(function() {
                const idActividad = $(this).val();
                const $tipo = $('#idTipoActividad');
                $tipo.html('<option value="">Cargando...</option>');

                if (idActividad) {
                    $.ajax({
                        type: 'POST',
                        url: '../persistencia/dExpediente.php',
                        data: { action: 'listarTiposActividad', idActividad: idActividad },
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">Seleccionar</option>';
                            $.each(data, function(index, item) {
                                options += '<option value="' + item.idTipoActividad + '">' + item.nombre + '</option>';
                            });
                            $tipo.html(options);
                            <?php if ($esEdicion && !empty($idTipoActividad)): ?>
                                $tipo.val('<?php echo $idTipoActividad ?>');
                            <?php endif; ?>
                        },
                        error: function() {
                            alert('Error al cargar tipos de actividad');
                            $tipo.html('<option value="">Error al cargar</option>');
                        }
                    });
                } else {
                    $tipo.html('<option value="">Primero seleccione Actividad</option>');
                }
            });

            // ============================================
            // Mostrar/ocultar subcampos BPOF
            // ============================================
            function toggleSubcamposBPOF() {
                const valor = $('#cumplimientoBPOF').val();
                if (valor === 'SI' || valor === 'NO') {
                    $('#subcamposBPOF').slideDown();
                } else {
                    $('#subcamposBPOF').slideUp();
                    // Limpiar valores
                    $('#bpd, #bpa, #bpf, #bpsf, #bpdt').val('');
                }
            }

            $('#cumplimientoBPOF').change(function() {
                toggleSubcamposBPOF();
            });

            // Estado inicial
            toggleSubcamposBPOF();

            // Si estamos editando, disparar eventos para cargar selects dependientes
            <?php if ($esEdicion && !empty($idEquipoDCVS)): ?>
                // Forzar carga de actividades después de cargar la página
                setTimeout(function() {
                    $('#idEquipoDCVS').trigger('change');
                }, 300);
            <?php endif; ?>

            // ============================================
            // FUNCIÓN PARA ELIMINAR (desde la tabla)
            // ============================================
            window.eliminarExpediente = function(id) {
                if (confirm('¿Está seguro de eliminar este expediente?')) {
                    window.location.href = 'eliminarExpediente.php?id=' + id + '&area=UFREMID';
                }
            };
        });

        function cancelar() {
            window.location.href = '<?php echo $_SERVER['PHP_SELF'] ?>';
        }
    </script>
</body>
</html>