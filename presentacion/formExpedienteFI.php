<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dExpediente.php');

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$area = $_GET['area'] ?? '';
if ($area === 'UFREMID' OR $area === 'UFRESA' OR $area === 'UFRESBIT'){
    $area = $area;
} else {
    header("Location: formExpedienteUFREMID.php?mensaje=" . urlencode("Área no válida"));
    exit;
}


$idExpediente = isset($_GET['idExpediente']) ? intval($_GET['idExpediente']) : 0;
if (!$idExpediente) {
    header("Location: formExpediente" . urlencode($area) . ".php?mensaje=" . urlencode("ID de expediente no válido"));
    exit;
}

// Obtener datos del expediente
$expediente = obtenerExpediente($pdo, $idExpediente);
if (!$expediente) {
    header("Location: formExpediente" . urlencode($area) . ".php?mensaje=" . urlencode("Expediente no encontrado"));
    exit;
}

// Procesar acciones
$mensaje = '';
$mensajeError = '';
$accion = $_GET['accion'] ?? '';
$idFI = isset($_GET['idFI']) ? intval($_GET['idFI']) : 0;

// Si es edición, cargar datos
$datosEdicion = null;
if ($accion === 'editar' && $idFI) {
    $datosEdicion = obtenerExpedienteFI($pdo, $idFI);
    if (!$datosEdicion) {
        header("Location: formExpedienteFI.php?idExpediente=$idExpediente&area=" . urlencode($area) . "&mensaje=" . urlencode("Registro no encontrado"));
        exit;
    }
}

// Procesar guardado de nuevo registro o actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnGuardarFI'])) {
    $tipoEvento = $_POST['tipoEvento'] ?? 'INICIO';
    $oficioIniciaPAS = $_POST['oficioIniciaPAS'] ?? '';
    $fechaNotificacion = !empty($_POST['fechaNotificacionInicioPAS']) ? $_POST['fechaNotificacionInicioPAS'] : null;
    $fechaDescargo = !empty($_POST['fechaDescargoPresentado']) ? $_POST['fechaDescargoPresentado'] : null;
    $informeTecnico = $_POST['informeTecnicoInicioPAS'] ?? '';
    $fechaInforme = !empty($_POST['fechaInformeTecnico']) ? $_POST['fechaInformeTecnico'] : null;
    $documentoEleva = $_POST['documentoElevaEscrito'] ?? '';
    $informeLegal = $_POST['informeLegalCaducidad'] ?? '';
    $resolucionCaducidad = $_POST['resolucionCaducidad'] ?? '';
    $recurso = $_POST['recursoInterpuesto'] ?? '';
    $resolucionRecurso = $_POST['resolucionRecurso'] ?? '';
    $fechaNotifRecurso = !empty($_POST['fechaNotificacionRecurso']) ? $_POST['fechaNotificacionRecurso'] : null;
    $informeFinal = $_POST['informeFinalInstruccion'] ?? '';

    // Si estamos editando
    if ($accion === 'editar' && $idFI) {
        $errores = [];
        if (empty($fechaNotificacion)) {
            $errores[] = "La fecha de notificación es requerida.";
        }
        // No se exige oficio ni otros campos.
        if (empty($errores)) {
            try {
                // Llamamos a la nueva función que actualiza ambas fechas
                    actualizarExpedienteFI($pdo, $idFI, $fechaNotificacion, $fechaDescargo, $area);
                    $mensaje = "Fecha de notificación y descargo actualizadas correctamente.";
                    header("Location: formExpedienteFI.php?idExpediente=$idExpediente&area=" . urlencode($area) . "&mensaje=" . urlencode($mensaje));
                    exit;

                
            } catch (Exception $e) {
                $mensajeError = "Error al actualizar: " . $e->getMessage();
            }
        } else {
            $mensajeError = implode("<br>", $errores);
        }
    } else {
        // Nuevo registro (validación completa)
        $errores = [];
        if (empty($oficioIniciaPAS)) $errores[] = "Oficio de Inicio P.A.S. es requerido.";
        if (empty($fechaNotificacion)) $errores[] = "Fecha de notificación de Inicio de PAS es requerida.";

        if (empty($errores)) {
            try {
                $data = [
                    'idExpediente' => $idExpediente,
                    'tipoEvento' => $tipoEvento,
                    'informeTecnicoInicioPAS' => $informeTecnico,
                    'fechaInformeTecnico' => $fechaInforme,
                    'oficioIniciaPAS' => $oficioIniciaPAS,
                    'fechaNotificacionInicioPAS' => $fechaNotificacion,
                    'fechaDescargoPresentado' => $fechaDescargo,
                    'documentoElevaEscrito' => $documentoEleva,
                    'informeLegalCaducidad' => $informeLegal,
                    'resolucionCaducidad' => $resolucionCaducidad,
                    'recursoInterpuesto' => $recurso,
                    'resolucionRecurso' => $resolucionRecurso,
                    'fechaNotificacionRecurso' => $fechaNotifRecurso,
                    'informeFinalInstruccion' => $informeFinal
                ];
                    $idNuevo = insertarExpedienteFI($pdo, $data, $area);
                    $mensaje = "Registro FI guardado correctamente (ID: $idNuevo).";
                    header("Location: formExpedienteFI.php?idExpediente=$idExpediente&area=" . urlencode($area) . "&mensaje=" .  urlencode($mensaje));
                    exit;
                
            } catch (Exception $e) {
                $mensajeError = "Error al guardar: " . $e->getMessage();
            }
        } else {
            $mensajeError = implode("<br>", $errores);
        }
    }
}

// Obtener listado de FI
$registrosFI = listarExpedienteFI($pdo, $idExpediente);
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
    <title>Fase Instructora - Expediente <?= htmlspecialchars($expediente['numeroActa']) ?></title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <?php include 'select2-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... mismos estilos que formExpedienteUFREMID ... */
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
        @media (max-width: 768px) { .page-header { padding: 20px 0; } .btn-primary-custom, .btn-outline-secondary-custom { width: 100%; margin-bottom: 5px; } }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-gavel me-2"></i>Fase Instructora</h2>
            <p>
                Expediente N° <strong><?= htmlspecialchars($expediente['numeroActa']) ?></strong> - 
                Sede: <?= htmlspecialchars($expediente['nombreSede']) ?>
            </p>
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

        <!-- Formulario para agregar/editar -->
        <div class="card card-modern mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <?php if ($accion === 'editar'): ?>
                        <i class="fas fa-edit me-2"></i>Editar Fecha de Notificación
                    <?php else: ?>
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Inicio de PAS
                    <?php endif; ?>
                </h5>
                <form method="POST" action="">
                    <?php if ($accion === 'editar'): ?>
                        <!-- ... (ocultar otros campos, solo mostrar fecha de notificación y descargo) -->
                        <div class="col-md-6">
                            <label for="fechaNotificacionInicioPAS" class="form-label">
                                Fecha de notificación de Inicio de PAS <span class="text-danger">*</span>
                                <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="1° ALARMA principal por 9 meses"></i>
                            </label>
                            <input type="date" class="form-control form-control-modern" name="fechaNotificacionInicioPAS" id="fechaNotificacionInicioPAS" 
                                value="<?= $datosEdicion ? $datosEdicion['fechaNotificacionInicioPAS'] : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fechaDescargoPresentado" class="form-label">Fecha de Descargo o impugnación <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="5 días hábiles"></i></label>
                            <input type="date" class="form-control form-control-modern" name="fechaDescargoPresentado" id="fechaDescargoPresentado" 
                                value="<?= $datosEdicion ? $datosEdicion['fechaDescargoPresentado'] : '' ?>">
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <?php if ($accion !== 'editar'): ?>
                            <!-- Campos para nuevo registro -->
                            <div class="col-md-6">
                                <label for="tipoEvento" class="form-label">Tipo de Evento</label>
                                <select name="tipoEvento" id="tipoEvento" class="form-select">
                                    <option value="INICIO">Inicio de PAS</option>
                                    <option value="REINICIO">Reinicio de PAS</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="oficioIniciaPAS" class="form-label">Oficio de Inicio P.A.S. <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-modern" name="oficioIniciaPAS" id="oficioIniciaPAS" placeholder="N° de oficio" required>
                            </div>
                            <div class="col-md-6">
                                <label for="informeTecnicoInicioPAS" class="form-label">Informe Técnico de Inicio de PAS</label>
                                <input type="text" class="form-control form-control-modern" name="informeTecnicoInicioPAS" id="informeTecnicoInicioPAS" placeholder="N° de informe">
                            </div>
                            <div class="col-md-6">
                                <label for="fechaInformeTecnico" class="form-label">Fecha del Informe Técnico</label>
                                <input type="date" class="form-control form-control-modern" name="fechaInformeTecnico" id="fechaInformeTecnico">
                            </div>
                            <div class="col-md-6">
                                <label for="informeFinalInstruccion" class="form-label">Informe Final de Instrucción (IFI)</label>
                                <input type="text" class="form-control form-control-modern" name="informeFinalInstruccion" id="informeFinalInstruccion" placeholder="N° de informe">
                            </div>
                            <div class="col-md-6">
                                <label for="fechaNotificacionInicioPAS" class="form-label">
                                    Fecha de notificación de Inicio de PAS <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="1° ALARMA principal por 9 meses"></i>
                                </label>
                                <input type="date" class="form-control form-control-modern" name="fechaNotificacionInicioPAS" id="fechaNotificacionInicioPAS" 
                                    value="<?= $datosEdicion ? $datosEdicion['fechaNotificacionInicioPAS'] : '' ?>" required>
                            </div>
                        <?php endif; ?>


                        <?php if ($accion !== 'editar'): ?>
                            <div class="col-md-6">
                                <label for="fechaDescargoPresentado" class="form-label">Fecha de Descargo o impugnación <i class="fas fa-info-circle text-primary" data-bs-toggle="popover" data-bs-content="5 días hábiles"></i></label>
                                <input type="date" class="form-control form-control-modern" name="fechaDescargoPresentado" id="fechaDescargoPresentado" 
                                    value="<?= $datosEdicion ? $datosEdicion['fechaDescargoPresentado'] : '' ?>">
                            </div>

                            <!-- Campos de caducidad/recurso (aparecen si pasan 9 meses, pero en el formulario los dejamos visibles) -->
                            <div class="col-12 mt-3">
                                <h6 class="fw-bold" style="color: #0b2a4a;">Campos posteriores a la caducidad</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="documentoElevaEscrito" class="form-label">Documento que eleva el escrito</label>
                                <input type="text" class="form-control form-control-modern" name="documentoElevaEscrito" id="documentoElevaEscrito" placeholder="N° de documento">
                            </div>
                            <div class="col-md-6">
                                <label for="informeLegalCaducidad" class="form-label">Informe legal para declarar caducidad</label>
                                <input type="text" class="form-control form-control-modern" name="informeLegalCaducidad" id="informeLegalCaducidad" placeholder="N° de informe">
                            </div>
                            <div class="col-md-6">
                                <label for="resolucionCaducidad" class="form-label">Resolución de caducidad</label>
                                <input type="text" class="form-control form-control-modern" name="resolucionCaducidad" id="resolucionCaducidad" placeholder="N° de resolución">
                            </div>
                            <div class="col-md-6">
                                <label for="recursoInterpuesto" class="form-label">Recurso interpuesto</label>
                                <input type="text" class="form-control form-control-modern" name="recursoInterpuesto" id="recursoInterpuesto" placeholder="Descripción o número">
                            </div>
                            <div class="col-md-6">
                                <label for="resolucionRecurso" class="form-label">RSG que resuelve recurso</label>
                                <input type="text" class="form-control form-control-modern" name="resolucionRecurso" id="resolucionRecurso" placeholder="Ej. RSG N° 083-2014">
                            </div>
                            <div class="col-md-6">
                                <label for="fechaNotificacionRecurso" class="form-label">Fecha de Notificación de la RSG que resuelve Recurso</label>
                                <input type="date" class="form-control form-control-modern" name="fechaNotificacionRecurso" id="fechaNotificacionRecurso">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button type="submit" name="btnGuardarFI" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i><?= ($accion === 'editar') ? 'Actualizar Fecha' : 'Guardar Inicio PAS' ?>
                        </button>
                        <a href="formExpedienteFI.php?idExpediente=<?= $idExpediente ?>&area=<?= urlencode($area) ?>" class="btn btn-outline-secondary-custom">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <a href="formExpediente<?php echo urlencode($area); ?>.php" class="btn btn-outline-secondary-custom">
                            <i class="fas fa-arrow-left me-2"></i>Volver a Expedientes
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historial de FI -->
        <div class="card card-modern">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-history me-2"></i>Historial de Fase Instructora
                </h5>
                <div class="table-responsive table-modern">
                    <table id="tablaFI" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Oficio Inicio</th>
                                <th>Fecha Notificación</th>
                                <th>Fecha Descargo</th>
                                <th>Estado Descargo</th>
                                <th>Caducidad (9 meses)</th>
                                <th>Estado Caducidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrosFI as $fi): ?>
                            <tr>
                                <td><?= $fi['idExpedienteFI'] ?></td>
                                <td><?= htmlspecialchars($fi['tipoEvento']) ?></td>
                                <td><?= htmlspecialchars($fi['oficioIniciaPAS'] ?? '') ?></td>
                                <td><?= $fi['fechaNotificacionInicioPAS'] ?? '' ?></td>
                                <td><?= $fi['fechaDescargoPresentado'] ?? '' ?></td>
                                <td>
                                    <?php 
                                    $estadoDescargo = $fi['estadoDescargo'] ?? 'VIGENTE';
                                    $badgeClass = 'badge-plazo-vigente';
                                    if ($estadoDescargo == 'PROXIMO_VENCER') $badgeClass = 'badge-plazo-proximo';
                                    elseif ($estadoDescargo == 'VENCIDO') $badgeClass = 'badge-plazo-vencido';
                                    elseif ($estadoDescargo == 'CUMPLIDO') $badgeClass = 'badge-plazo-cumplido';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $estadoDescargo ?></span>
                                    <br><small><?= $fi['fechaVencimientoDescargo'] ?? '' ?></small>
                                </td>
                                <td><?= $fi['fechaVencimientoCaducidad'] ?? '' ?></td>
                                <td>
                                    <?php 
                                    $estadoCad = $fi['estadoCaducidad'] ?? 'VIGENTE';
                                    $badgeClass2 = 'badge-plazo-vigente';
                                    if ($estadoCad == 'PROXIMO_VENCER') $badgeClass2 = 'badge-plazo-proximo';
                                    elseif ($estadoCad == 'VENCIDO') $badgeClass2 = 'badge-plazo-vencido';
                                    elseif ($estadoCad == 'CUMPLIDO') $badgeClass2 = 'badge-plazo-cumplido';
                                    ?>
                                    <span class="badge <?= $badgeClass2 ?>"><?= $estadoCad ?></span>
                                </td>
                                <td>
                                    <a href="?idExpediente=<?= $idExpediente ?>&accion=editar&idFI=<?= $fi['idExpedienteFI'] ?>&area=<?= urlencode($area) ?>" class="btn btn-sm btn-primary accion-boton" title="Editar fecha de notificación">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="formExpedienteFS.php?idFI=<?= (int)$fi['idExpedienteFI'] ?>&area=<?= urlencode($area) ?>" class="btn btn-sm btn-success accion-boton" title="Fase Sancionadora">
                                        <i class="fas fa-balance-scale"></i> FS
                                    </a>
                                    <!-- No hay botón eliminar (solo historial) -->
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
            // DataTable
            $('#tablaFI').DataTable({
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