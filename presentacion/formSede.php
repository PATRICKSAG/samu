<?php
    include_once __DIR__ . '/../config.php';
    include_once __DIR__ . '/../persistencia/conexion.php';
    include_once __DIR__ . '/../persistencia/dSede.php';
    include_once __DIR__ . '/../persistencia/dCategoria.php';
    include_once __DIR__ . '/../persistencia/dEstablecimiento.php';
    include_once __DIR__ . '/../persistencia/dDepartamento.php';
    include_once __DIR__ . '/../persistencia/dProvincia.php';
    include_once __DIR__ . '/../persistencia/dDistrito.php';
    include_once __DIR__ . '/../persistencia/dSituacionEstablecimiento.php';
    include_once __DIR__ . '/../persistencia/dSituacionDigemid.php';
    include_once __DIR__ . '/../persistencia/dRenipress.php'; // NUEVO

    // VERIFICACIÓN DE SESIÓN (AGREGAR ESTO)
    include_once(__DIR__ . '/auth_check.php');

    $pdo = Database::getConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Inicializar variables
    $txtIdSede                   = '';
    $txtEstablecimiento          = '';
    $txtNumeroEstacion           = '';
    $txtnombreComercial          = '';
    $txtFechaRegistroSi          = '';
    $txtCategoria                = '';
    $txtSituacionEstablecimiento = '';
    $txtSituacionDigemid         = '';
    $txtDepartamento             = '';
    $txtProvincia                = '';
    $txtDistrito                 = '';
    $txtDireccion                = '';
    $txtTelefono                 = '';
    $txtTieneQuimicoFarmaceutico = 0;
    $txtHorarioFuncionamiento    = '';
    $txtAreaOrigen               = 'UFREMID'; // por defecto
    $txtEstadoRenipress          = '';
    $txtInstitucionRenipress     = '';
    $txtTipoRenipress            = '';
    $txtClasificacionRenipress   = '';
    $txtCategorizacion = '';
    $txtInicioActividad = '';

    $mensaje      = '';
    $mensajeError = '';

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnConsultar'])) {
    // Asignar valores POST
    $txtIdSede                   = $_POST['txtIdSede'] ?? '';
    $txtEstablecimiento          = $_POST['txtEstablecimiento'] ?? '';
    $txtNumeroEstacion           = $_POST['txtNumeroEstacion'] ?? '';
    $txtnombreComercial          = $_POST['txtnombreComercial'] ?? '';
    $txtFechaRegistroSi          = $_POST['txtFechaRegistroSi'] ?? '';
    $txtCategoria                = $_POST['txtCategoria'] ?? '';
    $txtSituacionEstablecimiento = $_POST['txtSituacionEstablecimiento'] ?? '';
    $txtSituacionDigemid         = $_POST['txtSituacionDigemid'] ?? '';
    $txtDepartamento             = $_POST['txtDepartamento'] ?? '';
    $txtProvincia                = $_POST['txtProvincia'] ?? '';
    $txtDistrito                 = $_POST['txtDistrito'] ?? '';
    $txtDireccion                = $_POST['txtDireccion'] ?? '';
    $txtTelefono                 = $_POST['txtTelefono'] ?? '';
    $txtTieneQuimicoFarmaceutico = isset($_POST['txtTieneQuimicoFarmaceutico']) ? 1 : 0;
    $txtHorarioFuncionamiento    = $_POST['txtHorarioFuncionamiento'] ?? '';
    $txtAreaOrigen               = $_POST['txtAreaOrigen'] ?? 'UFREMID';
    $txtEstadoRenipress          = $_POST['txtEstadoRenipress'] ?? '';
    $txtInstitucionRenipress     = $_POST['txtInstitucionRenipress'] ?? '';
    $txtTipoRenipress            = $_POST['txtTipoRenipress'] ?? '';
    $txtClasificacionRenipress   = $_POST['txtClasificacionRenipress'] ?? '';
    $txtCategorizacion           = $_POST['txtCategorizacion'] ?? '';
    $txtInicioActividad          = $_POST['txtInicioActividad'] ?? '';
    $errores = [];
    if (empty($txtnombreComercial)) {
        $errores[] = "El nombre comercial es requerido.";
    }
    if (empty($txtEstablecimiento)) {
        $errores[] = "El establecimiento es requerido.";
    }
    if (empty($txtDepartamento)) {
        $errores[] = "El departamento es requerido.";
    }
    if (empty($txtProvincia)) {
        $errores[] = "La provincia es requerida.";
    }
    if (empty($txtDistrito)) {
        $errores[] = "El distrito es requerido.";
    }
    if (empty($txtDireccion)) {
        $errores[] = "La dirección es requerida.";
    }
    if (empty($txtSituacionEstablecimiento)) {
        $errores[] = "La situación del establecimiento es requerida.";
    }

    // Validaciones según área
    if ($txtAreaOrigen == 'UFREMID' || $txtAreaOrigen == 'UFRESA') {
        if (empty($txtCategoria)) {
            $errores[] = "La categoría es requerida.";
        }
        if (empty($txtSituacionDigemid)) {
            $errores[] = "La situación es requerida.";
        }
    } elseif ($txtAreaOrigen == 'UFRESBIT') {
        if (empty($txtEstadoRenipress)) {
            $errores[] = "El estado IPRESS es requerido.";
        }
        if (empty($txtInstitucionRenipress)) {
            $errores[] = "La institución IPRESS es requerida.";
        }
        if (empty($txtTipoRenipress)) {
            $errores[] = "El tipo IPRESS es requerido.";
        }
        if (empty($txtClasificacionRenipress)) {
            $errores[] = "La clasificación IPRESS es requerida.";
        }
    }

    if (empty($errores)) {
        try {
            $data = [
                'idEstablecimiento'          => $txtEstablecimiento,
                'numeroEstacion'             => $txtNumeroEstacion,
                'nombre'                     => $txtnombreComercial,
                'fechaRegistroSi'            => empty($txtFechaRegistroSi) ? null : $txtFechaRegistroSi,
                'idCategoria'                => ($txtAreaOrigen != 'UFRESBIT') ? $txtCategoria : null,
                'idSituacionEstablecimiento' => $txtSituacionEstablecimiento,
                'idSituacionDigemid'         => ($txtAreaOrigen != 'UFRESBIT') ? $txtSituacionDigemid : null,
                'direccion'                  => $txtDireccion,
                'telefono'                   => $txtTelefono,
                'tieneQuimicoFarmaceutico'   => ($txtAreaOrigen != 'UFRESBIT') ? $txtTieneQuimicoFarmaceutico : 0,
                'idDepartamento'             => $txtDepartamento,
                'idProvincia'                => $txtProvincia,
                'idDistrito'                 => $txtDistrito,
                'horarioFuncionamiento'      => $txtHorarioFuncionamiento,
                'areaOrigen'                 => $txtAreaOrigen,
                'idEstadoRenipress'          => ($txtAreaOrigen == 'UFRESBIT') ? $txtEstadoRenipress : null,
                'idInstitucionRenipress'     => ($txtAreaOrigen == 'UFRESBIT') ? $txtInstitucionRenipress : null,
                'idTipoRenipress'            => ($txtAreaOrigen == 'UFRESBIT') ? $txtTipoRenipress : null,
                'idClasificacionRenipress'   => ($txtAreaOrigen == 'UFRESBIT') ? $txtClasificacionRenipress : null,
                'categorizacion'             => ($txtAreaOrigen == 'UFRESBIT') ? $txtCategorizacion : null,
                'inicioActividad'            => ($txtAreaOrigen == 'UFRESBIT') ? $txtInicioActividad : null,
            ];

            if (! empty($txtIdSede)) {
                $data['idSede'] = $txtIdSede;
                actualizarSede($pdo, $data);
                $mensaje = "Sede actualizada correctamente.";
            } else {
                insertarSede($pdo, $data);
                $mensaje = "Sede creada correctamente.";
            }

            // Limpiar campos (opcional, pero redirigimos)
            header("Location: " . $_SERVER['PHP_SELF'] . "?mensaje=" . urlencode($mensaje));
            exit;
        } catch (PDOException $e) {
            $mensajeError = "Error al guardar: " . $e->getMessage();
        }
    } else {
        $mensajeError = implode("<br>", $errores);
    }
    } else {
    // Si hay mensaje en GET, mostrarlo
    if (isset($_GET['mensaje'])) {
        $mensaje = $_GET['mensaje'];
    }
    }

    // Cargar datos para editar vía GET
    if (isset($_GET['editar'])) {
    $idEditar   = intval($_GET['editar']);
    $sedesTodas = listarSedes($pdo);
    foreach ($sedesTodas as $s) {
        if ($s['idSede'] == $idEditar) {
            $txtIdSede                   = $s['idSede'];
            $txtEstablecimiento          = $s['idEstablecimiento'];
            $txtNumeroEstacion           = $s['numeroEstacion'];
            $txtnombreComercial          = $s['nombre'];
            $txtFechaRegistroSi          = $s['fechaRegistroSi'];
            $txtCategoria                = $s['idCategoria'];
            $txtSituacionEstablecimiento = $s['idSituacionEstablecimiento'];
            $txtSituacionDigemid         = $s['idSituacionDigemid'];
            $txtDepartamento             = $s['idDepartamento'];
            $txtProvincia                = $s['idProvincia'];
            $txtDistrito                 = $s['idDistrito'];
            $txtDireccion                = $s['direccion'];
            $txtTelefono                 = $s['telefono'];
            $txtTieneQuimicoFarmaceutico = $s['tieneQuimicoFarmaceutico'];
            $txtHorarioFuncionamiento    = $s['horarioFuncionamiento'];
            $txtAreaOrigen               = $s['areaOrigen'] ?? 'UFREMID';
            $txtEstadoRenipress          = $s['idEstadoRenipress'];
            $txtInstitucionRenipress     = $s['idInstitucionRenipress'];
            $txtTipoRenipress            = $s['idTipoRenipress'];
            $txtClasificacionRenipress   = $s['idClasificacionRenipress'];
            $txtCategorizacion           = $s['categorizacion'] ?? '';
            $txtInicioActividad          = $s['inicioActividad'] ?? '';
            break;
        }
    }
    }

    // Obtener listas para selects
    $categorias       = listarCategorias($pdo);
    $establecimientos = listarEstablecimientos($pdo);
    $departamentos    = listarDepartamentos($pdo);
    $situaciones      = listarSituacionesEstablecimientos($pdo);
    $digemids         = listarSituacionesDigemid($pdo);
    $provincias       = listarProvincias($pdo);
    $distritos        = listarDistritos($pdo);
    $sedes            = listarSedes($pdo);

    // Nuevas listas para UFRESBIT
    $estadosRenipress       = listarEstadosRenipress($pdo);
    $institucionesRenipress = listarInstitucionesRenipress($pdo);
    $tiposRenipress         = listarTiposRenipress($pdo);
    // Las clasificaciones se cargarán dinámicamente vía AJAX, pero para edición necesitamos cargar la clasificación actual
    // También podríamos cargar todas y filtrar con JS, pero haremos AJAX.

    $dataTable = [];
foreach ($sedes as $sede) {
    // Construir la columna Q.F.
    $qf = ($sede['tieneQuimicoFarmaceutico'] ?? 0) 
        ? '<span class="badge-qf"><i class="fas fa-check-circle me-1"></i>SI</span>' 
        : '<span class="badge-noqf"><i class="fas fa-times-circle me-1"></i>NO</span>';
    
    // Construir la columna de acciones
    $acciones = '<a href="?editar=' . $sede['idSede'] . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Editar</a> '
              . '<button type="button" class="btn btn-sm btn-danger" onclick="eliminar(' . $sede['idSede'] . ')"><i class="fas fa-trash-alt"></i> Eliminar</button>';
    
    // Agregar la fila como array
    $dataTable[] = [
        $sede['idSede'],
        $sede['areaOrigen'] ?? '',
        $sede['numeroEstacion'] ?? '',
        $sede['nombre'] ?? '',
        $sede['categoria'] ?? '',
        $sede['ruc'] ?? '',
        $sede['razonSocial'] ?? '',
        $sede['provincia'] ?? '',
        $sede['distrito'] ?? '',
        $sede['direccion'] ?? '',
        $qf,
        $sede['Situacion_Digemid'] ?? '',
        $sede['estadoRenipress'] ?? '',
        $sede['institucionRenipress'] ?? '',
        $sede['tipoRenipress'] ?? '',
        $sede['clasificacionRenipress'] ?? '',
        $sede['categorizacion'] ?? '',
        $sede['inicioActividad'] ?? '',
        $acciones
    ];
}
// Convertir a JSON (escapar para seguridad)
$jsonData = json_encode($dataTable, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sedes</title>
    <?php include 'boostrap-css.php'; ?>
    <?php include 'datatable-css.php'; ?>
    <?php include 'select2-css.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Evitar que los badges se rompan en dos líneas */
        .badge-qf, .badge-noqf {
            white-space: nowrap;
        }

        /* Dar un ancho mínimo a la columna Q.F. (columna 11 en el orden de la tabla) */
        #example th:nth-child(11),
        #example td:nth-child(11) {
            min-width: 70px;
            white-space: nowrap;
        }
        #example th:nth-child(10),
        #example td:nth-child(10) {
            min-width: 20px;
            white-space: nowrap;
        }
        .renipress-fields {
            display: none;
        }
        .ufremid-fields, .ufresa-fields {
            display: block;
        }
        .table-modern {
            border-radius: 16px;
            overflow-x: auto !important;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
        }
        .table-modern table {
            min-width: 1200px;
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
            padding: 10px 8px;
        }
        .table-modern td {
            vertical-align: middle;
            padding: 8px 6px;
        }
        .badge-qf {
            background: #17a2b8;
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge-noqf {
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        /* restaura los estilos previos */
        body { background-color: #f0f4fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-header { background: linear-gradient(135deg, #0b2a4a 0%, #1b4f8b 100%); color: white; padding: 30px 0 25px; border-radius: 0 0 40px 40px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .page-header h2 { font-weight: 700; margin: 0; }
        .page-header p { opacity: 0.85; margin: 5px 0 0; }
        .card-modern { border: none; border-radius: 24px; background: #ffffff; box-shadow: 0 8px 25px rgba(0,0,0,0.06); transition: transform 0.2s; }
        .card-modern:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .btn-primary-custom { background: #1b4f8b; border: none; border-radius: 50px; padding: 10px 30px; font-weight: 600; color: white; transition: 0.25s; }
        .btn-primary-custom:hover { background: #0f3b6b; transform: scale(1.02); }
        .btn-outline-secondary-custom { border: 2px solid #6c757d; color: #6c757d; border-radius: 50px; padding: 10px 30px; font-weight: 600; transition: 0.25s; background: transparent; }
        .btn-outline-secondary-custom:hover { background: #6c757d; color: white; }
        .form-control-modern { border-radius: 12px; border: 1px solid #dce3ed; padding: 10px 15px; transition: 0.2s; }
        .form-control-modern:focus { border-color: #1b4f8b; box-shadow: 0 0 0 3px rgba(27,79,139,0.15); }
        .form-label { font-weight: 500; color: #2c3e50; }
        .footer-custom { background: #0b2a4a; color: rgba(255,255,255,0.7); padding: 20px 0; border-radius: 40px 40px 0 0; margin-top: 40px; text-align: center; font-size: 0.9rem; }
        .footer-custom a { color: white; text-decoration: none; }
        .footer-custom a:hover { text-decoration: underline; }
        .icon-input { background: #e9f0fc; padding: 0 15px; border-radius: 12px 0 0 12px; display: flex; align-items: center; color: #1b4f8b; border: 1px solid #dce3ed; border-right: none; }
        .input-group-custom { display: flex; align-items: stretch; }
        .input-group-custom .form-control { border-radius: 0 12px 12px 0; border-left: none; }
        .select2-container--default .select2-selection--single { border-radius: 12px !important; border-color: #dce3ed !important; height: 44px !important; display: flex; align-items: center; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 42px !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px !important; }
        @media (max-width: 768px) { .page-header { padding: 20px 0; } .btn-primary-custom, .btn-outline-secondary-custom { width: 100%; margin-bottom: 5px; } }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Cabecera -->
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-building me-2"></i>Gestión de Sedes</h2>
            <p><i class="fas fa-edit me-1"></i>Registra, edita y administra las sedes de los establecimientos</p>
        </div>
    </div>

    <div class="container">
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($mensajeError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($mensajeError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="card card-modern mb-4">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3" style="color: #0b2a4a;">
                    <i class="fas fa-pen-alt me-2"></i><?php echo $txtIdSede ? 'Editar Local' : 'Nuevo Local' ?>
                </h5>
                <form method="POST" action="">
                    <input type="hidden" name="txtIdSede" id="txtIdSede" value="<?php echo $txtIdSede ?>">

                    <div class="row g-3">
                        <!-- Área Origen -->
                        <div class="col-md-4">
                            <label for="txtAreaOrigen" class="form-label"><i class="fas fa-tag me-1"></i>Área Origen <span class="text-danger">*</span></label>
                            <select name="txtAreaOrigen" id="txtAreaOrigen" class="form-select" required>
                                <option value="UFREMID" <?php echo($txtAreaOrigen == 'UFREMID') ? 'selected' : '' ?>>UFREMID</option>
                                <option value="UFRESA" <?php echo($txtAreaOrigen == 'UFRESA') ? 'selected' : '' ?>>UFRESA</option>
                                <option value="UFRESBIT" <?php echo($txtAreaOrigen == 'UFRESBIT') ? 'selected' : '' ?>>UFRESBIT</option>
                            </select>
                        </div>

                        <!-- Establecimiento -->
                        <div class="col-md-8">
                            <label for="txtEstablecimiento" class="form-label"><i class="fas fa-store me-1"></i>Dueño <span class="text-danger">*</span></label>
                            <select name="txtEstablecimiento" id="txtEstablecimiento" class="form-select select2-auto" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($establecimientos as $est): ?>
                                    <option value="<?php echo $est['idEstablecimiento'] ?>" <?php echo($txtEstablecimiento == $est['idEstablecimiento']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($est['ruc'] . ' - ' . $est['razonSocial']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nombre Comercial -->
                        <div class="col-md-6">
                            <label for="txtnombreComercial" class="form-label"><i class="fas fa-tag me-1"></i>Nombre Comercial <span class="text-danger">*</span></label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-store-alt"></i></span>
                                <input type="text" class="form-control form-control-modern mayuscula" name="txtnombreComercial" id="txtnombreComercial" value="<?php echo htmlspecialchars($txtnombreComercial ?? '') ?>" placeholder="Nombre comercial" autocomplete="off" required>
                            </div>
                        </div>

                        <!-- Nº Estación -->
                        <div class="col-md-3">
                            <label for="txtNumeroEstacion" class="form-label"><i class="fas fa-hashtag me-1"></i>Nº Estación</label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-sort-numeric-up"></i></span>
                                <input type="text" class="form-control form-control-modern" name="txtNumeroEstacion" id="txtNumeroEstacion" value="<?php echo htmlspecialchars($txtNumeroEstacion ?? '') ?>" placeholder="Ej. 123">
                            </div>
                        </div>

                        <!-- Fecha Registro SI -->
                        <div class="col-md-3">
                            <label for="txtFechaRegistroSi" class="form-label"><i class="fas fa-calendar-alt me-1"></i>Fecha Registro SI</label>
                            <input type="date" class="form-control form-control-modern" name="txtFechaRegistroSi" id="txtFechaRegistroSi" value="<?php echo $txtFechaRegistroSi ?>">
                        </div>

                        <!-- Campos UFREMID / UFRESA -->
                        <div class="col-12" id="camposUFREMIDUFRESA">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="txtCategoria" class="form-label"><i class="fas fa-tag me-1"></i>Categoría <span class="text-danger">*</span></label>
                                    <select name="txtCategoria" id="txtCategoria" class="form-select" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <?php
                                                // Filtrar según área (se aplicará con JavaScript)
                                                $show = false;
                                                if ($txtAreaOrigen == 'UFREMID' && $cat['idCategoria'] <= 8) {
                                                    $show = true;
                                                }

                                                if ($txtAreaOrigen == 'UFRESA' && $cat['idCategoria'] >= 9) {
                                                    $show = true;
                                                }

                                                if ($txtAreaOrigen == 'UFRESBIT') {
                                                    $show = false;
                                                }
                                                // no se usa
                                            ?>
                                            <?php if ($show): ?>
                                                <option value="<?php echo $cat['idCategoria'] ?>" <?php echo($txtCategoria == $cat['idCategoria']) ? 'selected' : '' ?>>
                                                    <?php echo htmlspecialchars($cat['nombre']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="txtSituacionDigemid" class="form-label"><i class="fas fa-medical-alt me-1"></i>Situación <span class="text-danger">*</span></label>
                                    <select name="txtSituacionDigemid" id="txtSituacionDigemid" class="form-select" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($digemids as $dig): ?>
                                            <option value="<?php echo $dig['idSituacionDigemid'] ?>" <?php echo($txtSituacionDigemid == $dig['idSituacionDigemid']) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($dig['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4" id="campoQF">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="txtTieneQuimicoFarmaceutico" id="txtTieneQuimicoFarmaceutico" value="1" <?php echo $txtTieneQuimicoFarmaceutico ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="txtTieneQuimicoFarmaceutico">
                                            <i class="fas fa-flask me-1" style="color: #17a2b8;"></i> ¿Tiene Q.F.?
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos UFRESBIT -->
                        <div class="col-12 renipress-fields" id="camposUFRESBIT">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="txtEstadoRenipress" class="form-label"><i class="fas fa-circle me-1"></i>Estado IPRESS <span class="text-danger">*</span></label>
                                    <select name="txtEstadoRenipress" id="txtEstadoRenipress" class="form-select" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($estadosRenipress as $est): ?>
                                            <option value="<?php echo $est['id_estado'] ?>" <?php echo($txtEstadoRenipress == $est['id_estado']) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($est['descripcion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="txtInstitucionRenipress" class="form-label"><i class="fas fa-university me-1"></i>Institución <span class="text-danger">*</span></label>
                                    <select name="txtInstitucionRenipress" id="txtInstitucionRenipress" class="form-select" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($institucionesRenipress as $inst): ?>
                                            <option value="<?php echo $inst['idInsticionRenipress'] ?>" <?php echo($txtInstitucionRenipress == $inst['idInsticionRenipress']) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($inst['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="txtTipoRenipress" class="form-label"><i class="fas fa-layer-group me-1"></i>Tipo <span class="text-danger">*</span></label>
                                    <select name="txtTipoRenipress" id="txtTipoRenipress" class="form-select select2-auto" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($tiposRenipress as $tipo): ?>
                                            <option value="<?php echo $tipo['idTipoRenipress'] ?>" <?php echo($txtTipoRenipress == $tipo['idTipoRenipress']) ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($tipo['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="txtClasificacionRenipress" class="form-label"><i class="fas fa-sitemap me-1"></i>Clasificación <span class="text-danger">*</span></label>
                                    <select name="txtClasificacionRenipress" id="txtClasificacionRenipress" class="form-select select2-auto" required>
                                        <option value="">Seleccionar</option>
                                        <!-- Las opciones se cargarán vía AJAX al cambiar tipo -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="txtCategorizacion" class="form-label"><i class="fas fa-tags me-1"></i>Categorización <span class="text-danger">*</span></label>
                                    <select name="txtCategorizacion" id="txtCategorizacion" class="form-select" required>
                                        <option value="">Seleccionar</option>
                                        <option value="SI" <?= ($txtCategorizacion == 'SI') ? 'selected' : '' ?>>SI</option>
                                        <option value="NO" <?= ($txtCategorizacion == 'NO') ? 'selected' : '' ?>>NO</option>
                                        <option value="NO APLICA" <?= ($txtCategorizacion == 'NO APLICA') ? 'selected' : '' ?>>NO APLICA</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="txtInicioActividad" class="form-label"><i class="fas fa-play me-1"></i>Inicio Actividad <span class="text-danger">*</span></label>
                                    <select name="txtInicioActividad" id="txtInicioActividad" class="form-select" required>
                                        <option value="">Seleccionar</option>
                                        <option value="SI" <?= ($txtInicioActividad == 'SI') ? 'selected' : '' ?>>SI</option>
                                        <option value="NO" <?= ($txtInicioActividad == 'NO') ? 'selected' : '' ?>>NO</option>
                                        <option value="NO APLICA" <?= ($txtInicioActividad == 'NO APLICA') ? 'selected' : '' ?>>NO APLICA</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Ubicación y dirección (comunes) -->
                        <div class="col-md-4">
                            <label for="txtDepartamento" class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Departamento <span class="text-danger">*</span></label>
                            <select name="txtDepartamento" id="txtDepartamento" class="form-select select2-auto" data-url="../persistencia/dProvincia.php" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($departamentos as $dep): ?>
                                    <option value="<?php echo $dep['idDepartamento'] ?>" <?php echo($txtDepartamento == $dep['idDepartamento']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($dep['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="txtProvincia" class="form-label"><i class="fas fa-map-pin me-1"></i>Provincia <span class="text-danger">*</span></label>
                            <select name="txtProvincia" id="txtProvincia" class="form-select select2-auto" data-url="../persistencia/dDistrito.php" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($provincias as $prov): ?>
                                    <option value="<?php echo $prov['idProvincia'] ?>" <?php echo($txtProvincia == $prov['idProvincia']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($prov['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="txtDistrito" class="form-label"><i class="fas fa-city me-1"></i>Distrito <span class="text-danger">*</span></label>
                            <select name="txtDistrito" id="txtDistrito" class="form-select select2-auto" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($distritos as $dist): ?>
                                    <option value="<?php echo $dist['idDistrito'] ?>" <?php echo($txtDistrito == $dist['idDistrito']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($dist['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label for="txtDireccion" class="form-label"><i class="fas fa-address-card me-1"></i>Dirección <span class="text-danger">*</span></label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-home"></i></span>
                                <input type="text" class="form-control form-control-modern mayuscula" name="txtDireccion" id="txtDireccion" value="<?php echo htmlspecialchars($txtDireccion?? '') ?>" placeholder="Dirección completa" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="txtTelefono" class="form-label"><i class="fas fa-phone me-1"></i>Teléfono</label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control form-control-modern" name="txtTelefono" id="txtTelefono" value="<?php echo htmlspecialchars($txtTelefono?? '') ?>" placeholder="Teléfono">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="txtHorarioFuncionamiento" class="form-label"><i class="fas fa-clock me-1"></i>Horario Func.</label>
                            <div class="input-group-custom">
                                <span class="icon-input"><i class="fas fa-clock"></i></span>
                                <input type="text" class="form-control form-control-modern" name="txtHorarioFuncionamiento" id="txtHorarioFuncionamiento" value="<?php echo htmlspecialchars($txtHorarioFuncionamiento?? '') ?>" placeholder="Ej. Lun-Vie 8am-6pm">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="txtSituacionEstablecimiento" class="form-label"><i class="fas fa-info-circle me-1"></i>Situación Establecimiento <span class="text-danger">*</span></label>
                            <select name="txtSituacionEstablecimiento" id="txtSituacionEstablecimiento" class="form-select" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($situaciones as $sit): ?>
                                    <option value="<?php echo $sit['idSituacionEstablecimiento'] ?>" <?php echo($txtSituacionEstablecimiento == $sit['idSituacionEstablecimiento']) ? 'selected' : '' ?>>
                                        <?php echo htmlspecialchars($sit['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button type="submit" name="btnConsultar" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                        <button type="button" class="btn btn-outline-secondary-custom" onclick="cancelar();">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary-custom">
                            <i class="fas fa-plus me-2"></i>Nuevo
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado -->
        <div class="card card-modern">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <h5 class="card-title fw-bold" style="color: #0b2a4a;">
                        <i class="fas fa-list me-2"></i>Sedes Registradas
                    </h5>
                    <div style="min-width: 200px;">
                        <label for="filtroArea" class="form-label mb-0 me-2 fw-bold" style="color: #0b2a4a;">
                            <i class="fas fa-filter me-1"></i>Filtrar por Área:
                        </label>
                        <select id="filtroArea" class="form-select d-inline-block w-auto">
                            <option value="">Todas</option>
                            <option value="UFREMID">UFREMID</option>
                            <option value="UFRESA">UFRESA</option>
                            <option value="UFRESBIT">UFRESBIT</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive table-modern">
                    <table id="example" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Área</th>
                                <th>Nº EST.</th>
                                <th>N. Comercial</th>
                                <th>CAT.</th>
                                <th>RUC</th>
                                <th>Razón Social</th>
                                <th>Provincia</th>
                                <th>Distrito</th>
                                <th>Dirección</th>
                                <th>Q.F.</th>
                                <th>Situación</th>
                                <th>Estado IPRESS</th>
                                <th>Institución</th>
                                <th>Tipo</th>
                                <th>Clasificación</th>
                                <th>Categorización</th>
                                <th>Inicio Actividad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y') ?> Sub Gerencia de Regulación Sectorial - Todos los derechos reservados.</p>
        </div>
    </footer>

    <?php include 'boostrap-js.php'; ?>
    <?php include 'datatable-js.php'; ?>
    <?php include 'select2-js.php'; ?>

    <script>
        $(document).ready(function() {
            var table = $('#example').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                responsive: true,
                order: [[0, 'desc']],
                data: <?= $jsonData ?>,  // Los datos desde PHP
                columns: [
                    { data: 0 },  // ID
                    { data: 1 },  // Área
                    { data: 2 },  // Nº EST.
                    { data: 3 },  // N. Comercial
                    { data: 4 },  // CAT.
                    { data: 5 },  // RUC
                    { data: 6 },  // Razón Social
                    { data: 7 },  // Provincia
                    { data: 8 },  // Distrito
                    { data: 9 },  // Dirección
                    { data: 10 }, // Q.F.
                    { data: 11 }, // S. DIGEMID
                    { data: 12 }, // Estado IPRESS
                    { data: 13 }, // Institución
                    { data: 14 }, // Tipo
                    { data: 15 }, // Clasificación
                    { data: 16 }, // Categorización
                    { data: 17 }, // Inicio Actividad
                    { data: 18 }  // Acciones
                ]
            });

            // Función para mostrar/ocultar columnas según el área seleccionada
            function toggleTableColumns(area) {
            // Mostrar todas las columnas primero
            table.column(10).visible(true);  // Q.F.
            table.column(11).visible(true);  // S. DIGEMID
            table.column(12).visible(true);  // Estado IPRESS
            table.column(13).visible(true);  // Institución
            table.column(14).visible(true);  // Tipo
            table.column(15).visible(true);  // Clasificación
            table.column(16).visible(true);  // Categorización
            table.column(17).visible(true);  // Inicio Actividad

            if (area === 'UFREMID') {
                table.column(10).visible(true);   // Q.F.
                table.column(11).visible(true);   // S. DIGEMID
                table.column(12).visible(false);
                table.column(13).visible(false);
                table.column(14).visible(false);
                table.column(15).visible(false);
                table.column(16).visible(false);
                table.column(17).visible(false);
            } else if (area === 'UFRESA') {
                table.column(10).visible(false);   // Q.F.
                table.column(11).visible(true);    // S. DIGEMID
                table.column(12).visible(false);
                table.column(13).visible(false);
                table.column(14).visible(false);
                table.column(15).visible(false);
                table.column(16).visible(false);
                table.column(17).visible(false);
            } else if (area === 'UFRESBIT') {
                table.column(10).visible(false);   // Q.F.
                table.column(11).visible(false);   // S. DIGEMID
                table.column(12).visible(true);
                table.column(13).visible(true);
                table.column(14).visible(true);
                table.column(15).visible(true);
                table.column(16).visible(true);    // Categorización
                table.column(17).visible(true);    // Inicio Actividad
            } else {
                    // 'Todas': mostrar todas (ya están visibles)
                }

                // Aplicar filtro de filas por área (columna 1 = Área)
                if (area === '') {
                    table.column(1).search('').draw();
                } else {
                    table.column(1).search(area).draw();
                }
            }

            // Evento del filtro de área
            $('#filtroArea').on('change', function() {
                var area = $(this).val();
                toggleTableColumns(area);
            });

            // Inicializar el filtro mostrando todas las columnas (o según el área por defecto)
            // Por defecto, mostrar todas las columnas.
            // Si prefieres que al cargar la página muestre solo las de UFREMID, cambia el valor del select.
            $('#filtroArea').val(''); // 'Todas'
            toggleTableColumns('');

            // Inicializar Select2
            if ($.fn.select2) {
                $('.select2-auto').select2({
                    width: '100%',
                    placeholder: 'Buscar...',
                    allowClear: true
                });
            }

            // Función para mostrar/ocultar campos según área
            function toggleCampos(area) {
                // Ocultar todos los bloques
                $('#camposUFREMIDUFRESA').hide();
                $('#camposUFRESBIT').hide();

                // Mostrar el correspondiente
                if (area === 'UFREMID' || area === 'UFRESA') {
                    $('#camposUFREMIDUFRESA').show();
                    // // Filtrar categorías según área
                    // $('#txtCategoria option').each(function() {
                    //     var id = parseInt($(this).val());
                    //     if (area === 'UFREMID' && id <= 8) {
                    //         $(this).show();
                    //     } else if (area === 'UFRESA' && id >= 9) {
                    //         $(this).show();
                    //     } else {
                    //         $(this).hide();
                    //     }
                    // });
                    // Hacer required los campos de UFREMID/UFRESA
                    $('#txtCategoria, #txtSituacionDigemid').prop('required', true);
                    // Quitar required de campos ipress
                    $('#txtEstadoRenipress, #txtInstitucionRenipress, #txtTipoRenipress, #txtClasificacionRenipress').prop('required', false);

                    // *** NUEVO: Controlar el checkbox Q.F. ***
                    if (area === 'UFREMID') {
                        $('#campoQF').show();
                        $('#txtTieneQuimicoFarmaceutico').prop('required', false); // no es obligatorio, solo se muestra
                    } else if (area === 'UFRESA') {
                        $('#campoQF').hide();
                        $('#txtTieneQuimicoFarmaceutico').prop('checked', false); // desmarcar si estaba
                        $('#txtTieneQuimicoFarmaceutico').prop('required', false);
                    }
                } else if (area === 'UFRESBIT') {
                    $('#camposUFRESBIT').show();
                    // Ocultar todas las categorías (no se usa)
                    $('#txtCategoria option').hide();
                    // Quitar required de UFREMID/UFRESA
                    $('#txtCategoria, #txtSituacionDigemid, #txtTieneQuimicoFarmaceutico').prop('required', false);
                    // Hacer required campos ipress
                    $('#txtEstadoRenipress, #txtInstitucionRenipress, #txtTipoRenipress, #txtClasificacionRenipress').prop('required', true);
                    // Ocultar Q.F. si está visible
                    $('#campoQF').hide();
                    $('#txtTieneQuimicoFarmaceutico').prop('checked', false);
                }
            }

            // Evento cambio de área
            $('#txtAreaOrigen').change(function() {
                var area = $(this).val();
                toggleCampos(area);
                // Resetear selects que quedan ocultos (opcional)
                if (area !== 'UFREMID' && area !== 'UFRESA') {
                    $('#txtCategoria').val('');
                    $('#txtSituacionDigemid').val('');
                    $('#txtTieneQuimicoFarmaceutico').prop('checked', false);
                } else {
                    $('#txtEstadoRenipress, #txtInstitucionRenipress, #txtTipoRenipress, #txtClasificacionRenipress').val('');
                }
                // Si es UFRESBIT, cargar clasificaciones según el tipo seleccionado (si hay)
                if (area === 'UFRESBIT') {
                    cargarClasificaciones($('#txtTipoRenipress').val());
                }
                // Recargar categorías vía AJAX
                $.ajax({
                    type: 'POST',
                    url: '../persistencia/dCategoria.php',
                    data: { area: area, action: 'listarPorArea' },
                    dataType: 'json',
                    success: function(categorias) {
                        var options = '<option value="">Seleccionar</option>';
                        $.each(categorias, function(index, cat) {
                            options += '<option value="' + cat.idCategoria + '">' + cat.nombre + '</option>';
                        });
                        $('#txtCategoria').html(options);
                        $('#txtCategoria').trigger('change');
                    },
                    error: function() {
                        alert('Error al cargar categorías');
                    }
                });
            });

            // Carga dinámica de clasificaciones según tipo
            function cargarClasificaciones(idTipo) {
                if (idTipo) {
                    $.ajax({
                        type: 'POST',
                        url: '../persistencia/dRenipress.php', // creamos un endpoint para obtener clasificaciones
                        data: { idTipo: idTipo },
                        dataType: 'json',
                        success: function(data) {
                            var options = '<option value="">Seleccionar</option>';
                            $.each(data, function(index, item) {
                                options += '<option value="' + item.idClasificacionRenipress + '">' + item.nombre + '</option>';
                            });
                            $('#txtClasificacionRenipress').html(options);
                            // Si estamos editando y hay un valor guardado, seleccionarlo
                            var valorActual = '<?php echo $txtClasificacionRenipress ?>';
                            if (valorActual) {
                                $('#txtClasificacionRenipress').val(valorActual);
                            }
                            // Actualizar Select2
                            $('#txtClasificacionRenipress').trigger('change');
                        },
                        error: function() {
                            alert('Error al cargar clasificaciones');
                        }
                    });
                } else {
                    $('#txtClasificacionRenipress').html('<option value="">Seleccionar</option>');
                    $('#txtClasificacionRenipress').trigger('change');
                }
            }

            // Evento cambio de tipo para cargar clasificaciones
            $('#txtTipoRenipress').change(function() {
                var idTipo = $(this).val();
                cargarClasificaciones(idTipo);
            });

            // Carga dinámica de provincias/distritos (como antes)
            $('#txtDepartamento').change(function() {
                const idDepartamento = $(this).val();
                const url = $(this).data('url');
                if (idDepartamento) {
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: { idDepartamento: idDepartamento },
                        dataType: 'json',
                        success: function(provincias) {
                            let options = '<option value="">Seleccionar</option>';
                            provincias.forEach(function(prov) {
                                options += `<option value="${prov.idProvincia}">${prov.nombre}</option>`;
                            });
                            $('#txtProvincia').html(options);
                            $('#txtProvincia').trigger('change');
                        },
                        error: function() {
                            alert('Error al cargar provincias');
                        }
                    });
                } else {
                    $('#txtProvincia').html('<option value="">Seleccionar</option>');
                    $('#txtProvincia').trigger('change');
                }
            });

            $('#txtProvincia').change(function() {
                const idProvincia = $(this).val();
                const url = $(this).data('url');
                if (idProvincia) {
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: { idProvincia: idProvincia },
                        dataType: 'json',
                        success: function(distritos) {
                            let options = '<option value="">Seleccionar</option>';
                            distritos.forEach(function(dist) {
                                options += `<option value="${dist.idDistrito}">${dist.nombre}</option>`;
                            });
                            $('#txtDistrito').html(options);
                            $('#txtDistrito').trigger('change');
                        },
                        error: function() {
                            alert('Error al cargar distritos');
                        }
                    });
                } else {
                    $('#txtDistrito').html('<option value="">Seleccionar</option>');
                    $('#txtDistrito').trigger('change');
                }
            });

            // Al cargar la página, aplicar el estado inicial según el área seleccionada
            var areaInicial = $('#txtAreaOrigen').val();
            toggleCampos(areaInicial);
            if (areaInicial === 'UFRESBIT' && $('#txtTipoRenipress').val()) {
                cargarClasificaciones($('#txtTipoRenipress').val());
            }
        });

        // Funciones de eliminar y cancelar
        function eliminar(id) {
            if (confirm('¿Está seguro de eliminar esta sede?')) {
                window.location.href = 'eliminarSede.php?id=' + id;
            }
        }
        function cancelar() {
            window.location.href = '<?php echo $_SERVER['PHP_SELF'] ?>';
        }
    </script>
</body>
</html>