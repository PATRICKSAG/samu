<?php

    include_once __DIR__ . '/../config.php';
    include_once __DIR__ . '/../persistencia/conexion.php';
    // VERIFICACIÓN DE SESIÓN (AGREGAR ESTO)
    include_once(__DIR__ . '/auth_check.php');


    $pdo = Database::getConexion();

    $sql              = "SELECT * FROM establecimiento";
    $stmt             = $pdo->query($sql);
    $establecimientos = $stmt->fetchAll();

    extract($_POST);

    if (isset($_REQUEST['btnConsultar'])) {
    }

    $sql = "SELECT
    s.idSede,
    s.numeroEstacion,
    (SELECT nombre FROM categoria c WHERE c.idCategoria = s.idCategoria) AS categoria,
    e.ruc, e.razonSocial,
    s.direccion,
    s.idDistrito,
    s.idProvincia,
    (SELECT nombre FROM distrito d WHERE d.idDistrito= s.idDistrito) AS distrito,
    (SELECT nombre FROM provincia p WHERE p.idProvincia = s.idProvincia ) AS provincia,
    (SELECT COUNT(ex.idExpediente) FROM expediente ex WHERE s.idSede = ex.idSede) AS 'totalInspecciones'
    FROM sede s
    INNER JOIN establecimiento e
    ON s.idEstablecimiento = e.idEstablecimiento;";
    $stmt  = $pdo->query($sql);
    $sedes = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <?php include 'boostrap-css.php'; ?>
</head>

<body>

    <?php include 'header.php'; ?>

    <?php include 'datatable-css.php'; ?>

    <div class="container">
        <form action="" method="POST">
            <div class="row">
                <div class="col mb-1">
                    <label for="">Fecha Inicio</label>
                    <input type="date" class="form-control" name="txtFechaInicio">
                </div>
                <div class="col mb-1">
                    <label for="">Fecha Fin</label>
                    <input type="date" class="form-control" name="txtFechaFin">
                </div>
                <div class="col-12 mb-1">
                    <label for="">Establecimiento</label>
                    <select name="" id="" class="form-select">
                        <option value="_all_">Todos</option>
                        <?php foreach ($establecimientos as $establecimiento) {?>
                            <option value="<?php echo $establecimiento['idEstablecimiento'] ?>"><?php echo $establecimiento['ruc'] ?> - <?php echo $establecimiento['razonSocial'] ?></option>
                        <?php }?>
                    </select>
                </div>
            </div>

            <button type="submit" name="btnConsultar" class="btn btn-success">Consultar</button>
        </form>


        <table id="example" class="table table-striped table-bordered mt-1" style="width:100%">
            <thead class="table-dark">
                <tr>
                    <th>ID.</th>
                    <th>Nº EST.</th>
                    <th>CAT.</th>
                    <th>RUC</th>
                    <th>RAZON SOCIAL</th>
                    <th>DIRECCIÓN</th>
                    <th>DISTRITO</th>
                    <th>PROV</th>
                    <th>INSPECCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sedes as $sede) {?>
                    <tr>
                        <td><?php echo $sede['idSede'] ?></td>
                        <td><?php echo $sede['numeroEstacion'] ?></td>
                        <td><?php echo $sede['categoria'] ?></td>
                        <td><?php echo $sede['ruc'] ?></td>
                        <td><?php echo $sede['razonSocial'] ?></td>
                        <td><?php echo $sede['direccion'] ?></td>
                        <td><?php echo $sede['distrito'] ?></td>
                        <td><?php echo $sede['provincia'] ?></td>
                        <td><?php echo $sede['totalInspecciones'] ?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>

    </div>


    <?php include 'boostrap-js.php'; ?>

    <?php include 'datatable-js.php'; ?>

    <script>
        const datable = new DataTable('#example');
    </script>

</body>

</html>