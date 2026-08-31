<?php
require_once __DIR__ . '/../config.php';
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dExpediente.php');
include_once(__DIR__ . '/auth_check.php');

$pdo = Database::getConexion();

// Obtener filtros
$area = isset($_GET['area']) ? $_GET['area'] : null;
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;

// Obtener todas las alertas (sin límite)
$alertas = obtenerPlazosCriticos($pdo, 0, $area, $estado);

// Obtener listado de áreas para el filtro
$areas = ['UFREMID', 'UFRESA', 'UFRESBIT'];
$estadosFiltro = ['VENCIDO', 'PROXIMO_VENCER'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas - Sub Gerencia de Regulación Sectorial</title>
    <?php include 'boostrap-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f0f4fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        .table-modern {
            border-radius: 16px;
            overflow-x: auto !important;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
        }
        .table-modern table {
            min-width: 800px;
            width: 100%;
            margin-bottom: 0;
        }
        .table-modern thead {
            background: #0b2a4a;
            color: white;
        }
        .table-modern th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            padding: 12px 10px;
        }
        .table-modern td {
            vertical-align: middle;
            padding: 10px 8px;
        }
        .badge-estado {
            padding: 4px 14px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge-vencido {
            background: #dc3545;
            color: white;
        }
        .badge-proximo {
            background: #ffc107;
            color: #212529;
        }
        .badge-area {
            background: #eaf3ff;
            color: #1b4f8b;
            padding: 4px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .filtros-form {
            background: #f8faff;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
        }
        .filtros-form .form-label {
            font-weight: 500;
            color: #0b2a4a;
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
        .footer-custom {
            background: #0b2a4a;
            color: rgba(255,255,255,0.75);
            padding: 25px 0;
            border-radius: 60px 60px 0 0;
            margin-top: 50px;
        }
        .footer-custom a {
            color: white;
            text-decoration: none;
        }
        .footer-custom a:hover {
            text-decoration: underline;
        }
        .link-acta {
            color: #0b2a4a;
            font-weight: 600;
            text-decoration: none;
        }
        .link-acta:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .page-header { padding: 20px 0; }
            .filtros-form .row > div { margin-bottom: 10px; }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-bell me-2"></i>Alertas de Plazos</h2>
            <p><i class="fas fa-clock me-1"></i>Listado completo de alertas vencidas y próximas a vencer</p>
        </div>
    </div>

    <div class="container">

        <!-- Filtros -->
        <div class="filtros-form">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="area" class="form-label"><i class="fas fa-tag me-1"></i>Área</label>
                    <select name="area" id="area" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a ?>" <?= ($area == $a) ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label"><i class="fas fa-circle me-1"></i>Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="VENCIDO" <?= ($estado == 'VENCIDO') ? 'selected' : '' ?>>Vencido</option>
                        <option value="PROXIMO_VENCER" <?= ($estado == 'PROXIMO_VENCER') ? 'selected' : '' ?>>Próximo a vencer</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom w-100">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                    <a href="alertas.php" class="btn btn-outline-secondary-custom w-100">
                        <i class="fas fa-undo me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de alertas -->
        <div class="card card-modern">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <h5 class="card-title fw-bold" style="color: #0b2a4a;">
                        <i class="fas fa-list me-2"></i>Alertas Pendientes
                        <span class="badge bg-primary ms-2"><?= $alertas['total'] ?></span>
                    </h5>
                </div>
                
<?php if (empty($alertas['lista'])): ?>
    <!-- No mostrar tabla, solo un mensaje -->
    <div class="alert alert-info text-center py-4">
        <i class="fas fa-check-circle fa-2x text-success d-block mb-2"></i>
        No hay alertas pendientes que coincidan con los filtros.
    </div>
<?php else: ?>
    <div class="table-responsive table-modern">
        <table id="tablaAlertas" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Acta</th>
                    <th>Área</th>
                    <th>Evento</th>
                    <th>Fecha Vencimiento</th>
                    <th>Días</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas['lista'] as $index => $alerta): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <a href="seguimiento.php?id=<?= $alerta['idExpediente'] ?>" class="link-acta">
                                Acta <?= htmlspecialchars($alerta['numeroActa']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge-area"><?= htmlspecialchars($alerta['areaOrigen']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($alerta['evento']) ?></td>
                        <td><?= date('d/m/Y', strtotime($alerta['fechaVencimiento'])) ?></td>
                        <td><?= $alerta['dias'] ?></td>
                        <td>
                            <span class="badge-estado <?= $alerta['estado'] == 'VENCIDO' ? 'badge-vencido' : 'badge-proximo' ?>">
                                <?= $alerta['estado'] == 'VENCIDO' ? 'VENCIDO' : 'PRÓXIMO' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($alerta['responsable']) ?></td>
                        <td>
                            <a href="seguimiento.php?id=<?= $alerta['idExpediente'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        $(document).ready(function() {
            $('#tablaAlertas').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true,
                order: [[4, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
            });
        });
    </script>
<?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom text-center">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Sub Gerencia de Regulación Sectorial - Todos los derechos reservados.</p>
            <small>Gestor de alertas de plazos.</small>
        </div>
    </footer>

    <?php include 'boostrap-js.php'; ?>
    <?php include 'datatable-js.php'; ?>

    <script>
        $(document).ready(function() {
            // Inicializar DataTable para ordenación y búsqueda (opcional)
            $('#tablaAlertas').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true,
                order: [[4, 'asc']], // ordenar por fecha de vencimiento
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
            });
        });
    </script>

</body>
</html>