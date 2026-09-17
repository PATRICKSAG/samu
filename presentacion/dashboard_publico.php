<?php
// presentacion/dashboard_publico.php
// Dashboard público (sin autenticación)

include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../persistencia/conexion.php';
include_once __DIR__ . '/../persistencia/dReportes.php';
include_once __DIR__ . '/../persistencia/dDashboard.php';

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$anioActual = (int)date('Y');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : $anioActual;
if ($anio < 2020 || $anio > $anioActual + 1) {
    $anio = $anioActual;
}

// CEPLAN (matriz por categoría y mes) + KPIs + listados detallados
$datosCeplan = reporteCEPLAN($pdo, ['anio' => $anio]);
$kpis        = contarExpedientesPorAreaAnio($pdo, $anio);
$detalleUFREMID  = listarExpedientesDetalle($pdo, 'UFREMID',  $anio);
$detalleUFRESA   = listarExpedientesDetalle($pdo, 'UFRESA',   $anio);
$detalleUFRESBIT = listarExpedientesDetalle($pdo, 'UFRESBIT', $anio);

$totalGeneral = $kpis['UFREMID'] + $kpis['UFRESA'] + $kpis['UFRESBIT'];
$meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Público - SGRS</title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { background-color: #f0f4fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-header {
            background: linear-gradient(135deg, #0b2a4a 0%, #1b4f8b 100%);
            color: white; padding: 30px 0 25px;
            border-radius: 0 0 40px 40px; margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .page-header h2 { font-weight: 700; margin: 0; }
        .page-header p { opacity: 0.85; margin: 5px 0 0; }
        .kpi-card {
            border: none; border-radius: 20px; background: #ffffff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            padding: 20px; text-align: center; height: 100%;
        }
        .kpi-icon { font-size: 2rem; color: #1b4f8b; margin-bottom: 8px; }
        .kpi-num { font-size: 2.2rem; font-weight: 700; color: #0b2a4a; line-height: 1; }
        .kpi-lbl { color: #6f85a3; font-weight: 500; font-size: 0.85rem; margin-top: 6px; }
        .card-modern {
            border: none; border-radius: 20px; background: #ffffff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06); padding: 20px;
        }
        .nav-tabs .nav-link {
            color: #0b2a4a; font-weight: 600;
            border-radius: 12px 12px 0 0;
        }
        .nav-tabs .nav-link.active {
            background: #1b4f8b; color: white; border-color: #1b4f8b;
        }
        .table-modern { border-radius: 12px; overflow-x: auto; }
        .table-modern table { min-width: 900px; width: 100%; margin-bottom: 0; }
        .table-modern thead { background: #0b2a4a; color: white; }
        .table-modern th {
            font-weight: 600; text-transform: uppercase; font-size: 0.7rem;
            letter-spacing: 0.5px; white-space: nowrap; padding: 10px 8px;
        }
        .table-modern td { vertical-align: middle; padding: 8px 8px; font-size: 0.88rem; }
        .badge-estado { padding: 4px 12px; border-radius: 30px; font-weight: 600; font-size: 0.7rem; }
        .badge-proceso { background: #ffc107; color: #212529; }
        .badge-cerrado { background: #28a745; color: white; }
        .badge-archivado { background: #6c757d; color: white; }
        .chart-wrap { position: relative; height: 380px; margin-top: 20px; }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-chart-line me-2"></i>Dashboard Público de Inspecciones</h2>
                <p><i class="fas fa-info-circle me-1"></i>Sub Gerencia de Regulación Sectorial - Año <?= htmlspecialchars($anio) ?></p>
            </div>
            <form method="GET" class="d-flex align-items-center gap-2">
                <label class="mb-0 text-white"><i class="fas fa-calendar-alt me-1"></i>Año:</label>
                <select name="anio" class="form-select" onchange="this.form.submit()" style="max-width: 120px;">
                    <?php for ($a = $anioActual; $a >= $anioActual - 5; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === $anio ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>
</div>

<div class="container">

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-file-alt"></i></div>
                <div class="kpi-num"><?= number_format($totalGeneral) ?></div>
                <div class="kpi-lbl">Total Expedientes</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-pills"></i></div>
                <div class="kpi-num"><?= number_format($kpis['UFREMID']) ?></div>
                <div class="kpi-lbl">UFREMID</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-leaf"></i></div>
                <div class="kpi-num"><?= number_format($kpis['UFRESA']) ?></div>
                <div class="kpi-lbl">UFRESA</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-microscope"></i></div>
                <div class="kpi-num"><?= number_format($kpis['UFRESBIT']) ?></div>
                <div class="kpi-lbl">UFRESBIT</div>
            </div>
        </div>
    </div>

    <!-- Tabs por área -->
    <div class="card-modern">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ufremid" type="button" role="tab">
                    <i class="fas fa-pills me-1"></i> UFREMID
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ufresa" type="button" role="tab">
                    <i class="fas fa-leaf me-1"></i> UFRESA
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ufresbit" type="button" role="tab">
                    <i class="fas fa-microscope me-1"></i> UFRESBIT
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <?php
            $areasTab = [
                'UFREMID'  => ['id' => 'tab-ufremid',  'detalle' => $detalleUFREMID],
                'UFRESA'   => ['id' => 'tab-ufresa',   'detalle' => $detalleUFRESA],
                'UFRESBIT' => ['id' => 'tab-ufresbit', 'detalle' => $detalleUFRESBIT],
            ];
            $first = true;
            foreach ($areasTab as $areaNombre => $tab):
                $info   = $datosCeplan['areas'][$areaNombre] ?? ['categorias'=>[], 'matriz'=>[], 'actividades'=>[]];
                $activo = $first ? 'show active' : '';
                $first  = false;

                // Preparar datos para el gráfico (agrupado por actividad para UFREMID/UFRESA)
                $tieneActividad = in_array($areaNombre, ['UFREMID','UFRESA']);
                $series = [];
                if ($tieneActividad) {
                    foreach ($info['categorias'] as $cat) {
                        $act = $info['actividades'][$cat] ?? 'Sin actividad';
                        if ($act === '') $act = 'Sin actividad';
                        if (!isset($series[$act])) $series[$act] = array_fill(1, 12, 0);
                        for ($m = 1; $m <= 12; $m++) {
                            $series[$act][$m] += $info['matriz'][$cat][$m] ?? 0;
                        }
                    }
                } else {
                    foreach ($info['categorias'] as $cat) {
                        $series[$cat] = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $series[$cat][$m] = $info['matriz'][$cat][$m] ?? 0;
                        }
                    }
                }
                ksort($series);
            ?>
            <div class="tab-pane fade <?= $activo ?>" id="<?= $tab['id'] ?>" role="tabpanel">

                <!-- Gráfico -->
                <h5 class="fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-chart-bar me-2"></i>
                    Expedientes por <?= $tieneActividad ? 'Actividad Operativa' : 'Categoría' ?> y mes
                </h5>
                <div class="chart-wrap">
                    <canvas id="chart_<?= $areaNombre ?>"></canvas>
                </div>

                <!-- Tabla detallada -->
                <h5 class="fw-bold mt-5 mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-list me-2"></i>Detalle de Expedientes (<?= count($tab['detalle']) ?>)
                </h5>
                <div class="table-responsive table-modern">
                    <table class="table table-hover table-striped tabla-detalle" style="width:100%">
                        <thead>
                            <tr>
                                <th>Acta</th>
                                <th>Fecha</th>
                                <th>Sede</th>
                                <th>RUC</th>
                                <th>Razón Social</th>
                                <th><?= $areaNombre === 'UFRESBIT' ? 'Clasificación' : 'Categoría' ?></th>
                                <th>Actividad Operativa</th>
                                <th>Inspector</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tab['detalle'] as $d):
                                $estado = $d['estadoExpediente'] ?? '';
                                $bc = 'badge-proceso';
                                if ($estado === 'CERRADO') $bc = 'badge-cerrado';
                                elseif ($estado === 'ARCHIVADO') $bc = 'badge-archivado';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($d['numeroActa'] ?? '') ?></strong></td>
                                <td><?= htmlspecialchars($d['fechaInspeccion'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['sedeNombre'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['ruc'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['razonSocial'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['categoria'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['actividadOperativa'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['responsable'] ?? '') ?></td>
                                <td><span class="badge-estado <?= $bc ?>"><?= htmlspecialchars($estado) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
            window.chartsData = window.chartsData || {};
            window.chartsData['<?= $areaNombre ?>'] = {
                labels: <?= json_encode($meses) ?>,
                series: <?= json_encode($series) ?>
            };
            </script>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<footer class="footer-custom mt-5" style="background:#0b2a4a; color: rgba(255,255,255,0.7); padding: 20px 0; border-radius: 40px 40px 0 0; text-align: center;">
    <div class="container">
        <p class="mb-0">&copy; <?= date('Y') ?> Sub Gerencia de Regulación Sectorial - Todos los derechos reservados.</p>
    </div>
</footer>

<?php include 'boostrap-js.php'; ?>
<?php include 'datatable-js.php'; ?>

<script>
$(document).ready(function () {
    // DataTables para cada tabla de detalle
    $('.tabla-detalle').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
        responsive: true,
        order: [[1, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']]
    });

    // Colores para las series (paleta variada)
    const palette = [
        '#1b4f8b', '#2c5f9e', '#4a7fc4', '#6fa3e0', '#a3c5ec',
        '#c9a227', '#dab949', '#8a6d1a', '#28a745', '#5fbf7d',
        '#dc3545', '#e77f88', '#6c757d', '#9aa7bd', '#17a2b8'
    ];

    // Construir los gráficos (Chart.js)
    Object.keys(window.chartsData).forEach(function (area) {
        const info = window.chartsData[area];
        const ctx = document.getElementById('chart_' + area);
        if (!ctx) return;

        const datasets = Object.keys(info.series).map(function (nombre, i) {
            return {
                label: nombre,
                data: Object.values(info.series[nombre]),
                backgroundColor: palette[i % palette.length],
                borderColor: palette[i % palette.length],
                borderWidth: 1
            };
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: info.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    });
});
</script>
</body>
</html>