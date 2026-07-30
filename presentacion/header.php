<?php
// presentacion/header.php
$nombreUsuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$BASE_URL = defined('BASE_URL') ? BASE_URL : '../';
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= $BASE_URL . '/index.php' ?>">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $BASE_URL . '/presentacion/formEstablecimiento.php' ?>">Establecimiento</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $BASE_URL . '/presentacion/formSede.php' ?>">Sede</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Expediente
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="<?= $BASE_URL . '/presentacion/formExpedienteUFREMID.php' ?>">Expediente UFREMID</a></li>
                        <li><a class="dropdown-item" href="<?= $BASE_URL . '/presentacion/formExpedienteUFRESA.php' ?>">Expediente UFRESA</a></li>
                        <li><a class="dropdown-item" href="<?= $BASE_URL . '/presentacion/formExpedienteUFRESBIT.php' ?>">Expediente UFRESBIT</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= $BASE_URL . '/presentacion/reporte1.php' ?>">Reporte</a></li>
            </ul>

            <!-- Usuario y Logout -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nombreUsuario) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= $BASE_URL . '/presentacion/logout.php' ?>">
                            <i class="fas fa-sign-out-alt me-2 text-danger"></i>Cerrar Sesión
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>