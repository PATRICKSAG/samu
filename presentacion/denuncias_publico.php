<?php
// presentacion/denuncias_publico.php
include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../persistencia/conexion.php';
include_once __DIR__ . '/../persistencia/dDenuncias.php';

$mensaje      = '';
$mensajeError = '';
$data = [
    'anonimo'         => 0,
    'nombre'          => '',
    'correo'          => '',
    'inspector'       => '',
    'establecimiento' => '',
    'area'            => '',
    'motivo'          => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnEnviarDenuncia'])) {
    $data = [
        'anonimo'         => isset($_POST['anonimo']) ? 1 : 0,
        'nombre'          => trim($_POST['nombre'] ?? ''),
        'correo'          => trim($_POST['correo'] ?? ''),
        'inspector'       => trim($_POST['inspector'] ?? ''),
        'establecimiento' => trim($_POST['establecimiento'] ?? ''),
        'area'            => trim($_POST['area'] ?? ''),
        'motivo'          => trim($_POST['motivo'] ?? ''),
    ];

    $errores = [];
    if (empty($data['establecimiento'])) $errores[] = 'El nombre del establecimiento es requerido.';
    if (empty($data['area']))            $errores[] = 'El área es requerida.';
    if (empty($data['motivo']))          $errores[] = 'La descripción de los hechos es requerida.';
    if (!$data['anonimo'] && empty($data['nombre'])) $errores[] = 'Si no es anónima, indique su nombre.';

    if (empty($errores)) {
        try {
            $archivo = $_FILES['evidencia'] ?? null;
            registrarDenuncia($data, $archivo);
            $mensaje = 'Su denuncia ha sido enviada correctamente. Gracias por su colaboración.';

            // Limpiar el formulario tras el envío
            $data = [
                'anonimo'         => 0,
                'nombre'          => '',
                'correo'          => '',
                'inspector'       => '',
                'establecimiento' => '',
                'area'            => '',
                'motivo'          => '',
            ];
        } catch (Exception $e) {
            $mensajeError = $e->getMessage();
        }
    } else {
        $mensajeError = implode('<br>', $errores);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canal de Denuncias - SGRS</title>
    <?php include 'boostrap-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color: #f0f4fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .page-header {
            background: linear-gradient(135deg, #0b2a4a 0%, #1b4f8b 100%);
            color: white;
            padding: 30px 0 25px;
            border-radius: 0 0 40px 40px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .page-header h2 { font-weight: 700; margin: 0; }
        .page-header p { opacity: 0.85; margin: 5px 0 0; }

        .card-modern {
            border: none;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            padding: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
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

        .btn-primary-custom {
            background: #1b4f8b;
            border: none;
            border-radius: 50px;
            padding: 12px 40px;
            font-weight: 600;
            color: white;
            transition: 0.25s;
        }
        .btn-primary-custom:hover { background: #0f3b6b; transform: scale(1.02); }

        .btn-outline-custom {
            border: 2px solid #6c757d;
            color: #6c757d;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            background: transparent;
            text-decoration: none;
            display: inline-block;
        }
        .btn-outline-custom:hover {
            background: #6c757d;
            color: white;
        }

        .info-banner {
            background: #eaf3ff;
            border-left: 5px solid #1b4f8b;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
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
        .footer-custom a { color: white; text-decoration: none; }
        .footer-custom a:hover { text-decoration: underline; }

        .form-check-input:checked {
            background-color: #1b4f8b;
            border-color: #1b4f8b;
        }
    </style>
</head>
<body>

    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-bullhorn me-2"></i>Canal de Denuncias</h2>
            <p><i class="fas fa-info-circle me-1"></i>Reporte presuntos actos de corrupción por parte de los inspectores. Su denuncia es confidencial.</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="card-modern">

            <div class="info-banner">
                <i class="fas fa-shield-halved me-2" style="color: #1b4f8b;"></i>
                <strong>Confidencialidad garantizada.</strong>
                Puede registrar su denuncia de forma <strong>anónima</strong> o identificándose.
                Su información será tratada con estricta reserva.
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($mensajeError): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $mensajeError ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="formDenuncia">

                <div class="row g-3">
                    <!-- Anónima -->
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="anonimo" id="anonimo" value="1"
                                <?= !empty($data['anonimo']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="anonimo">
                                <i class="fas fa-user-secret me-1" style="color: #1b4f8b;"></i>
                                Deseo que mi denuncia sea anónima
                            </label>
                        </div>
                    </div>

                    <!-- Nombre / Correo -->
                    <div class="col-md-6 campo-identidad">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control form-control-modern" name="nombre" id="nombre"
                               value="<?= htmlspecialchars($data['nombre'] ?? '') ?>" placeholder="Su nombre">
                    </div>
                    <div class="col-md-6 campo-identidad">
                        <label for="correo" class="form-label">Correo de contacto (opcional)</label>
                        <input type="email" class="form-control form-control-modern" name="correo" id="correo"
                               value="<?= htmlspecialchars($data['correo'] ?? '') ?>" placeholder="ejemplo@correo.com">
                    </div>

                    <!-- Inspector denunciado -->
                    <div class="col-md-6">
                        <label for="inspector" class="form-label">Nombre del inspector (si lo conoce)</label>
                        <input type="text" class="form-control form-control-modern" name="inspector" id="inspector"
                               value="<?= htmlspecialchars($data['inspector'] ?? '') ?>" placeholder="Nombre del inspector">
                    </div>

                    <!-- Establecimiento -->
                    <div class="col-md-6">
                        <label for="establecimiento" class="form-label">
                            Nombre del establecimiento <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-modern" name="establecimiento" id="establecimiento"
                               value="<?= htmlspecialchars($data['establecimiento'] ?? '') ?>"
                               placeholder="Nombre del establecimiento inspeccionado" required>
                    </div>

                    <!-- Área -->
                    <div class="col-md-6">
                        <label for="area" class="form-label">
                            Área que lo inspeccionó <span class="text-danger">*</span>
                        </label>
                        <select name="area" id="area" class="form-select form-control-modern" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="UFRESA"   <?= ($data['area'] ?? '') === 'UFRESA'   ? 'selected' : '' ?>>UFRESA</option>
                            <option value="UFREMID"  <?= ($data['area'] ?? '') === 'UFREMID'  ? 'selected' : '' ?>>UFREMID</option>
                            <option value="UFRESBIT" <?= ($data['area'] ?? '') === 'UFRESBIT' ? 'selected' : '' ?>>UFRESBIT</option>
                        </select>
                    </div>

                    <!-- Motivo -->
                    <div class="col-12">
                        <label for="motivo" class="form-label">
                            Descripción de los hechos <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-modern" name="motivo" id="motivo" rows="6"
                            placeholder="Describa lo sucedido con el mayor detalle posible: fecha, lugar, personas involucradas, etc."
                            required><?= htmlspecialchars($data['motivo'] ?? '') ?></textarea>
                    </div>

                    <!-- Evidencia -->
                    <div class="col-12">
                        <label for="evidencia" class="form-label">Adjuntar evidencia (opcional)</label>
                        <input type="file" class="form-control form-control-modern" name="evidencia" id="evidencia"
                               accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <small class="text-muted">
                            Formatos aceptados: JPG, PNG, PDF, DOC, DOCX, XLS, XLSX, TXT. Tamaño máximo 5 MB.
                        </small>
                    </div>
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" name="btnEnviarDenuncia" class="btn btn-primary-custom">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Denuncia
                    </button>
                    <a href="login.php" class="btn btn-outline-custom">
                        <i class="fas fa-arrow-left me-2"></i>Volver al inicio
                    </a>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer-custom">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Sub Gerencia de Regulación Sectorial - Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?php include 'boostrap-js.php'; ?>

    <script>
        $(document).ready(function () {
            function toggleIdentidad() {
                const esAnonimo = $('#anonimo').is(':checked');

                if (esAnonimo) {
                    $('.campo-identidad').stop(true, true).slideUp(200, function() {
                        $('#nombre, #correo').val('');
                    });
                    $('#nombre').removeAttr('required').prop('required', false);
                } else {
                    $('.campo-identidad').stop(true, true).slideDown(200);
                    $('#nombre').attr('required', 'required').prop('required', true);
                }
            }

            $('#anonimo').on('change', toggleIdentidad);

            if ($('#anonimo').is(':checked')) {
                $('.campo-identidad').hide();
                $('#nombre').removeAttr('required');
            } else {
                $('#nombre').attr('required', 'required');
            }

            // Validar tamaño del archivo adjunto (5 MB)
            $('#evidencia').on('change', function () {
                const f = this.files[0];
                if (f && f.size > 5 * 1024 * 1024) {
                    alert('El archivo supera los 5 MB. Por favor seleccione uno más pequeño.');
                    $(this).val('');
                }
            });
        });
    </script>
</body>
</html>