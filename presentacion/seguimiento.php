<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dExpediente.php');

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$idExpediente = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$idExpediente) {
    header("Location: formExpedienteUFREMID.php?mensaje=" . urlencode("ID de expediente no válido"));
    exit;
}

// Obtener datos del expediente
$expediente = obtenerExpediente($pdo, $idExpediente);
if (!$expediente) {
    header("Location: formExpedienteUFREMID.php?mensaje=" . urlencode("Expediente no encontrado"));
    exit;
}

// Obtener plazos del expediente
$sqlPlazos = "SELECT * FROM expediente_plazos WHERE idExpediente = ? ORDER BY fechaVencimiento ASC";
$stmtPlazos = $pdo->prepare($sqlPlazos);
$stmtPlazos->execute([$idExpediente]);
$plazos = $stmtPlazos->fetchAll(PDO::FETCH_ASSOC);

// Obtener MS (si existe)
$sqlMS = "SELECT * FROM expediente_ms WHERE idExpediente = ?";
$stmtMS = $pdo->prepare($sqlMS);
$stmtMS->execute([$idExpediente]);
$ms = $stmtMS->fetch(PDO::FETCH_ASSOC);

// Obtener FI (puede haber múltiples)
$sqlFI = "SELECT * FROM expediente_fi WHERE idExpediente = ? ORDER BY idExpedienteFI ASC";
$stmtFI = $pdo->prepare($sqlFI);
$stmtFI->execute([$idExpediente]);
$fis = $stmtFI->fetchAll(PDO::FETCH_ASSOC);

// Obtener FS (puede haber múltiples, pero generalmente uno por FI)
$sqlFS = "SELECT * FROM expediente_fs WHERE idExpediente = ? ORDER BY idExpedienteFS ASC";
$stmtFS = $pdo->prepare($sqlFS);
$stmtFS->execute([$idExpediente]);
$fss = $stmtFS->fetchAll(PDO::FETCH_ASSOC);

// Función para determinar el estado de un plazo en tiempo real
function getEstadoPlazo(array $plazo): string {
    $fechaVencimiento = $plazo['fechaVencimiento'];
    $fechaCumplimiento = $plazo['fechaCumplimiento'];
    
    // Si no hay fecha de vencimiento, no se puede evaluar
    if (!$fechaVencimiento) {
        return 'SIN_FECHA';
    }
    
    $hoy = new DateTime();
    $venc = new DateTime($fechaVencimiento);
    
    // Si hay fecha de cumplimiento, evaluar si fue dentro del plazo
    if ($fechaCumplimiento) {
        $cumpl = new DateTime($fechaCumplimiento);
        if ($cumpl <= $venc) {
            return 'CUMPLIDO';
        } else {
            return 'VENCIDO'; // Presentado fuera de plazo
        }
    }
    
    // Si no hay cumplimiento, evaluar si está vencido o vigente
    if ($hoy > $venc) {
        return 'VENCIDO';
    }
    
    $diferencia = $hoy->diff($venc)->days;
    if ($diferencia <= 3) {
        return 'PROXIMO_VENCER';
    }
    
    return 'VIGENTE';
}

// Función para obtener clase CSS según estado
function getBadgeClass(string $estado) {
    switch ($estado) {
        case 'CUMPLIDO': return 'badge-plazo-cumplido';
        case 'PROXIMO_VENCER': return 'badge-plazo-proximo';
        case 'VENCIDO': return 'badge-plazo-vencido';
        case 'VIGENTE': return 'badge-plazo-vigente';
        default: return 'badge-plazo-vigente';
    }
}

// Función para obtener texto del estado
function getEstadoTexto(string $estado) {
    switch ($estado) {
        case 'CUMPLIDO': return '✅ Cumplido';
        case 'PROXIMO_VENCER': return '🟡 Próximo a vencer';
        case 'VENCIDO': return '❌ Vencido';
        case 'VIGENTE': return '🔵 Vigente';
        default: return '⏳ Sin fecha';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Expediente <?= htmlspecialchars($expediente['numeroActa']) ?></title>
    <?php include 'boostrap-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color: #f0f4fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-header { background: linear-gradient(135deg, #0b2a4a 0%, #1b4f8b 100%); color: white; padding: 30px 0 25px; border-radius: 0 0 40px 40px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .page-header h2 { font-weight: 700; margin: 0; }
        .page-header p { opacity: 0.85; margin: 5px 0 0; }
        .card-modern { border: none; border-radius: 24px; background: #ffffff; box-shadow: 0 8px 25px rgba(0,0,0,0.06); transition: transform 0.2s; }
        .card-modern:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .badge-plazo-vigente { background: #17a2b8; color: white; padding: 6px 12px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; }
        .badge-plazo-proximo { background: #ffc107; color: #212529; padding: 6px 12px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; }
        .badge-plazo-vencido { background: #dc3545; color: white; padding: 6px 12px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; }
        .badge-plazo-cumplido { background: #28a745; color: white; padding: 6px 12px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; }
        .timeline-item { border-left: 3px solid #1b4f8b; padding-left: 20px; margin-bottom: 20px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: -8px; top: 0; width: 12px; height: 12px; background: #1b4f8b; border-radius: 50%; border: 2px solid white; }
        .timeline-item.cumplido::before { background: #28a745; }
        .timeline-item.vencido::before { background: #dc3545; }
        .timeline-item.proximo::before { background: #ffc107; }
        .timeline-item.vigente::before { background: #17a2b8; }
        .timeline-title { font-weight: 600; color: #0b2a4a; }
        .timeline-date { font-size: 0.85rem; color: #6c757d; }
        .badge-area { background: #eaf3ff; color: #1b4f8b; padding: 4px 16px; border-radius: 30px; font-size: 0.8rem; font-weight: 500; }
        .footer-custom { background: #0b2a4a; color: rgba(255,255,255,0.7); padding: 20px 0; border-radius: 40px 40px 0 0; margin-top: 40px; text-align: center; font-size: 0.9rem; }
        .footer-custom a { color: white; text-decoration: none; }
        .footer-custom a:hover { text-decoration: underline; }
        @media (max-width: 768px) { .page-header { padding: 20px 0; } }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-chart-line me-2"></i>Seguimiento de Expediente</h2>
            <p>
                Acta: <strong><?= htmlspecialchars($expediente['numeroActa']) ?></strong> |
                Área: <span class="badge-area"><?= htmlspecialchars($expediente['areaOrigen']) ?></span> |
                Sede: <?= htmlspecialchars($expediente['nombreSede']) ?>
            </p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card card-modern">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4" style="color: #0b2a4a;">
                            <i class="fas fa-clock me-2"></i>Línea de Tiempo
                            <a href="formExpediente<?= $expediente['areaOrigen'] ?>.php" class="btn btn-outline-secondary btn-sm float-end">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                        </h5>

                        <?php if (empty($plazos) && !$ms && empty($fis) && empty($fss)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Este expediente aún no tiene plazos registrados.
                            </div>
                        <?php endif; ?>

                        <!-- 1. CREACIÓN -->
                        <div class="timeline-item <?= $expediente['fechaInspeccion'] ? 'vigente' : '' ?>">
                            <div class="timeline-title">
                                <i class="fas fa-plus-circle me-2" style="color: #1b4f8b;"></i>Creación del Expediente
                            </div>
                            <div class="timeline-date">
                                Fecha de Inspección: <?= $expediente['fechaInspeccion'] ? date('d/m/Y', strtotime($expediente['fechaInspeccion'])) : 'No registrada' ?>
                            </div>
                            <?php if ($expediente['fechaInspeccion']): ?>
                                <?php
                                // Buscar plazo de descargo de acta
                                $plazoActa = null;
                                foreach ($plazos as $p) {
                                    if ($p['evento'] == 'DESCARGO_ACTA') {
                                        $plazoActa = $p;
                                        break;
                                    }
                                }
                                if ($plazoActa):
                                    $estado = getEstadoPlazo($plazoActa);
                                    $badgeClass = getBadgeClass($estado);
                                ?>
                                    <div class="mt-2">
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= getEstadoTexto($estado) ?>
                                        </span>
                                        <span class="ms-2">
                                            <i class="fas fa-calendar-alt me-1"></i> Vence: <?= date('d/m/Y', strtotime($plazoActa['fechaVencimiento'])) ?>
                                        </span>
                                        <?php if ($plazoActa['fechaCumplimiento']): ?>
                                            <span class="ms-2 text-success">
                                                <i class="fas fa-check-circle me-1"></i> Cumplido el <?= date('d/m/Y', strtotime($plazoActa['fechaCumplimiento'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- 2. MEDIDAS DE SEGURIDAD -->
                        <?php if ($ms): ?>
                            <div class="timeline-item <?= $ms['fechaDescargoActa'] ? 'cumplido' : 'vigente' ?>">
                                <div class="timeline-title">
                                    <i class="fas fa-shield-alt me-2" style="color: #1b4f8b;"></i>Medidas de Seguridad (MS)
                                </div>
                                <div class="timeline-date">
                                    <?php if ($ms['fechaDescargoActa']): ?>
                                        <i class="fas fa-calendar-check me-1"></i> Descargo presentado: <?= date('d/m/Y', strtotime($ms['fechaDescargoActa'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin descargo registrado</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($ms['rgrRatificaCierreTemporal']): ?>
                                    <div class="mt-1"><small><i class="fas fa-file-alt me-1"></i> RGR Cierre: <?= htmlspecialchars($ms['rgrRatificaCierreTemporal']) ?></small></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 3. FASE INSTRUCTORA -->
                        <?php if ($fis): ?>
                            <?php foreach ($fis as $idx => $fi): ?>
                                <div class="timeline-item <?= $fi['fechaNotificacionInicioPAS'] ? 'vigente' : '' ?>">
                                    <div class="timeline-title">
                                        <i class="fas fa-gavel me-2" style="color: #1b4f8b;"></i>
                                        Fase Instructora (FI) #<?= $idx + 1 ?>
                                        <?php if ($fi['tipoEvento'] == 'REINICIO'): ?>
                                            <span class="badge bg-warning text-dark ms-2">Reinicio</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-date">
                                        <i class="fas fa-calendar-alt me-1"></i> Notificación: <?= $fi['fechaNotificacionInicioPAS'] ? date('d/m/Y', strtotime($fi['fechaNotificacionInicioPAS'])) : 'No registrada' ?>
                                        <?php if ($fi['oficioIniciaPAS']): ?>
                                            <span class="ms-3"><i class="fas fa-file-alt me-1"></i> Oficio: <?= htmlspecialchars($fi['oficioIniciaPAS']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    // Plazos asociados a este FI (por idExpedienteFI)
                                    foreach ($plazos as $p) {
                                        if ($p['idExpedienteFI'] == $fi['idExpedienteFI']) {
                                            $estado = getEstadoPlazo($p);
                                            $badgeClass = getBadgeClass($estado);
                                    ?>
                                            <div class="mt-2">
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= getEstadoTexto($estado) ?>
                                                </span>
                                                <span class="ms-2">
                                                    <?php if ($p['evento'] == 'DESCARGO_PAS'): ?>
                                                        <i class="fas fa-file-signature me-1"></i> Descargo:
                                                    <?php elseif ($p['evento'] == 'CADUCIDAD_PAS'): ?>
                                                        <i class="fas fa-hourglass-end me-1"></i> Caducidad:
                                                    <?php endif; ?>
                                                    Vence: <?= date('d/m/Y', strtotime($p['fechaVencimiento'])) ?>
                                                </span>
                                                <?php if ($p['fechaCumplimiento']): ?>
                                                    <span class="ms-2 text-success">
                                                        <i class="fas fa-check-circle me-1"></i> Cumplido el <?= date('d/m/Y', strtotime($p['fechaCumplimiento'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                    <?php
                                        }
                                    }
                                    ?>
                                    <?php if ($fi['fechaDescargoPresentado']): ?>
                                        <div class="mt-1 text-success"><i class="fas fa-check-circle me-1"></i> Descargo presentado: <?= date('d/m/Y', strtotime($fi['fechaDescargoPresentado'])) ?></div>
                                    <?php endif; ?>
                                    <?php if ($fi['informeFinalInstruccion']): ?>
                                        <div class="mt-1"><small><i class="fas fa-file-alt me-1"></i> IFI: <?= htmlspecialchars($fi['informeFinalInstruccion']) ?></small></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="timeline-item">
                                <div class="timeline-title">
                                    <i class="fas fa-gavel me-2" style="color: #6c757d;"></i>Fase Instructora (FI)
                                </div>
                                <div class="timeline-date text-muted">No se ha iniciado la Fase Instructora.</div>
                            </div>
                        <?php endif; ?>

                        <!-- 4. FASE SANCIONADORA -->
                        <?php if ($fss): ?>
                            <?php foreach ($fss as $idx => $fs): ?>
                                <div class="timeline-item <?= $fs['fechaNotificacionIFI'] ? 'vigente' : '' ?>">
                                    <div class="timeline-title">
                                        <i class="fas fa-balance-scale me-2" style="color: #1b4f8b;"></i>
                                        Fase Sancionadora (FS) #<?= $idx + 1 ?>
                                    </div>
                                    <div class="timeline-date">
                                        <i class="fas fa-calendar-alt me-1"></i> Notificación IFI: <?= $fs['fechaNotificacionIFI'] ? date('d/m/Y', strtotime($fs['fechaNotificacionIFI'])) : 'No registrada' ?>
                                        <?php if ($fs['oficioTrasladaIFI']): ?>
                                            <span class="ms-3"><i class="fas fa-file-alt me-1"></i> Oficio: <?= htmlspecialchars($fs['oficioTrasladaIFI']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    // Plazos asociados a este FS (por idExpedienteFS)
                                    foreach ($plazos as $p) {
                                        if ($p['idExpedienteFS'] == $fs['idExpedienteFS']) {
                                            $estado = getEstadoPlazo($p);
                                            $badgeClass = getBadgeClass($estado);
                                    ?>
                                            <div class="mt-2">
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= getEstadoTexto($estado) ?>
                                                </span>
                                                <span class="ms-2">
                                                    <?php if ($p['evento'] == 'DESCARGO_IFI'): ?>
                                                        <i class="fas fa-file-signature me-1"></i> Descargo IFI:
                                                    <?php elseif ($p['evento'] == 'RECURSO_SANCION'): ?>
                                                        <i class="fas fa-gavel me-1"></i> Recurso Sanción:
                                                    <?php elseif ($p['evento'] == 'CUMPLIMIENTO_CONSENTIDA'): ?>
                                                        <i class="fas fa-check-double me-1"></i> Cumplimiento Consentida:
                                                    <?php endif; ?>
                                                    Vence: <?= date('d/m/Y', strtotime($p['fechaVencimiento'])) ?>
                                                </span>
                                                <?php if ($p['fechaCumplimiento']): ?>
                                                    <span class="ms-2 text-success">
                                                        <i class="fas fa-check-circle me-1"></i> Cumplido el <?= date('d/m/Y', strtotime($p['fechaCumplimiento'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                    <?php
                                        }
                                    }
                                    ?>
                                    <?php if ($fs['fechaDescargoIFI']): ?>
                                        <div class="mt-1 text-success"><i class="fas fa-check-circle me-1"></i> Descargo IFI presentado: <?= date('d/m/Y', strtotime($fs['fechaDescargoIFI'])) ?></div>
                                    <?php endif; ?>
                                    <?php if ($fs['nResolucionSancion']): ?>
                                        <div class="mt-1"><small><i class="fas fa-file-alt me-1"></i> Resolución Sanción: <?= htmlspecialchars($fs['nResolucionSancion']) ?></small></div>
                                    <?php endif; ?>
                                    <?php if ($fs['pagoApela']): ?>
                                        <div class="mt-1"><small><i class="fas fa-money-bill-wave me-1"></i> Pago/Apela: <?= htmlspecialchars($fs['pagoApela']) ?></small></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="timeline-item">
                                <div class="timeline-title">
                                    <i class="fas fa-balance-scale me-2" style="color: #6c757d;"></i>Fase Sancionadora (FS)
                                </div>
                                <div class="timeline-date text-muted">No se ha iniciado la Fase Sancionadora.</div>
                            </div>
                        <?php endif; ?>

                        <!-- Resumen de plazos (tabla compacta) -->
                        <hr>
                        <h6 class="fw-bold mt-4" style="color: #0b2a4a;"><i class="fas fa-list me-2"></i>Resumen de Plazos</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Evento</th>
                                        <th>Fecha Origen</th>
                                        <th>Fecha Vencimiento</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plazos as $p): ?>
                                        <?php $estado = getEstadoPlazo($p); ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['evento']) ?></td>
                                            <td><?= $p['fechaOrigen'] ? date('d/m/Y', strtotime($p['fechaOrigen'])) : '' ?></td>
                                            <td><?= $p['fechaVencimiento'] ? date('d/m/Y', strtotime($p['fechaVencimiento'])) : '' ?></td>
                                            <td><span class="badge <?= getBadgeClass($estado) ?>"><?= getEstadoTexto($estado) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
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
</body>
</html>