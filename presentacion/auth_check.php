<?php
// presentacion/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    // Guardar la URL actual para redirigir después del login (opcional)
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// Verificar que el usuario esté activo (opcional)
// Aquí podrías hacer una consulta a la BD para verificar que el usuario sigue activo
// pero para simplificar, lo dejamos así.

// Prevenir caché para que no se pueda volver atrás después del logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>