<?php

function insertarSede(PDO $pdo, array $data)
{
    $idEstablecimiento = $data['idEstablecimiento'];
    $nombreComercial = $data['nombre'];
    $numeroEstacion = $data['numeroEstacion'] ?? null;
    $fechaRegistroSi = $data['fechaRegistroSi'] ?? null;
    $idCategoria = $data['idCategoria'] ?? null;
    $idSituacionEstablecimiento = $data['idSituacionEstablecimiento'] ?? null;
    $direccion = $data['direccion'];
    $telefono = $data['telefono'] ?? null;
    $tieneQuimicoFarmaceutico = $data['tieneQuimicoFarmaceutico'] ?? 0;
    $idDepartamento = $data['idDepartamento'] ?? null;
    $idProvincia = $data['idProvincia'] ?? null;
    $idDistrito = $data['idDistrito'] ?? null;
    $horarioFuncionamiento = $data['horarioFuncionamiento'] ?? null;
    $idSituacionDigemid = $data['idSituacionDigemid'] ?? null;
    // Nuevos campos
    $areaOrigen = $data['areaOrigen'] ?? null;
    $idEstadoRenipress = $data['idEstadoRenipress'] ?? null;
    $idInstitucionRenipress = $data['idInstitucionRenipress'] ?? null;
    $idTipoRenipress = $data['idTipoRenipress'] ?? null;
    $idClasificacionRenipress = $data['idClasificacionRenipress'] ?? null;
    // NUEVOS CAMPOS UFRESBIT
    $categorizacion = $data['categorizacion'] ?? null;
    $inicioActividad = $data['inicioActividad'] ?? null;

    $sql = "INSERT INTO sede (
                idEstablecimiento, nombre, numeroEstacion, fechaRegistroSi, 
                idCategoria, idSituacionEstablecimiento, direccion, telefono, 
                tieneQuimicoFarmaceutico, idSituacionDigemid, 
                idDepartamento, idProvincia, idDistrito, horarioFuncionamiento,
                areaOrigen, idEstadoRenipress, idInstitucionRenipress, 
                idTipoRenipress, idClasificacionRenipress,
                categorizacion, inicioActividad
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idEstablecimiento, $nombreComercial, $numeroEstacion, $fechaRegistroSi,
        $idCategoria, $idSituacionEstablecimiento, $direccion, $telefono,
        $tieneQuimicoFarmaceutico, $idSituacionDigemid,
        $idDepartamento, $idProvincia, $idDistrito, $horarioFuncionamiento,
        $areaOrigen, $idEstadoRenipress, $idInstitucionRenipress,
        $idTipoRenipress, $idClasificacionRenipress,
        $categorizacion, $inicioActividad
    ]);
    return $pdo->lastInsertId();
}

function actualizarSede(PDO $pdo, array $data)
{
    $idSede = $data['idSede'] ?? null;
    $idEstablecimiento = $data['idEstablecimiento'] ?? null;
    $nombreComercial = $data['nombre'] ?? null;
    $numeroEstacion = $data['numeroEstacion'] ?? null;
    $fechaRegistroSi = $data['fechaRegistroSi'] ?? null;
    $idCategoria = $data['idCategoria'] ?? null;
    $idSituacionEstablecimiento = $data['idSituacionEstablecimiento'] ?? null;
    $direccion = $data['direccion'] ?? null;
    $telefono = $data['telefono'] ?? null;
    $tieneQuimicoFarmaceutico = $data['tieneQuimicoFarmaceutico'] ?? 0;
    $idDepartamento = $data['idDepartamento'] ?? null;
    $idProvincia = $data['idProvincia'] ?? null;
    $idDistrito = $data['idDistrito'] ?? null;
    $horarioFuncionamiento = $data['horarioFuncionamiento'] ?? null;
    $idSituacionDigemid = $data['idSituacionDigemid'] ?? null;
    // Nuevos campos
    $areaOrigen = $data['areaOrigen'] ?? null;
    $idEstadoRenipress = $data['idEstadoRenipress'] ?? null;
    $idInstitucionRenipress = $data['idInstitucionRenipress'] ?? null;
    $idTipoRenipress = $data['idTipoRenipress'] ?? null;
    $idClasificacionRenipress = $data['idClasificacionRenipress'] ?? null;
    // NUEVOS CAMPOS UFRESBIT
    $categorizacion = $data['categorizacion'] ?? null;
    $inicioActividad = $data['inicioActividad'] ?? null;

    $sql = "UPDATE sede SET 
                idEstablecimiento = ?,
                nombre = ?,
                numeroEstacion = ?,
                fechaRegistroSi = ?,
                idCategoria = ?,
                idSituacionEstablecimiento = ?,
                direccion = ?,
                telefono = ?,
                tieneQuimicoFarmaceutico = ?,
                idSituacionDigemid = ?,
                idDepartamento = ?,
                idProvincia = ?,
                idDistrito = ?,
                horarioFuncionamiento = ?,
                areaOrigen = ?,
                idEstadoRenipress = ?,
                idInstitucionRenipress = ?,
                idTipoRenipress = ?,
                idClasificacionRenipress = ?,
                categorizacion = ?,
                inicioActividad = ?
            WHERE idSede = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $idEstablecimiento, $nombreComercial, $numeroEstacion, $fechaRegistroSi,
        $idCategoria, $idSituacionEstablecimiento, $direccion, $telefono,
        $tieneQuimicoFarmaceutico, $idSituacionDigemid,
        $idDepartamento, $idProvincia, $idDistrito, $horarioFuncionamiento,
        $areaOrigen, $idEstadoRenipress, $idInstitucionRenipress,
        $idTipoRenipress, $idClasificacionRenipress,
        $categorizacion, $inicioActividad,
        $idSede
    ]);
    return $stmt->rowCount();
}


function listarSedes(PDO $pdo)
{
    $sql = "SELECT 
                s.idSede,
                s.idEstablecimiento,
                s.idCategoria,
                s.idSituacionEstablecimiento,
                s.idSituacionDigemid,
                s.idDepartamento,
                s.idProvincia,
                s.idDistrito,
                s.numeroEstacion,
                s.nombre,
                s.fechaRegistroSi, 
                s.direccion,
                s.tieneQuimicoFarmaceutico,
                s.horarioFuncionamiento,
                s.telefono,
                s.areaOrigen,
                s.idEstadoRenipress,
                s.idInstitucionRenipress,
                s.idTipoRenipress,
                s.idClasificacionRenipress,
                s.categorizacion,
                s.inicioActividad,
                (SELECT c.nombre FROM categoria c WHERE c.idCategoria = s.idCategoria) AS categoria,
                (SELECT e.ruc FROM establecimiento e WHERE e.idEstablecimiento = s.idEstablecimiento) AS ruc,
                (SELECT e.razonSocial FROM establecimiento e WHERE e.idEstablecimiento = s.idEstablecimiento) AS razonSocial,
                (SELECT p.nombre FROM provincia p WHERE p.idProvincia = s.idProvincia) AS provincia,
                (SELECT d.nombre FROM distrito d WHERE d.idDistrito = s.idDistrito) AS distrito,
                (SELECT se.nombre FROM situacion_establecimiento se WHERE se.idSituacionEstablecimiento = s.idSituacionEstablecimiento) AS situacion_establecimiento,
                (SELECT sd.nombre FROM situacion_digemid sd WHERE sd.idSituacionDigemid = s.idSituacionDigemid) AS Situacion_Digemid,
                (SELECT er.descripcion FROM estadoRenipress er WHERE er.id_estado = s.idEstadoRenipress) AS estadoRenipress,
                (SELECT ir.nombre FROM InsticionesRenipress ir WHERE ir.idInsticionRenipress = s.idInstitucionRenipress) AS institucionRenipress,
                (SELECT tr.nombre FROM tipoRenipress tr WHERE tr.idTipoRenipress = s.idTipoRenipress) AS tipoRenipress,
                (SELECT cr.nombre FROM clasificacionRenipress cr WHERE cr.idClasificacionRenipress = s.idClasificacionRenipress) AS clasificacionRenipress
            FROM sede s 
            WHERE s.activo = 1";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function eliminarSede(PDO $pdo, $idSede)
{
    // Borrado lógico (actualizar activo = 0)
    $sql = "UPDATE sede SET activo = 0 WHERE idSede = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSede]);
    return $stmt->rowCount();
}

