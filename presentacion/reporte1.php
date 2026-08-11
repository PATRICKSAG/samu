<?php
include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../persistencia/conexion.php';
include_once __DIR__ . '/../persistencia/dReportes.php';
include_once __DIR__ . '/../persistencia/dSede.php';
include_once __DIR__ . '/../persistencia/dDepartamento.php';
include_once __DIR__ . '/../persistencia/dProvincia.php';
include_once __DIR__ . '/../persistencia/dDistrito.php';
include_once __DIR__ . '/../persistencia/dExpediente.php';
include_once __DIR__ . '/auth_check.php';

// ============================================
// IMPORTAR CLASES DE PHPSPREADSHEET (GLOBAL)
// ============================================
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$pdo = Database::getConexion();

// Obtener listas para filtros
$departamentos = listarDepartamentos($pdo);
$provincias = [];
$distritos = [];
$equiposDCVS = listarEquiposDCVS($pdo);
$areas = ['UFREMID', 'UFRESA', 'UFRESBIT'];
$estadosExpediente = ['EN PROCESO', 'CERRADO', 'ARCHIVADO', 'ENVIADO AL EJECUTOR'];
$eventosPlazos = ['DESCARGO_ACTA', 'DESCARGO_PAS', 'CADUCIDAD_PAS', 'DESCARGO_IFI', 'RECURSO_SANCION', 'CUMPLIMIENTO_CONSENTIDA'];
$cumplimientos = ['SI', 'NO', 'N.A.'];

// ============================================
// PROCESAR EXPORTACIÓN
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exportar'])) {
    // Limpiar cualquier salida previa
    if (ob_get_level()) ob_end_clean();

    $reporte = $_POST['reporte'] ?? '';
    $filtros = $_POST['filtros'] ?? [];

    try {
        $data = [];
        $titulo = '';
        switch ($reporte) {
            case 'general':
                $data = reporteExpedientesGeneral($pdo, $filtros);
                $titulo = 'Reporte_General_Expedientes';
                break;
            case 'plazos':
                $data = reportePlazosCriticos($pdo, $filtros);
                $titulo = 'Reporte_Plazos_Criticos';
                break;
            case 'digemid':
                $data = reporteDigemid($pdo, $filtros);
                $titulo = 'Reporte_Cumplimiento_DIGEMID';
                break;
            case 'sedes':
                $data = reporteSedes($pdo, $filtros);
                $titulo = 'Reporte_Sedes_Ubicacion';
                break;
            default:
                throw new Exception('Reporte no válido');
        }

        if (empty($data)) {
            throw new Exception('No hay datos para exportar con los filtros seleccionados.');
        }

        // Cargar PhpSpreadsheet (autoload)
        require_once __DIR__ . '/../vendor/autoload.php';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        // Obtener headers y limpiar nombres de columnas
        $headers = array_keys($data[0]);
        $cleanHeaders = [];
        foreach ($headers as $h) {
            $h = str_replace(['á','é','í','ó','ú','ñ','Ñ'], ['a','e','i','o','u','n','N'], $h);
            $h = preg_replace('/[^a-zA-Z0-9_ ]/', '', $h);
            $cleanHeaders[] = ucfirst(str_replace('_', ' ', $h));
        }

        // Escribir encabezados
        $col = 'A';
        foreach ($cleanHeaders as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $lastCol = chr(ord('A') + count($cleanHeaders) - 1);

        // Estilo de encabezado
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B4F8B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Datos
        $row = 2;
        foreach ($data as $item) {
            $col = 'A';
            foreach ($item as $value) {
                if ($value instanceof DateTime) {
                    $value = $value->format('Y-m-d');
                } elseif (is_bool($value)) {
                    $value = $value ? 'SI' : 'NO';
                } elseif (is_null($value)) {
                    $value = '';
                }
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Autoajustar columnas
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Aplicar bordes
        $sheet->getStyle("A1:{$lastCol}" . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Insertar título en la primera fila
        $sheet->insertNewRowBefore(1);
        $sheet->setCellValue('A1', str_replace('_', ' ', $titulo));
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Configurar respuesta HTTP
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $titulo . '_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    } catch (Exception $e) {
        $mensajeError = $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode($mensajeError));
        exit;
    }
}

// ============================================
// HTML DE LA PÁGINA
// ============================================
$mensaje = $_GET['mensaje'] ?? '';
$mensajeError = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - SAMU</title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <?php include 'select2-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            background-color: #f0f4fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-wrapper {
            flex: 1 0 auto;
        }
        .footer-custom {
            flex-shrink: 0;
            background: #0b2a4a;
            color: rgba(255,255,255,0.7);
            padding: 20px 0;
            border-radius: 40px 40px 0 0;
            text-align: center;
            font-size: 0.9rem;
            margin-top: 40px;
        }
        .footer-custom a {
            color: white;
            text-decoration: none;
        }
        .footer-custom a:hover {
            text-decoration: underline;
        }
        .page-header {
            background: linear-gradient(135deg, #0b2a4a 0%, #1b4f8b 100%);
            color: white;
            padding: 30px 0 25px;
            border-radius: 0 0 40px 40px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .page-header h2 {
            font-weight: 700;
            margin: 0;
        }
        .page-header p {
            opacity: 0.85;
            margin: 5px 0 0;
        }
        .card-modern {
            border: none;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: transform 0.2s;
        }
        .card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }
        .btn-primary-custom {
            background: #1b4f8b;
            border: none;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            color: white;
            transition: 0.25s;
        }
        .btn-primary-custom:hover {
            background: #0f3b6b;
            transform: scale(1.02);
        }
        .btn-outline-secondary-custom {
            border: 2px solid #6c757d;
            color: #6c757d;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            transition: 0.25s;
            background: transparent;
        }
        .btn-outline-secondary-custom:hover {
            background: #6c757d;
            color: white;
        }
        .nav-tabs .nav-link {
            color: #0b2a4a;
            font-weight: 500;
            border-radius: 12px 12px 0 0;
        }
        .nav-tabs .nav-link.active {
            background: #1b4f8b;
            color: white;
            border-color: #1b4f8b;
        }
        .nav-tabs .nav-link:hover {
            background: #eaf3ff;
            border-color: #dce3ed;
        }
        .form-control-modern {
            border-radius: 12px;
            border: 1px solid #dce3ed;
            padding: 10px 15px;
            transition: 0.2s;
        }
        .form-control-modern:focus {
            border-color: #1b4f8b;
            box-shadow: 0 0 0 3px rgba(27,79,139,0.15);
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        .filtro-row {
            background: #f8faff;
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .page-header { padding: 20px 0; }
            .btn-primary-custom, .btn-outline-secondary-custom { width: 100%; margin-bottom: 5px; }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="page-wrapper">
        <div class="page-header">
            <div class="container">
                <h2><i class="fas fa-chart-bar me-2"></i>Reportes Exportables</h2>
                <p>Selecciona un reporte, aplica filtros y exporta a Excel</p>
            </div>
        </div>

        <div class="container">
            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($mensajeError): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($mensajeError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-modern">
                <div class="card-body">
                    <!-- Pestañas -->
                    <ul class="nav nav-tabs" id="reporteTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-general" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-plazos" data-bs-toggle="tab" data-bs-target="#plazos" type="button" role="tab">Plazos Críticos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-digemid" data-bs-toggle="tab" data-bs-target="#digemid" type="button" role="tab">DIGEMID</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-sedes" data-bs-toggle="tab" data-bs-target="#sedes" type="button" role="tab">Sedes</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- REPORTE GENERAL -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="reporte" value="general">
                                <div class="filtro-row">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Área</label>
                                            <select name="filtros[area]" class="form-select form-control-modern">
                                                <option value="">Todas</option>
                                                <?php foreach ($areas as $area): ?>
                                                    <option value="<?= $area ?>"><?= $area ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Estado</label>
                                            <select name="filtros[estado]" class="form-select form-control-modern">
                                                <option value="">Todos</option>
                                                <?php foreach ($estadosExpediente as $est): ?>
                                                    <option value="<?= $est ?>"><?= $est ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha desde</label>
                                            <input type="date" name="filtros[fecha_desde]" class="form-control form-control-modern">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha hasta</label>
                                            <input type="date" name="filtros[fecha_hasta]" class="form-control form-control-modern">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="exportar" class="btn btn-primary-custom">
                                    <i class="fas fa-file-excel me-2"></i> Exportar a Excel
                                </button>
                            </form>
                        </div>

                        <!-- REPORTE PLAZOS CRÍTICOS -->
                        <div class="tab-pane fade" id="plazos" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="reporte" value="plazos">
                                <div class="filtro-row">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Área</label>
                                            <select name="filtros[area]" class="form-select form-control-modern">
                                                <option value="">Todas</option>
                                                <?php foreach ($areas as $area): ?>
                                                    <option value="<?= $area ?>"><?= $area ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Evento</label>
                                            <select name="filtros[evento]" class="form-select form-control-modern">
                                                <option value="">Todos</option>
                                                <?php foreach ($eventosPlazos as $ev): ?>
                                                    <option value="<?= $ev ?>"><?= $ev ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="exportar" class="btn btn-primary-custom">
                                    <i class="fas fa-file-excel me-2"></i> Exportar a Excel
                                </button>
                            </form>
                        </div>

                        <!-- REPORTE DIGEMID -->
                        <div class="tab-pane fade" id="digemid" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="reporte" value="digemid">
                                <div class="filtro-row">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Equipo DCVS</label>
                                            <select name="filtros[equipo]" class="form-select form-control-modern">
                                                <option value="">Todos</option>
                                                <?php foreach ($equiposDCVS as $eq): ?>
                                                    <option value="<?= $eq['idEquipo'] ?>"><?= htmlspecialchars($eq['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Cumplimiento BPOF</label>
                                            <select name="filtros[cumplimiento]" class="form-select form-control-modern">
                                                <option value="">Todos</option>
                                                <?php foreach ($cumplimientos as $val): ?>
                                                    <option value="<?= $val ?>"><?= $val ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha desde</label>
                                            <input type="date" name="filtros[fecha_desde]" class="form-control form-control-modern">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha hasta</label>
                                            <input type="date" name="filtros[fecha_hasta]" class="form-control form-control-modern">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="exportar" class="btn btn-primary-custom">
                                    <i class="fas fa-file-excel me-2"></i> Exportar a Excel
                                </button>
                            </form>
                        </div>

                        <!-- REPORTE SEDES -->
                        <div class="tab-pane fade" id="sedes" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="reporte" value="sedes">
                                <div class="filtro-row">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Departamento</label>
                                            <select name="filtros[departamento]" id="filtroDepartamento" class="form-select form-control-modern select2-auto">
                                                <option value="">Todos</option>
                                                <?php foreach ($departamentos as $dep): ?>
                                                    <option value="<?= $dep['idDepartamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Provincia</label>
                                            <select name="filtros[provincia]" id="filtroProvincia" class="form-select form-control-modern select2-auto">
                                                <option value="">Todos</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Distrito</label>
                                            <select name="filtros[distrito]" id="filtroDistrito" class="form-select form-control-modern select2-auto">
                                                <option value="">Todos</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Área</label>
                                            <select name="filtros[area]" class="form-select form-control-modern">
                                                <option value="">Todas</option>
                                                <?php foreach ($areas as $area): ?>
                                                    <option value="<?= $area ?>"><?= $area ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="exportar" class="btn btn-primary-custom">
                                    <i class="fas fa-file-excel me-2"></i> Exportar a Excel
                                </button>
                            </form>
                        </div>
                    </div>
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
            if ($.fn.select2) {
                $('.select2-auto').select2({
                    width: '100%',
                    placeholder: 'Buscar...',
                    allowClear: true
                });
            }

            $('#filtroDepartamento').change(function() {
                const id = $(this).val();
                const url = '../persistencia/dProvincia.php';
                if (id) {
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: { idDepartamento: id },
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">Todos</option>';
                            $.each(data, function(i, item) {
                                options += `<option value="${item.idProvincia}">${item.nombre}</option>`;
                            });
                            $('#filtroProvincia').html(options).trigger('change');
                        }
                    });
                } else {
                    $('#filtroProvincia').html('<option value="">Todos</option>').trigger('change');
                }
            });

            $('#filtroProvincia').change(function() {
                const id = $(this).val();
                const url = '../persistencia/dDistrito.php';
                if (id) {
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: { idProvincia: id },
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">Todos</option>';
                            $.each(data, function(i, item) {
                                options += `<option value="${item.idDistrito}">${item.nombre}</option>`;
                            });
                            $('#filtroDistrito').html(options).trigger('change');
                        }
                    });
                } else {
                    $('#filtroDistrito').html('<option value="">Todos</option>').trigger('change');
                }
            });
        });
    </script>
</body>
</html>