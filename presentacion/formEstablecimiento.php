<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dEstablecimiento.php');

extract($_POST);

// VERIFICACIÓN DE SESIÓN (AGREGAR ESTO)
include_once(__DIR__ . '/auth_check.php');

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Valor por defecto para el checkbox
if (!isset($txtInformal)) {
    $txtInformal = 0;
}

$mensaje = '';
$mensajeError = '';

if (isset($_REQUEST['btnConsultar'])) {
    $valido = true;
    if (empty($txtRuc)) {
        $mensajeError = "El RUC es requerido.";
        $valido = false;
    }
    if (empty($txtRazonSocial)) {
        $mensajeError = "La razón social es requerida.";
        $valido = false;
    }

    if ($valido) {
        try {
            if (!empty($txtIdEstablecimiento)) {
                actualizarEstablecimiento($pdo, [
                    'idEstablecimiento' => $txtIdEstablecimiento,
                    'ruc' => $txtRuc,
                    'razonSocial' => $txtRazonSocial,
                    'responsableLegal' => $txtResponsableLegal,
                    'cargoRepresentanteLegal' => $txtCargoResponsableLegal,
                    'informal' => $txtInformal,
                ]);
                $mensaje = "Establecimiento actualizado correctamente.";
            } else {
                insertarEstablecimiento($pdo, [
                    'ruc' => $txtRuc,
                    'razonSocial' => $txtRazonSocial,
                    'responsableLegal' => $txtResponsableLegal,
                    'cargoRepresentanteLegal' => $txtCargoResponsableLegal,
                    'informal' => $txtInformal,
                ]);
                $mensaje = "Establecimiento creado correctamente.";
            }

            // Limpiar campos
            $txtIdEstablecimiento = "";
            $txtRuc = "";
            $txtRazonSocial = "";
            $txtResponsableLegal = "";
            $txtCargoResponsableLegal = "";
            $txtInformal = 0;

            // Recargar la lista
            $establecimientos = listarEstablecimientos($pdo);

        } catch (PDOException $e) {
            $mensajeError = "Error al guardar: " . $e->getMessage();
        }
    } else {
        // Si hay error, mantenemos los valores enviados para mostrarlos
    }
} else {
    // Inicializar variables
    $txtIdEstablecimiento = "";
    $txtRuc = "";
    $txtRazonSocial = "";
    $txtResponsableLegal = "";
    $txtCargoResponsableLegal = "";
    $txtInformal = 0;
}

// Obtener lista actualizada
$establecimientos = listarEstablecimientos($pdo);

// Si se solicitó editar, cargar datos vía GET (usamos el mismo archivo)
if (isset($_GET['editar'])) {
    $idEditar = intval($_GET['editar']);
    // Buscar el establecimiento en el array (o hacer consulta directa)
    foreach ($establecimientos as $est) {
        if ($est['idEstablecimiento'] == $idEditar) {
            $txtIdEstablecimiento = $est['idEstablecimiento'];
            $txtRuc = $est['ruc'];
            $txtRazonSocial = $est['razonSocial'];
            $txtResponsableLegal = $est['responsableLegal'];
            $txtCargoResponsableLegal = $est['cargoRepresentanteLegal'];
            $txtInformal = $est['informal'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Establecimientos</title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <!-- Font Awesome para iconos -->
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
        .table-modern {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
        }
        .table-modern thead {
            background: #0b2a4a;
            color: white;
        }
        .table-modern th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .table-modern td {
            vertical-align: middle;
        }
        .badge-informal {
            background: #ffc107;
            color: #212529;
            padding: 4px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge-formal {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .form-control-modern {
            border-radius: 12px;
            border: 1px solid #dce3ed;
            padding: 10px 15px;
            transition: 0.2s;
        }
        .form-control-modern:focus {
            border-color: #1b4f8b;
            box-shadow: 0 0 0 3px rgba(27, 79, 139, 0.15);
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        .footer-custom {
            background: #0b2a4a;
            color: rgba(255,255,255,0.7);
            padding: 20px 0;
            border-radius: 40px 40px 0 0;
            margin-top: 40px;
            text-align: center;
            font-size: 0.9rem;
        }
        .footer-custom a {
            color: white;
            text-decoration: none;
        }
        .footer-custom a:hover {
            text-decoration: underline;
        }
        .icon-input {
            background: #e9f0fc;
            padding: 0 15px;
            border-radius: 12px 0 0 12px;
            display: flex;
            align-items: center;
            color: #1b4f8b;
        }
        .input-group-custom {
            display: flex;
            align-items: stretch;
        }
        .input-group-custom .form-control {
            border-radius: 0 12px 12px 0;
            border-left: none;
        }
        .input-group-custom .icon-input {
            border: 1px solid #dce3ed;
            border-right: none;
        }
        @media (max-width: 768px) {
            .page-header {
                padding: 20px 0;
            }
            .btn-primary-custom, .btn-outline-secondary-custom {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Cabecera de página -->
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-store me-2"></i>Gestión de Establecimientos</h2>
            <p><i class="fas fa-edit me-1"></i>Registra, edita y administra los establecimientos fiscalizados</p>
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
                    <i class="fas fa-pen-alt me-2"></i><?= $txtIdEstablecimiento ? 'Editar Dueño' : 'Nuevo Dueño' ?>
                </h5>
                <form method="POST" action="">
                    <input type="hidden" name="txtIdEstablecimiento" id="txtIdEstablecimiento" value="<?= $txtIdEstablecimiento ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="txtRuc" class="form-label"><i class="fas fa-id-card me-1"></i>RUC <span class="text-danger">*</span></label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-hashtag"></i></span>
                                <input type="text" class="form-control form-control-modern" name="txtRuc" id="txtRuc" value="<?= $txtRuc ?>" placeholder="Ingrese RUC" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="txtRazonSocial" class="form-label"><i class="fas fa-building me-1"></i>Razón Social <span class="text-danger">*</span></label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-store-alt"></i></span>
                                <input type="text" class="form-control form-control-modern" name="txtRazonSocial" id="txtRazonSocial" value="<?= $txtRazonSocial ?>" placeholder="Nombre legal" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="txtResponsableLegal" class="form-label"><i class="fas fa-user-tie me-1"></i>Responsable Legal</label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control form-control-modern" name="txtResponsableLegal" id="txtResponsableLegal" value="<?= $txtResponsableLegal ?>" placeholder="Nombre del representante" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="txtCargoResponsableLegal" class="form-label"><i class="fas fa-briefcase me-1"></i>Cargo Responsable Legal</label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control form-control-modern" name="txtCargoResponsableLegal" id="txtCargoResponsableLegal" value="<?= $txtCargoResponsableLegal ?>" placeholder="Ej. Gerente General" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="txtInformal" id="txtInformal" value="1" <?= $txtInformal ? 'checked' : '' ?>>
                                <label class="form-check-label" for="txtInformal">
                                    <i class="fas fa-exclamation-triangle me-1" style="color: #ffc107;"></i> ¿Es informal?
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button type="submit" name="btnConsultar" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                        <button type="button" class="btn btn-outline-secondary-custom" onclick="cancelar();">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary-custom">
                            <i class="fas fa-plus me-2"></i>Nuevo
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado -->
        <div class="card card-modern">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-list me-2"></i>Establecimientos Registrados
                </h5>
                <div class="table-responsive table-modern">
                    <table id="example" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>RUC</th>
                                <th>Razón Social</th>
                                <th>Responsable Legal</th>
                                <th>Cargo R. Legal</th>
                                <th>Informal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($establecimientos as $establecimiento) { ?>
                                <tr>
                                    <td><?= $establecimiento['idEstablecimiento'] ?></td>
                                    <td><?= htmlspecialchars($establecimiento['ruc']) ?></td>
                                    <td><?= htmlspecialchars($establecimiento['razonSocial']) ?></td>
                                    <td><?= htmlspecialchars($establecimiento['responsableLegal'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($establecimiento['cargoRepresentanteLegal'] ?? '') ?></td>
                                    <td>
                                        <?php if ($establecimiento['informal']) { ?>
                                            <span class="badge-informal"><i class="fas fa-exclamation-circle me-1"></i>SI</span>
                                        <?php } else { ?>
                                            <span class="badge-formal"><i class="fas fa-check-circle me-1"></i>NO</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="?editar=<?= $establecimiento['idEstablecimiento'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="eliminar(<?= $establecimiento['idEstablecimiento'] ?>)">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
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

    <script>
        // DataTable
        $(document).ready(function() {
            $('#example').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                responsive: true,
                order: [[0, 'desc']]
            });
        });

        // Funciones
        const txtIdEstablecimiento = document.getElementById('txtIdEstablecimiento');
        const txtRuc = document.getElementById('txtRuc');
        const txtRazonSocial = document.getElementById('txtRazonSocial');
        const txtResponsableLegal = document.getElementById('txtResponsableLegal');
        const txtCargoResponsableLegal = document.getElementById('txtCargoResponsableLegal');
        const txtInformal = document.getElementById('txtInformal');

        const cancelar = () => {
            txtIdEstablecimiento.value = "";
            txtRuc.value = "";
            txtRazonSocial.value = "";
            txtResponsableLegal.value = "";
            txtCargoResponsableLegal.value = "";
            txtInformal.checked = false;
            window.location.href = '<?= $_SERVER['PHP_SELF'] ?>';
        };

        const eliminar = (id) => {
            if (confirm('¿Está seguro de eliminar este establecimiento?')) {
                window.location.href = 'eliminarEstablecimiento.php?id=' + id;
            }
        };
    </script>
</body>
</html>