<?php require_once __DIR__ . '/config.php';
include_once(__DIR__ . '/persistencia/conexion.php');
include_once(__DIR__ . '/persistencia/dExpediente.php');
// VERIFICACIÓN DE SESIÓN (AGREGAR ESTO)
include_once(__DIR__ . '/presentacion/auth_check.php');
$pdo = Database::getConexion();
$alertas = obtenerPlazosCriticos($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión - Sub Gerencia de Regulación Sectorial</title>
    <?php include 'presentacion/boostrap-css.php'; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... (todos los estilos anteriores de index.php) ... */
        body {
            background-color: #f0f4fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            position: relative;
            background: linear-gradient(135deg, #0b2a4a 0%, #1b4f8b 100%);
            color: white;
            padding: 90px 0 70px;
            margin-bottom: 50px;
            border-radius: 0 0 60px 60px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        #particle-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            mix-blend-mode: screen;
            opacity: 0.8;
        }
        #particle-canvas canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
            pointer-events: none !important;
        }
        #particle-canvas > div {
            background: transparent !important;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .hero-title {
            font-weight: 700;
            font-size: 3.5rem;
            text-shadow: 2px 4px 12px rgba(0,0,0,0.25);
            letter-spacing: -0.5px;
        }
        .hero-sub {
            font-weight: 300;
            font-size: 1.8rem;
            opacity: 0.9;
        }
        .hero-desc {
            font-size: 1.2rem;
            opacity: 0.85;
            max-width: 600px;
            margin: 20px auto 30px;
        }
        .btn-hero {
            background: white;
            color: #1b4f8b;
            border-radius: 50px;
            padding: 12px 40px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            display: inline-block;
            text-decoration: none;
        }
        .btn-hero:hover {
            background: #e8f0fe;
            transform: scale(1.05);
            box-shadow: 0 12px 28px rgba(0,0,0,0.25);
            color: #1b4f8b;
        }
        /* Panel de alertas */
        .alertas-panel {
            background: #ffffff;
            border-radius: 30px;
            padding: 20px 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            margin-bottom: 40px;
            border-left: 6px solid #1b4f8b;
        }
        .alertas-panel .alertas-count {
            font-size: 2rem;
            font-weight: 700;
            color: #0b2a4a;
            line-height: 1;
        }
        .alertas-panel .alertas-label {
            color: #6f85a3;
            font-weight: 500;
        }
        .alertas-panel .alerta-item {
            padding: 8px 12px;
            border-radius: 12px;
            background: #f8f9fa;
            margin-bottom: 6px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .alertas-panel .alerta-item:hover {
            background: #e9f0fc;
        }
        .alertas-panel .alerta-item .badge-estado {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 30px;
            margin-right: 10px;
        }
        .badge-vencido { background: #dc3545; color: white; }
        .badge-proximo { background: #ffc107; color: #212529; }
        .alertas-panel .alerta-item .acta-link {
            font-weight: 600;
            color: #0b2a4a;
            text-decoration: none;
        }
        .alertas-panel .alerta-item .acta-link:hover {
            text-decoration: underline;
        }
        .alertas-panel .alerta-item .text-muted {
            font-size: 0.85rem;
        }
        /* Tarjetas de módulos */
        .card-modern {
            border: none;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
            padding: 10px 5px;
        }
        .card-modern:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 45px rgba(0,0,0,0.12);
        }
        .card-icon {
            width: 80px;
            height: 80px;
            background: #e9f0fc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 2.6rem;
            color: #1b4f8b;
            transition: 0.3s;
        }
        .card-modern:hover .card-icon {
            background: #1b4f8b;
            color: white;
        }
        .card-title-modern {
            font-weight: 600;
            color: #0b2a4a;
            font-size: 1.4rem;
        }
        .card-text-modern {
            color: #5a6f8c;
            font-size: 0.95rem;
        }
        .btn-outline-primary-custom {
            border: 2px solid #1b4f8b;
            color: #1b4f8b;
            border-radius: 50px;
            padding: 8px 28px;
            font-weight: 500;
            transition: 0.25s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-outline-primary-custom:hover {
            background: #1b4f8b;
            color: white;
        }
        .btn-primary-custom {
            background: #1b4f8b;
            border: none;
            border-radius: 50px;
            padding: 8px 28px;
            font-weight: 500;
            color: white;
            transition: 0.25s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom:hover {
            background: #0f3b6b;
            transform: scale(1.02);
        }
        .badge-area {
            background: #eaf3ff;
            color: #1b4f8b;
            padding: 4px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .stats-section {
            background: white;
            border-radius: 30px;
            padding: 30px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
            margin-top: 40px;
        }
        .stat-number {
            font-weight: 700;
            font-size: 2.4rem;
            color: #0b2a4a;
        }
        .stat-label {
            color: #6f85a3;
            font-weight: 500;
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
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.4rem;
            }
            .hero-sub {
                font-size: 1.3rem;
            }
            .hero-section {
                padding: 50px 0 40px;
            }
            .alertas-panel .alerta-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .alertas-panel .alerta-item .badge-estado {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>

    <?php include 'presentacion/header.php'; ?>

    <!-- Hero con partículas -->
    <section class="hero-section">
        <div id="particle-canvas"></div>
        <div class="container hero-content">
            <h1 class="hero-title">Sistema de Gestión</h1>
            <p class="hero-sub">Sub Gerencia de Regulación Sectorial</p>
            <p class="hero-desc">
                Administración integral de establecimientos, sedes, expedientes y reportes de inspección.
            </p>
            <a href="#modulos" class="btn-hero">
                <i class="fas fa-arrow-down me-2"></i>Explorar Módulos
            </a>
        </div>
    </section>

    <div class="container">

        <!-- Panel de Alertas -->
        <div class="alertas-panel">
            <div class="row align-items-center">
                <div class="col-md-3 col-sm-12 mb-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bell text-primary me-3" style="font-size: 2.2rem;"></i>
                        <div>
                            <div class="alertas-count"><?= $alertas['total'] ?></div>
                            <div class="alertas-label">Alertas Pendientes</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-9 col-sm-12">
                    <?php if ($alertas['total'] == 0): ?>
                        <div class="text-success fw-bold">
                            <i class="fas fa-check-circle me-2"></i>¡Todo en orden! No hay plazos críticos.
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                            <?php foreach ($alertas['lista'] as $alerta): ?>
                                <div class="alerta-item">
                                    <span class="badge-estado <?= $alerta['estado'] == 'VENCIDO' ? 'badge-vencido' : 'badge-proximo' ?>">
                                        <?= $alerta['estado'] == 'VENCIDO' ? 'VENCIDO' : 'PRÓXIMO' ?>
                                    </span>
                                    <a href="presentacion/seguimiento.php?id=<?= $alerta['idExpediente'] ?>" class="acta-link">
                                        Acta <?= htmlspecialchars($alerta['numeroActa']) ?>
                                    </a>
                                    <span class="text-muted ms-2">
                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($alerta['areaOrigen']) ?>
                                    </span>
                                    <span class="text-muted ms-2">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php if ($alerta['estado'] == 'VENCIDO'): ?>
                                            Vence: <?= date('d/m/Y', strtotime($alerta['fechaVencimiento'])) ?> (hace <?= $alerta['dias'] ?> días)
                                        <?php else: ?>
                                            Vence: <?= date('d/m/Y', strtotime($alerta['fechaVencimiento'])) ?> (en <?= $alerta['dias'] ?> días)
                                        <?php endif; ?>
                                    </span>
                                    <span class="ms-2">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($alerta['evento']) ?></span>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($alertas['total'] > count($alertas['lista'])): ?>
                            <div class="text-muted mt-2">
                                <i class="fas fa-ellipsis-h me-1"></i> Mostrando <?= count($alertas['lista']) ?> de <?= $alertas['total'] ?> alertas.
                                <a href="presentacion/alertas.php" class="ms-2">Ver todas</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Módulos -->
        <div class="text-center mb-5" id="modulos">
            <span class="badge-area"><i class="fas fa-cubes me-1"></i> Módulos disponibles</span>
            <h2 class="fw-bold mt-2" style="color: #0b2a4a;">Accede a las funcionalidades</h2>
            <p class="text-muted">Selecciona el área que deseas gestionar</p>
        </div>

        <div class="row g-4">
            <!-- Establecimiento -->
            <div class="col-md-4">
                <div class="card card-modern text-center">
                    <div class="card-body">
                        <div class="card-icon"><i class="fas fa-store"></i></div>
                        <h5 class="card-title-modern">Establecimiento</h5>
                        <p class="card-text-modern">Registro y mantenimiento de establecimientos, RUC, razón social y responsables.</p>
                        <a href="<?= BASE_URL . '/presentacion/formEstablecimiento.php' ?>" class="btn btn-outline-primary-custom mt-2">
                            <i class="fas fa-arrow-right me-2"></i>Gestionar
                        </a>
                    </div>
                </div>
            </div>
            <!-- Sede -->
            <div class="col-md-4">
                <div class="card card-modern text-center">
                    <div class="card-body">
                        <div class="card-icon"><i class="fas fa-building"></i></div>
                        <h5 class="card-title-modern">Sede</h5>
                        <p class="card-text-modern">Administración de sedes, direcciones, categorías y situación DIGEMID.</p>
                        <a href="<?= BASE_URL . '/presentacion/formSede.php' ?>" class="btn btn-outline-primary-custom mt-2">
                            <i class="fas fa-arrow-right me-2"></i>Gestionar
                        </a>
                    </div>
                </div>
            </div>
            <!-- Expedientes -->
            <div class="col-md-4">
                <div class="card card-modern text-center">
                    <div class="card-body">
                        <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                        <h5 class="card-title-modern">Expedientes</h5>
                        <p class="card-text-modern">Expedientes según área de origen: UFREMID, UFRESA y UFRESBYT.</p>
                        <div class="d-grid gap-2 mt-2">
                            <a href="<?= BASE_URL . '/presentacion/formExpedienteUFREMID.php' ?>" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-file-alt me-1"></i> UFREMID
                            </a>
                            <a href="<?= BASE_URL . '/presentacion/formExpedienteUFRESA.php' ?>" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-file-alt me-1"></i> UFRESA
                            </a>
                            <a href="<?= BASE_URL . '/presentacion/formExpedienteUFRESBIT.php' ?>" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-file-alt me-1"></i> UFRESBYT
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Reportes -->
            <div class="col-md-4 offset-md-4">
                <div class="card card-modern text-center">
                    <div class="card-body">
                        <div class="card-icon"><i class="fas fa-chart-pie"></i></div>
                        <h5 class="card-title-modern">Reportes</h5>
                        <p class="card-text-modern">Visualiza reportes de inspecciones, estadísticas y datos agregados.</p>
                        <a href="<?= BASE_URL . '/presentacion/reporte1.php' ?>" class="btn btn-outline-primary-custom mt-2">
                            <i class="fas fa-arrow-right me-2"></i>Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas (puedes reemplazar con datos reales)
        <div class="stats-section">
            <div class="row text-center g-3">
                <div class="col-6 col-md-3">
                    <div class="stat-number">45</div>
                    <div class="stat-label">Establecimientos</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-number">78</div>
                    <div class="stat-label">Sedes</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-number">120</div>
                    <div class="stat-label">Expedientes</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-number">32</div>
                    <div class="stat-label">Reportes</div>
                </div>
            </div>
        </div> -->
    </div>

    <!-- Footer -->
    <footer class="footer-custom text-center">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Sub Gerencia de Regulación Sectorial - Todos los derechos reservados.</p>
            <small>Desarrollado con <i class="fas fa-heart" style="color: #e74c3c;"></i> para la gestión eficiente.</small>
        </div>
    </footer>

    <?php include 'presentacion/boostrap-js.php'; ?>
    <!-- Script de partículas -->
    <script src="<?= BASE_URL . 'js/particle.min.js' ?>"></script>
    <script>
        var canvasDiv = document.getElementById('particle-canvas');
        var options = {
            particleColor: '#ffffff',
            interactive: true,
            speed: 'medium',
            density: 'high'
        };
        var particleCanvas = new ParticleNetwork(canvasDiv, options);
        // Eliminar el fondo del div que crea la librería
        var bgDiv = canvasDiv.querySelector('div');
        if (bgDiv) {
            bgDiv.style.background = 'transparent';
        }
    </script>
</body>
</html>