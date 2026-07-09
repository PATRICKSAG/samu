<?php
    include_once __DIR__ . '/../config.php';
    include_once __DIR__ . '/../persistencia/conexion.php';
    include_once __DIR__ . '/../persistencia/dExpediente.php';

    $pdo = Database::getConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $area = $_GET['area'] ?? '';
    if ($area === 'UFREMID' or $area === 'UFRESA' or $area === 'UFRESBIT') {
    $area = $area;
    } else {
    header("Location: formExpedienteUFREMID.php?mensaje=" . urlencode("Área no válida"));
    exit;
    }

    $idExpedienteFI = isset($_GET['idFI']) ? intval($_GET['idFI']) : 0;
    if (! $idExpedienteFI) {
    header("Location: formExpediente" . urlencode($area) . ".php?mensaje=" . urlencode("ID de FI no válido"));
    exit;
    }

    // Obtener datos del expediente y FI
    $fi = obtenerExpedienteFI($pdo, $idExpedienteFI);
    if (! $fi) {
    header("Location: formExpediente" . urlencode($area) . ".php?mensaje=" . urlencode("ID de FI no válido"));
    exit;
    }
    $expediente = obtenerExpediente($pdo, $fi['idExpediente']);
    if (! $expediente) {
    header("Location: formExpediente" . urlencode($area) . ".php?mensaje=" . urlencode("ID de FI no válido"));
    exit;
    }

    // Obtener o crear FS
    $fs = obtenerOCrearFS($pdo, $idExpedienteFI);
    if (! $fs) {
    header("Location: formExpedienteFI.php?idExpediente=" . $fi['idExpediente'] . "&area=" . urlencode($area) . "&mensaje=" . urlencode("Error al crear FS"));
    exit;
    }

    $idExpedienteFS = $fs['idExpedienteFS'];

    $mensaje      = '';
    $mensajeError = '';

    // Procesar guardado de FS
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGuardarFS'])) {
    $data = [
        'idExpedienteFS'                  => $idExpedienteFS,
        'idExpedienteFI'                  => $idExpedienteFI,
        'oficioTrasladaIFI'               => $_POST['oficioTrasladaIFI'] ?? '',
        'fechaNotificacionIFI'            => ! empty($_POST['fechaNotificacionIFI']) ? $_POST['fechaNotificacionIFI'] : null,
        'fechaDescargoIFI'                => ! empty($_POST['fechaDescargoIFI']) ? $_POST['fechaDescargoIFI'] : null,
        'nResolucionSancion'              => $_POST['nResolucionSancion'] ?? '',
        'nInfraccion'                     => $_POST['nInfraccion'] ?? '',
        'sancionImpuesta'                 => $_POST['sancionImpuesta'] ?? '',
        'fechaNotificacionSancion'        => ! empty($_POST['fechaNotificacionSancion']) ? $_POST['fechaNotificacionSancion'] : null,
        'recursoInterpuestoSancion'       => $_POST['recursoInterpuestoSancion'] ?? '',
        'fechaRecursoSancion'             => ! empty($_POST['fechaRecursoSancion']) ? $_POST['fechaRecursoSancion'] : null,
        'pagoApela'                       => $_POST['pagoApela'] ?? '',
        'resolucionRecursoSancion'        => $_POST['resolucionRecursoSancion'] ?? '',
        'resultadoRecurso'                => $_POST['resultadoRecurso'] ?? '',
        'fechaNotificacionRecursoSancion' => ! empty($_POST['fechaNotificacionRecursoSancion']) ? $_POST['fechaNotificacionRecursoSancion'] : null,
        'resolucionConsentida'            => $_POST['resolucionConsentida'] ?? '',
        'fechaNotificacionConsentida'     => ! empty($_POST['fechaNotificacionConsentida']) ? $_POST['fechaNotificacionConsentida'] : null,
        'oficioElevaApelacion'            => $_POST['oficioElevaApelacion'] ?? '',
        'resolucionApelacion'             => $_POST['resolucionApelacion'] ?? '',
        'fechaNotificacionApelacion'      => ! empty($_POST['fechaNotificacionApelacion']) ? $_POST['fechaNotificacionApelacion'] : null,
        'pagaDemandaContenciosa'          => $_POST['pagaDemandaContenciosa'] ?? '',
        'oficioSolicitaInfoProcurador'    => $_POST['oficioSolicitaInfoProcurador'] ?? '',
        'estadoContencioso'               => $_POST['estadoContencioso'] ?? '',
        'observacionesContencioso'        => $_POST['observacionesContencioso'] ?? '',
    ];

    $errores = [];
    if (empty($data['oficioTrasladaIFI'])) {
        $errores[] = "Oficio que traslada IFI es requerido.";
    }

    if (empty($data['fechaNotificacionIFI'])) {
        $errores[] = "Fecha de notificación del IFI es requerida.";
    }

    if (empty($errores)) {
        try {
            guardarExpedienteFS($pdo, $data);
            $mensaje = "Fase Sancionadora guardada correctamente.";
            // Recargar FS actualizado
            $fs = obtenerExpedienteFS($pdo, $idExpedienteFS);
        } catch (Exception $e) {
            $mensajeError = "Error al guardar: " . $e->getMessage();
        }
    } else {
        $mensajeError = implode("<br>", $errores);
    }
    }

    // Procesar pagos (AJAX o POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnAgregarPago'])) {
    $dataPago = [
        'idExpediente'      => $fi['idExpediente'],
        'idExpedienteFS'    => $idExpedienteFS,
        'tipoPago'          => $_POST['tipoPago'] ?? '',
        'numeroComprobante' => $_POST['numeroComprobante'] ?? '',
        'fechaPago'         => $_POST['fechaPago'] ?? '',
        'monto'             => $_POST['monto'] ?? 0,
        'observaciones'     => $_POST['observacionesPago'] ?? '',
    ];
    if (! empty($dataPago['tipoPago']) && ! empty($dataPago['fechaPago'])) {
        try {
            insertarPago($pdo, $dataPago);
            $mensaje = "Pago agregado correctamente.";
            // Redirigir para evitar reenvío
            header("Location: formExpedienteFS.php?idFI=$idExpedienteFI&area=" . urlencode($area) . "&mensaje=" . urlencode($mensaje));
            exit;
        } catch (Exception $e) {
            $mensajeError = "Error al agregar pago: " . $e->getMessage();
        }
    } else {
        $mensajeError = "Tipo de pago y fecha son requeridos.";
    }
    }

    // Eliminar pago
    if (isset($_GET['eliminarPago'])) {
    $idPago = intval($_GET['eliminarPago']);
    try {
        eliminarPago($pdo, $idPago);
        $mensaje = "Pago eliminado correctamente.";
        header("Location: formExpedienteFS.php?idFI=$idExpedienteFI&area=" . urlencode($area) . "&mensaje=" . urlencode($mensaje));
        exit;
    } catch (Exception $e) {
        $mensajeError = "Error al eliminar pago: " . $e->getMessage();
    }
    }

    // Obtener listado de FS (historial)
    $registrosFS = listarExpedienteFS($pdo, $idExpedienteFI);
    $pagos       = listarPagosPorFS($pdo, $idExpedienteFS);

    // Mensaje desde GET
    if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fase Sancionadora - Expediente <?php echo htmlspecialchars($expediente['numeroActa']) ?></title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <?php include 'select2-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... mismos estilos que formExpedienteFI ... */
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
        .icon-input { background: #e9f0fc; padding: 0 15px; border-radius: 12px 0 0 12px; display: flex; align-items: center; color: #1b4f8b; border: 1px solid #dce3ed; border-right: none; }
        .input-group-custom { display: flex; align-items: stretch; }
        .input-group-custom .form-control { border-radius: 0 12px 12px 0; border-left: none; }
        .table-modern { border-radius: 16px; overflow-x: auto !important; box-shadow: 0 5px 20px rgba(0,0,0,0.04); }
        .table-modern thead { background: #0b2a4a; color: white; }
        .table-modern th { font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; white-space: nowrap; padding: 10px 6px; }
        .table-modern td { vertical-align: middle; padding: 8px 6px; }
        .badge-plazo-vigente { background: #28a745; color: white; }
        .badge-plazo-proximo { background: #ffc107; color: #212529; }
        .badge-plazo-vencido { background: #dc3545; color: white; }
        .badge-plazo-cumplido { background: #17a2b8; color: white; }
        .accion-boton { margin-right: 5px; }
        .modal-pago .modal-content { border-radius: 24px; }
        @media (max-width: 768px) { .page-header { padding: 20px 0; } .btn-primary-custom, .btn-outline-secondary-custom { width: 100%; margin-bottom: 5px; } }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-balance-scale me-2"></i>Fase Sancionadora</h2>
            <p>
                Expediente N° <strong><?php echo htmlspecialchars($expediente['numeroActa']) ?></strong> -
                Sede: <?php echo htmlspecialchars($expediente['nombreSede']) ?><br>
                <small>Registro FI N° <?php echo $idExpedienteFI ?></small>
            </p>
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

        <!-- Formulario FS -->
        <div class="card card-modern mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-edit me-2"></i>Gestión de Fase Sancionadora
                </h5>
                <form method="POST" action="">
                    <input type="hidden" name="area" value="<?php echo htmlspecialchars($area) ?>">
                    <input type="hidden" name="idExpedienteFS" value="<?php echo $idExpedienteFS ?>">
                    <div class="row g-3">
                        <!-- Traslado IFI -->
                        <div class="col-md-6">
                            <label for="oficioTrasladaIFI" class="form-label">Oficio que traslada IFI <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-modern" name="oficioTrasladaIFI" id="oficioTrasladaIFI" value="<?php echo htmlspecialchars($fs['oficioTrasladaIFI'] ?? '') ?>" placeholder="N° de oficio" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fechaNotificacionIFI" class="form-label">Fecha de notificación del IFI <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="3° ALARMA"></i>
                            </label>
                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionIFI" id="fechaNotificacionIFI" value="<?php echo $fs['fechaNotificacionIFI'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fechaDescargoIFI" class="form-label">Fecha de descargo al IFI (5 días hábiles)</label>
                            <input type="date" class="form-control form-control-modern" name="fechaDescargoIFI" id="fechaDescargoIFI" value="<?php echo $fs['fechaDescargoIFI'] ?? '' ?>">
                        </div>

                        <div class="col-12"><hr></div>

                        <!-- Resolución Sancionadora -->
                        <div class="col-md-6">
                            <label for="nResolucionSancion" class="form-label">N° Resolución de Sanción</label>
                            <input type="text" class="form-control form-control-modern" name="nResolucionSancion" id="nResolucionSancion" value="<?php echo htmlspecialchars($fs['nResolucionSancion'] ?? '') ?>" placeholder="N° de resolución">
                        </div>
                        <div class="col-md-6">
                            <label for="nInfraccion" class="form-label">N° de infracción</label>
                            <input type="text" class="form-control form-control-modern" name="nInfraccion" id="nInfraccion" value="<?php echo htmlspecialchars($fs['nInfraccion'] ?? '') ?>" placeholder="N° de infracción">
                        </div>
                        <div class="col-md-6">
                            <label for="sancionImpuesta" class="form-label">Sanción impuesta</label>
                            <input type="text" class="form-control form-control-modern" name="sancionImpuesta" id="sancionImpuesta" value="<?php echo htmlspecialchars($fs['sancionImpuesta'] ?? '') ?>" placeholder="Tipo de sanción">
                        </div>
                        <div class="col-md-6">
                            <label for="fechaNotificacionSancion" class="form-label">Fecha de notificación de la Resolución de Sanción</label>
                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionSancion" id="fechaNotificacionSancion" value="<?php echo $fs['fechaNotificacionSancion'] ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="recursoInterpuestoSancion" class="form-label">Recurso interpuesto</label>
                            <input type="text" class="form-control form-control-modern" name="recursoInterpuestoSancion" id="recursoInterpuestoSancion" value="<?php echo htmlspecialchars($fs['recursoInterpuestoSancion'] ?? '') ?>" placeholder="Descripción o número">
                        </div>
                        <div class="col-md-6">
                            <label for="fechaRecursoSancion" class="form-label">Fecha del recurso</label>
                            <input type="date" class="form-control form-control-modern" name="fechaRecursoSancion" id="fechaRecursoSancion" value="<?php echo $fs['fechaRecursoSancion'] ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="pagoApela" class="form-label">Pago o Apela</label>
                            <select name="pagoApela" id="pagoApela" class="form-select">
                                <option value="">Seleccionar</option>
                                <option value="PAGA" <?php echo ($fs['pagoApela'] ?? '') == 'PAGA' ? 'selected' : '' ?>>PAGA</option>
                                <option value="APELA" <?php echo ($fs['pagoApela'] ?? '') == 'APELA' ? 'selected' : '' ?>>APELA</option>
                            </select>
                        </div>

                        <div class="col-12"><hr></div>

                        <!-- Resolución del recurso -->
                        <div class="col-md-6">
                            <label for="resolucionRecursoSancion" class="form-label">Resolución que resuelve el recurso</label>
                            <input type="text" class="form-control form-control-modern" name="resolucionRecursoSancion" id="resolucionRecursoSancion" value="<?php echo htmlspecialchars($fs['resolucionRecursoSancion'] ?? '') ?>" placeholder="N° de resolución">
                        </div>
                        <div class="col-md-6">
                            <label for="resultadoRecurso" class="form-label">Resultado del recurso</label>
                            <select name="resultadoRecurso" id="resultadoRecurso" class="form-select">
                                <option value="">Seleccionar</option>
                                <option value="FUNDADO" <?php echo ($fs['resultadoRecurso'] ?? '') == 'FUNDADO' ? 'selected' : '' ?>>FUNDADO</option>
                                <option value="INFUNDADO" <?php echo ($fs['resultadoRecurso'] ?? '') == 'INFUNDADO' ? 'selected' : '' ?>>INFUNDADO</option>
                                <option value="CONSENTIDO" <?php echo ($fs['resultadoRecurso'] ?? '') == 'CONSENTIDO' ? 'selected' : '' ?>>CONSENTIDO</option>
                                <option value="NO HA LUGAR" <?php echo ($fs['resultadoRecurso'] ?? '') == 'NO HA LUGAR' ? 'selected' : '' ?>>NO HA LUGAR</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="fechaNotificacionRecursoSancion" class="form-label">Fecha de notificación</label>
                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionRecursoSancion" id="fechaNotificacionRecursoSancion" value="<?php echo $fs['fechaNotificacionRecursoSancion'] ?? '' ?>">
                        </div>

                        <div class="col-12"><hr></div>

                        <!-- Consentimiento -->
                        <div class="col-md-6">
                            <label for="resolucionConsentida" class="form-label">Resolución consentida</label>
                            <input type="text" class="form-control form-control-modern" name="resolucionConsentida" id="resolucionConsentida" value="<?php echo htmlspecialchars($fs['resolucionConsentida'] ?? '') ?>" placeholder="N° de resolución">
                        </div>
                        <div class="col-md-6">
                            <label for="fechaNotificacionConsentida" class="form-label">Fecha de notificación</label>
                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionConsentida" id="fechaNotificacionConsentida" value="<?php echo $fs['fechaNotificacionConsentida'] ?? '' ?>">
                        </div>

                        <div class="col-12"><hr></div>

                        <!-- Apelación -->
                        <div class="col-md-6">
                            <label for="oficioElevaApelacion" class="form-label">Oficio que eleva la apelación</label>
                            <input type="text" class="form-control form-control-modern" name="oficioElevaApelacion" id="oficioElevaApelacion" value="<?php echo htmlspecialchars($fs['oficioElevaApelacion'] ?? '') ?>" placeholder="N° de oficio">
                        </div>
                        <div class="col-md-6">
                            <label for="resolucionApelacion" class="form-label">Resolución que resuelve la apelación</label>
                            <input type="text" class="form-control form-control-modern" name="resolucionApelacion" id="resolucionApelacion" value="<?php echo htmlspecialchars($fs['resolucionApelacion'] ?? '') ?>" placeholder="N° de resolución">
                        </div>
                        <div class="col-md-6">
                            <label for="fechaNotificacionApelacion" class="form-label">Fecha de notificación de la apelación</label>
                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionApelacion" id="fechaNotificacionApelacion" value="<?php echo $fs['fechaNotificacionApelacion'] ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="pagaDemandaContenciosa" class="form-label">Paga / Demanda Contencioso Administrativo</label>
                            <input type="text" class="form-control form-control-modern" name="pagaDemandaContenciosa" id="pagaDemandaContenciosa" value="<?php echo htmlspecialchars($fs['pagaDemandaContenciosa'] ?? '') ?>" placeholder="Descripción">
                        </div>

                        <div class="col-12"><hr></div>

                        <!-- Contencioso Administrativo -->
                        <div class="col-md-6">
                            <label for="oficioSolicitaInfoProcurador" class="form-label">Oficio que solicita información al Procurador</label>
                            <input type="text" class="form-control form-control-modern" name="oficioSolicitaInfoProcurador" id="oficioSolicitaInfoProcurador" value="<?php echo htmlspecialchars($fs['oficioSolicitaInfoProcurador'] ?? '') ?>" placeholder="N° de oficio">
                        </div>
                        <div class="col-md-6">
                            <label for="estadoContencioso" class="form-label">Estado del proceso contencioso</label>
                            <input type="text" class="form-control form-control-modern" name="estadoContencioso" id="estadoContencioso" value="<?php echo htmlspecialchars($fs['estadoContencioso'] ?? '') ?>" placeholder="Estado actual">
                        </div>
                        <div class="col-12">
                            <label for="observacionesContencioso" class="form-label">Observaciones</label>
                            <textarea class="form-control form-control-modern" name="observacionesContencioso" id="observacionesContencioso" rows="2" placeholder="Observaciones adicionales"><?php echo htmlspecialchars($fs['observacionesContencioso'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button type="submit" name="btnGuardarFS" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Guardar Fase Sancionadora
                        </button>
                        <a href="formExpedienteFI.php?idExpediente=<?php echo $fi['idExpediente'] ?>&area=<?php echo urlencode($area) ?>" class="btn btn-outline-secondary-custom">
                            <i class="fas fa-arrow-left me-2"></i>Volver a FI
                        </a>
                        <a href="formExpediente<?php echo urlencode($area) ?>.php" class="btn btn-outline-secondary-custom">
                            <i class="fas fa-home me-2"></i>Inicio
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historial de FS (si hubiera múltiples, pero en este caso es 1 a 1) -->
        <div class="card card-modern mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-history me-2"></i>Historial de Fase Sancionadora
                </h5>
                <div class="table-responsive table-modern">
                    <table id="tablaFS" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Oficio Traslado</th>
                                <th>Fecha Notif. IFI</th>
                                <th>Fecha Descargo</th>
                                <th>Estado Descargo</th>
                                <th>Fecha Sanción</th>
                                <th>Estado Recurso</th>
                                <th>Fecha Consentida</th>
                                <th>Estado Cumplimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrosFS as $fsReg): ?>
                            <tr>
                                <td><?php echo $fsReg['idExpedienteFS'] ?></td>
                                <td><?php echo htmlspecialchars($fsReg['oficioTrasladaIFI'] ?? '') ?></td>
                                <td><?php echo $fsReg['fechaNotificacionIFI'] ?? '' ?></td>
                                <td><?php echo $fsReg['fechaDescargoIFI'] ?? '' ?></td>
                                <td>
                                    <?php
                                        $estadoDescargo = $fsReg['estadoDescargoIFI'] ?? 'VIGENTE';
                                        $badgeClass     = 'badge-plazo-vigente';
                                        if ($estadoDescargo == 'PROXIMO_VENCER') {
                                            $badgeClass = 'badge-plazo-proximo';
                                        } elseif ($estadoDescargo == 'VENCIDO') {
                                            $badgeClass = 'badge-plazo-vencido';
                                        } elseif ($estadoDescargo == 'CUMPLIDO') {
                                            $badgeClass = 'badge-plazo-cumplido';
                                        }

                                    ?>
                                    <span class="badge <?php echo $badgeClass ?>"><?php echo $estadoDescargo ?></span>
                                    <br><small><?php echo $fsReg['fechaVencimientoDescargoIFI'] ?? '' ?></small>
                                </td>
                                <td><?php echo $fsReg['fechaNotificacionSancion'] ?? '' ?></td>
                                <td>
                                    <?php
                                        $estadoRecurso = $fsReg['estadoRecursoSancion'] ?? 'VIGENTE';
                                        $badgeClass2   = 'badge-plazo-vigente';
                                        if ($estadoRecurso == 'PROXIMO_VENCER') {
                                            $badgeClass2 = 'badge-plazo-proximo';
                                        } elseif ($estadoRecurso == 'VENCIDO') {
                                            $badgeClass2 = 'badge-plazo-vencido';
                                        } elseif ($estadoRecurso == 'CUMPLIDO') {
                                            $badgeClass2 = 'badge-plazo-cumplido';
                                        }

                                    ?>
                                    <span class="badge <?php echo $badgeClass2 ?>"><?php echo $estadoRecurso ?></span>
                                    <br><small><?php echo $fsReg['fechaVencimientoRecursoSancion'] ?? '' ?></small>
                                </td>
                                <td><?php echo $fsReg['fechaNotificacionConsentida'] ?? '' ?></td>
                                <td>
                                    <?php
                                        $estadoCump  = $fsReg['estadoCumplimientoConsentida'] ?? 'VIGENTE';
                                        $badgeClass3 = 'badge-plazo-vigente';
                                        if ($estadoCump == 'PROXIMO_VENCER') {
                                            $badgeClass3 = 'badge-plazo-proximo';
                                        } elseif ($estadoCump == 'VENCIDO') {
                                            $badgeClass3 = 'badge-plazo-vencido';
                                        } elseif ($estadoCump == 'CUMPLIDO') {
                                            $badgeClass3 = 'badge-plazo-cumplido';
                                        }

                                    ?>
                                    <span class="badge <?php echo $badgeClass3 ?>"><?php echo $estadoCump ?></span>
                                    <br><small><?php echo $fsReg['fechaVencimientoCumplimientoConsentida'] ?? '' ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gestión de Pagos -->
        <div class="card card-modern">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title fw-bold" style="color: #0b2a4a;">
                        <i class="fas fa-money-bill-wave me-2"></i>Pagos
                    </h5>
                    <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalPago">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Pago
                    </button>
                </div>
                <div class="table-responsive table-modern">
                    <table id="tablaPagos" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>N° Comprobante</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td><?php echo $pago['idExpedientePago'] ?></td>
                                <td><?php echo htmlspecialchars($pago['tipoPago'] ?? '') ?></td>
                                <td><?php echo htmlspecialchars($pago['numeroComprobante'] ?? '') ?></td>
                                <td><?php echo $pago['fechaPago'] ?? '' ?></td>
                                <td><?php echo number_format($pago['monto'] ?? 0, 2) ?></td>
                                <td><?php echo htmlspecialchars($pago['observaciones'] ?? '') ?></td>
                                <td>
                                    <a href="?idFI=<?php echo $idExpedienteFI ?>&area=<?php echo urlencode($area) ?>&eliminarPago=<?php echo $pago['idExpedientePago'] ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('¿Eliminar este pago?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Pago -->
    <div class="modal fade modal-pago" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #0b2a4a; color: white; border-radius: 24px 24px 0 0;">
                    <h5 class="modal-title" id="modalPagoLabel"><i class="fas fa-plus-circle me-2"></i>Agregar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="area" value="<?php echo htmlspecialchars($area) ?>">
                        <input type="hidden" name="idExpedienteFS" value="<?php echo $idExpedienteFS ?>">
                        <div class="mb-3">
                            <label for="tipoPago" class="form-label">Tipo de Pago</label>
                            <select name="tipoPago" id="tipoPago" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <option value="MULTA">MULTA</option>
                                <option value="RECURSO">RECURSO</option>
                                <option value="CONSENTIMIENTO">CONSENTIMIENTO</option>
                                <option value="APELACION">APELACION</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="numeroComprobante" class="form-label">N° Comprobante</label>
                            <input type="text" class="form-control form-control-modern" name="numeroComprobante" id="numeroComprobante" placeholder="N° de factura/boleta">
                        </div>
                        <div class="mb-3">
                            <label for="fechaPago" class="form-label">Fecha de Pago</label>
                            <input type="date" class="form-control form-control-modern" name="fechaPago" id="fechaPago" required>
                        </div>
                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto</label>
                            <input type="number" step="0.01" class="form-control form-control-modern" name="monto" id="monto" placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label for="observacionesPago" class="form-label">Observaciones</label>
                            <textarea class="form-control form-control-modern" name="observacionesPago" id="observacionesPago" rows="2" placeholder="Observaciones adicionales"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="btnAgregarPago" class="btn btn-primary-custom">Guardar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
            // DataTables
            $('#tablaFS').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true,
                order: [[0, 'desc']]
            });
            $('#tablaPagos').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true,
                order: [[0, 'desc']]
            });

            // Popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (el) {
                return new bootstrap.Popover(el, {
                    trigger: 'hover',
                    placement: 'top'
                });
            });
        });
    </script>
</body>
</html>