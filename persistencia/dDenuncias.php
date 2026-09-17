<?php
// persistencia/dDenuncias.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía la denuncia por correo usando SMTP autenticado (PHPMailer + Gmail).
 * No guarda nada en disco ni en BD.
 */
function registrarDenuncia(array $data, $archivo = null)
{
    require_once __DIR__ . '/../vendor/autoload.php';

    // --------------------------------------------
    // Destinatario (cambiar en producción)
    // Para pruebas: pon tu correo personal.
    // --------------------------------------------
    //$destinatario = 'sanandreas86@hotmail.com';
    $destinatario = 'regulacion_sectorial@diresalalibertad.gob.pe';

    $mail = new PHPMailer(true);

    try {
        // ------- Configuración SMTP Gmail -------
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sgrssoporte@gmail.com';
        $mail->Password   = 'cvcsjslwawgjckfl';   // App Password sin espacios
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Para depurar si algo falla, descomenta esta línea (y bórrala luego):
        // $mail->SMTPDebug = 2;

        // ------- Remitente / destinatario -------
        $mail->setFrom('sgrssoporte@gmail.com', 'Sistema SGRS - Denuncias');
        $mail->addAddress($destinatario);

        // ------- Datos del formulario -------
        $anonimo    = !empty($data['anonimo']) ? 'SÍ (anónima)' : 'NO';
        $nombre     = !empty($data['nombre'])    ? $data['nombre']    : 'No especificado';
        $correo     = !empty($data['correo'])    ? $data['correo']    : 'No especificado';
        $inspector  = !empty($data['inspector']) ? $data['inspector'] : 'No especificado';
        $estableci  = $data['establecimiento'] ?? '';
        $area       = $data['area'] ?? '';
        $motivo     = $data['motivo'] ?? '';

        // Si el denunciante dejó su correo, lo usamos como Reply-To
        if (!empty($data['correo']) && filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($data['correo'], $data['nombre'] ?? '');
        }

        // ------- Asunto -------
        $mail->Subject = 'Nueva Denuncia Registrada - ' . ($area ?: 'SIN ÁREA');

        // ------- Cuerpo (texto plano) -------
        $cuerpo  = "NUEVA DENUNCIA REGISTRADA\n";
        $cuerpo .= "==========================\n\n";
        $cuerpo .= "Anónima: $anonimo\n";
        $cuerpo .= "Nombre: $nombre\n";
        $cuerpo .= "Correo: $correo\n";
        $cuerpo .= "Inspector denunciado: $inspector\n";
        $cuerpo .= "Establecimiento: $estableci\n";
        $cuerpo .= "Área: $area\n\n";
        $cuerpo .= "Motivo / Descripción:\n";
        $cuerpo .= "--------------------\n";
        $cuerpo .= $motivo . "\n\n";
        $cuerpo .= "Fecha de registro: " . date('Y-m-d H:i:s') . "\n";

        $mail->isHTML(false);
        $mail->Body = $cuerpo;

        // ------- Adjunto opcional -------
        if ($archivo
            && isset($archivo['tmp_name'])
            && $archivo['error'] === UPLOAD_ERR_OK
            && is_uploaded_file($archivo['tmp_name'])) {
            $mail->addAttachment($archivo['tmp_name'], $archivo['name']);
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        throw new Exception('Error al enviar la denuncia: ' . $mail->ErrorInfo);
    }
}