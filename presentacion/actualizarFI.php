<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../persistencia/conexion.php');
include_once(__DIR__ . '/../persistencia/dExpediente.php');
include_once(__DIR__ . '/../persistencia/dPlazos.php');

$pdo = Database::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idExpedienteFI = $_POST['idExpedienteFI'] ?? 0;
    $idExpediente = $_POST['idExpediente'] ?? 0;
    $fechaNotificacionInicioPAS = $_POST['fechaNotificacionInicioPAS'] ?? '';
    $fechaDescargoPresentado = $_POST['fechaDescargoPresentado'] ?? '';
    $documentoElevaEscrito = $_POST['documentoElevaEscrito'] ?? '';
    $informeLegalCaducidad = $_POST['informeLegalCaducidad'] ?? '';
    $resolucionCaducidad = $_POST['resolucionCaducidad'] ?? '';
    $recursoInterpuesto = $_POST['recursoInterpuesto'] ?? '';
    $resolucionRecurso = $_POST['resolucionRecurso'] ?? '';
    $fechaNotificacionRecurso = $_POST['fechaNotificacionRecurso'] ?? '';
    $informeFinalInstruccion = $_POST['informeFinalInstruccion'] ?? '';

    if ($idExpedienteFI && $idExpediente) {
        try {
            $pdo->beginTransaction();

            // Actualizar registro FI
            $data = [
                'idExpedienteFI' => $idExpedienteFI,
                'fechaNotificacionInicioPAS' => $fechaNotificacionInicioPAS,
                'fechaDescargoPresentado' => $fechaDescargoPresentado,
                'documentoElevaEscrito' => $documentoElevaEscrito,
                'informeLegalCaducidad' => $informeLegalCaducidad,
                'resolucionCaducidad' => $resolucionCaducidad,
                'recursoInterpuesto' => $recursoInterpuesto,
                'resolucionRecurso' => $resolucionRecurso,
                'fechaNotificacionRecurso' => $fechaNotificacionRecurso,
                'informeFinalInstruccion' => $informeFinalInstruccion
            ];
            actualizarExpedienteFI($pdo, $data);

            // Eliminar plazos antiguos y recalcular
            $sqlDelete = "DELETE FROM expediente_plazos WHERE idExpediente = ? AND evento IN ('DESCARGO_PAS', 'CADUCIDAD_PAS')";
            $stmtDel = $pdo->prepare($sqlDelete);
            $stmtDel->execute([$idExpediente]);

            if ($fechaNotificacionInicioPAS) {
                $fechaVencimientoDescargo = calcularDiasHabiles($pdo, $fechaNotificacionInicioPAS, 5);
                insertarPlazo($pdo, $idExpediente, 'DESCARGO_PAS', $fechaNotificacionInicioPAS, 5, 'DIAS_HABILES', $fechaVencimientoDescargo);

                $fechaCaducidad = date('Y-m-d', strtotime($fechaNotificacionInicioPAS . ' +9 months'));
                insertarPlazo($pdo, $idExpediente, 'CADUCIDAD_PAS', $fechaNotificacionInicioPAS, 9, 'MESES', $fechaCaducidad);
            }

            $pdo->commit();
            $mensaje = "Registro actualizado correctamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "Error: " . $e->getMessage();
        }
    } else {
        $mensaje = "Datos incompletos.";
    }
    header("Location: formExpedienteFI.php?idExpediente=" . $idExpediente . "&mensaje=" . urlencode($mensaje));
    exit;
} else {
    header("Location: formExpedienteUFREMID.php");
    exit;
}