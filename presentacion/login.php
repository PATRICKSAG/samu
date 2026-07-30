<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Si ya está autenticado, redirigir al index
if (isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnLogin'])) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            include_once(__DIR__ . '/../persistencia/conexion.php');
            $pdo = Database::getConexion();

            $sql = "SELECT id, usuario, password, nombre, email, activo FROM usuarios WHERE usuario = ? AND activo = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['password'] === $password) {
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['nombre'] = $user['nombre'] ?? $user['usuario'];
                $_SESSION['idUsuario'] = $user['id'];
                $redirect = $_SESSION['redirect_after_login'] ?? '../index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SGRS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,500;8..60,600&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy-950: #081428;
            --navy-900: #0d2540;
            --navy-700: #123a63;
            --blue-600: #2c5f9e;
            --gold-500: #c9a227;
            --gold-400: #dab949;
            --paper: #f5f7fa;
            --ink-900: #16233a;
            --slate-600: #56637c;
            --slate-400: #9aa7bd;
            --border: #dde3ee;
            --danger-bg: #fdecec;
            --danger-text: #8a2332;
            --danger-border: #f3c8cc;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--paper);
            color: var(--ink-900);
        }

        .portal-row {
            min-height: 100vh;
        }

        /* ---------- Brand panel ---------- */
        .brand-panel {
            position: relative;
            background: linear-gradient(165deg, var(--navy-900) 0%, var(--navy-700) 100%);
            color: #eef2f8;
            padding: 48px 44px;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: radial-gradient(circle at 30% 30%, black 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            color: var(--gold-400);
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .brand-eyebrow::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold-400);
            display: inline-block;
        }

        .brand-body {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
            padding: 40px 0;
        }

        .seal {
            width: 92px;
            height: 92px;
            margin-bottom: 28px;
        }

        .seal line, .seal circle, .seal path {
            stroke: var(--gold-500);
        }

        .brand-title {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
            font-size: 2rem;
            line-height: 1.2;
            margin: 0 0 10px;
            color: #ffffff;
        }

        .brand-subtitle {
            font-size: 1rem;
            color: #c3cee0;
            margin: 0;
            max-width: 34ch;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.82rem;
            color: #9fb0c9;
            border-top: 1px solid rgba(255,255,255,0.12);
            padding-top: 20px;
        }

        .brand-footer i { color: var(--gold-400); margin-top: 2px; }

        /* ---------- Form panel ---------- */
        .form-panel {
            background: var(--paper);
            padding: 40px 24px;
        }

        .form-wrap {
            width: 100%;
            max-width: 380px;
        }

        .form-heading {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 600;
            font-size: 1.7rem;
            color: var(--ink-900);
            margin-bottom: 6px;
        }

        .form-subtitle {
            color: var(--slate-600);
            font-size: 0.94rem;
            margin-bottom: 28px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ink-900);
            margin-bottom: 6px;
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
            font-size: 0.95rem;
            pointer-events: none;
        }

        .field-control {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            font-size: 0.96rem;
            color: var(--ink-900);
            background: #ffffff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .field-control::placeholder { color: var(--slate-400); }

        .field-control:focus {
            outline: none;
            border-color: var(--blue-600);
            box-shadow: 0 0 0 3px rgba(44, 95, 158, 0.16);
        }

        .toggle-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--slate-400);
            cursor: pointer;
            padding: 4px;
        }

        .toggle-pass:hover { color: var(--slate-600); }
        .toggle-pass:focus-visible {
            outline: 2px solid var(--blue-600);
            outline-offset: 2px;
            border-radius: 6px;
        }

        .btn-login {
            width: 100%;
            background: var(--navy-900);
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.98rem;
            color: #ffffff;
            transition: background 0.15s;
        }

        .btn-login:hover { background: var(--navy-700); color: #fff; }

        .btn-login:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.45);
        }

        .alert-inline {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .alert-inline i { margin-top: 2px; }

        .form-footnote {
            text-align: center;
            margin-top: 28px;
            font-size: 0.82rem;
            color: var(--slate-600);
        }

        .form-footnote a {
            color: var(--blue-600);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footnote a:hover { text-decoration: underline; }

        @media (max-width: 991.98px) {
            .brand-panel {
                padding: 32px 28px;
            }
            .brand-body { padding: 16px 0; }
            .seal { width: 64px; height: 64px; margin-bottom: 18px; }
            .brand-title { font-size: 1.5rem; }
            .brand-footer { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>

    <div class="container-fluid" style="--bs-gutter-x:0">
        <div class="row g-0 portal-row">

            <!-- Panel institucional -->
            <div class="col-lg-5 brand-panel d-flex flex-column">
                <span class="brand-eyebrow">Sistema interno</span>

                <div class="brand-body">
                    <svg class="seal" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <g stroke-width="2" stroke-linecap="round">
                            <line x1="128.0" y1="70.0" x2="136.0" y2="70.0" />
                            <line x1="126.7" y1="82.1" x2="134.6" y2="83.7" />
                            <line x1="123.0" y1="93.6" x2="130.3" y2="96.8" />
                            <line x1="116.9" y1="104.1" x2="123.4" y2="108.8" />
                            <line x1="108.8" y1="113.1" x2="114.2" y2="119.0" />
                            <line x1="99.0" y1="120.2" x2="103.0" y2="127.2" />
                            <line x1="87.9" y1="125.2" x2="90.4" y2="132.8" />
                            <line x1="76.1" y1="127.7" x2="76.9" y2="135.6" />
                            <line x1="63.9" y1="127.7" x2="63.1" y2="135.6" />
                            <line x1="52.1" y1="125.2" x2="49.6" y2="132.8" />
                            <line x1="41.0" y1="120.2" x2="37.0" y2="127.2" />
                            <line x1="31.2" y1="113.1" x2="25.8" y2="119.0" />
                            <line x1="23.1" y1="104.1" x2="16.6" y2="108.8" />
                            <line x1="17.0" y1="93.6" x2="9.7" y2="96.8" />
                            <line x1="13.3" y1="82.1" x2="5.4" y2="83.7" />
                            <line x1="12.0" y1="70.0" x2="4.0" y2="70.0" />
                            <line x1="13.3" y1="57.9" x2="5.4" y2="56.3" />
                            <line x1="17.0" y1="46.4" x2="9.7" y2="43.2" />
                            <line x1="23.1" y1="35.9" x2="16.6" y2="31.2" />
                            <line x1="31.2" y1="26.9" x2="25.8" y2="21.0" />
                            <line x1="41.0" y1="19.8" x2="37.0" y2="12.8" />
                            <line x1="52.1" y1="14.8" x2="49.6" y2="7.2" />
                            <line x1="63.9" y1="12.3" x2="63.1" y2="4.4" />
                            <line x1="76.1" y1="12.3" x2="76.9" y2="4.4" />
                            <line x1="87.9" y1="14.8" x2="90.4" y2="7.2" />
                            <line x1="99.0" y1="19.8" x2="103.0" y2="12.8" />
                            <line x1="108.8" y1="26.9" x2="114.2" y2="21.0" />
                            <line x1="116.9" y1="35.9" x2="123.4" y2="31.2" />
                            <line x1="123.0" y1="46.4" x2="130.3" y2="43.2" />
                            <line x1="126.7" y1="57.9" x2="134.6" y2="56.3" />
                        </g>
                        <circle cx="70" cy="70" r="46" stroke-width="2"/>
                        <path d="M52 70l13 13 24-26" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                    <h1 class="brand-title">Sistema de Gestión SGRS</h1>
                    <p class="brand-subtitle">Sub Gerencia de Regulación Sectorial</p>
                </div>

                <div class="brand-footer">
                    <i class="fas fa-shield-halved"></i>
                    <span>Acceso exclusivo para personal autorizado. Toda actividad queda registrada.</span>
                </div>
            </div>

            <!-- Panel de acceso -->
            <div class="col-lg-7 form-panel d-flex align-items-center justify-content-center">
                <div class="form-wrap">
                    <h2 class="form-heading">Iniciar sesión</h2>
                    <p class="form-subtitle">Ingresa tus credenciales institucionales para continuar.</p>

                    <?php if ($error): ?>
                        <div class="alert-inline" role="alert">
                            <i class="fas fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuario</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user field-icon"></i>
                                <input
                                    type="text"
                                    class="field-control"
                                    id="usuario"
                                    name="usuario"
                                    placeholder="Ingresa tu usuario"
                                    autocomplete="username"
                                    autofocus
                                    required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-key field-icon"></i>
                                <input
                                    type="password"
                                    class="field-control"
                                    id="password"
                                    name="password"
                                    placeholder="Ingresa tu contraseña"
                                    autocomplete="current-password"
                                    required>
                                <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="btnLogin" class="btn-login">
                            Iniciar sesión
                        </button>
                    </form>

                    <div class="form-footnote">
                        ¿Problemas para acceder? <a href="mailto:soporte@sgrs.gob.pe">Contacta al administrador del sistema</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggleBtn = document.getElementById('togglePass');
        const passInput = document.getElementById('password');
        if (toggleBtn && passInput) {
            toggleBtn.addEventListener('click', () => {
                const isHidden = passInput.type === 'password';
                passInput.type = isHidden ? 'text' : 'password';
                toggleBtn.setAttribute('aria-pressed', String(isHidden));
                toggleBtn.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggleBtn.innerHTML = isHidden ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        }
    </script>
</body>
</html>