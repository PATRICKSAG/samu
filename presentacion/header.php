<?php
    // Al inicio del header, puedes incluir la conexión y la función
    include_once __DIR__ . '/../persistencia/conexion.php';
    include_once __DIR__ . '/../persistencia/dExpediente.php';
    $pdo          = Database::getConexion();
    $alertas      = obtenerPlazosCriticos($pdo);
    $totalAlertas = $alertas['total'];
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?php echo BASE_URL . '/index.php' ?>">
              Inicio
              <?php if ($totalAlertas > 0): ?>
                  <span class="badge bg-danger rounded-pill ms-1"><?php echo $totalAlertas ?></span>
              <?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL . '/presentacion/formEstablecimiento.php' ?>">Dueños</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL . '/presentacion/formSede.php' ?>">Locales</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Expediente
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            <li><a class="dropdown-item" href="<?php echo BASE_URL . '/presentacion/formExpedienteUFREMID.php' ?>">Expediente UFREMID</a></li>
            <li><a class="dropdown-item" href="<?php echo BASE_URL . '/presentacion/formExpedienteUFRESA.php' ?>">Expediente UFRESA</a></li>
            <li><a class="dropdown-item" href="<?php echo BASE_URL . '/presentacion/formExpedienteUFRESBIT.php' ?>">Expediente UFRESBIT</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL . '/presentacion/reporte1.php' ?>">Reporte</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Al final del body, solo carga bootstrap -->
<?php include 'boostrap-js.php'; ?>