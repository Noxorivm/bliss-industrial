<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// --- Incluir Autoloader de Composer (para PHPMailer) ---
// Asume que este script está en /forms/ y vendor/ está en la raíz del proyecto.
$autoloader_path = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader_path)) {
    // Para AJAX con validate.js, die() es suficiente para que muestre el error.
    // Es importante que el header Content-Type no se envíe si hay un error fatal antes.
    // O podrías devolver un JSON de error si tu JS lo maneja mejor.
    // Por simplicidad con validate.js, un mensaje de error directo es lo más compatible.
    http_response_code(500); // Internal Server Error
    die('Error crítico: Falta la librería de envío (autoload). Contacta al administrador. Ruta buscada: ' . realpath(dirname(__FILE__)) . '/' . $autoloader_path);
}
require $autoloader_path;

// --- Usar clases de PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- Email donde recibirás las notificaciones ---
$receiving_email_address = 'hola@blissindustrial.eu';
// === Clave Secreta reCAPTCHA ===
$recaptcha_secret_key = '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N'; // TU CLAVE SECRETA
// ===============================

// === Verificación reCAPTCHA ===
if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $verify_data = [
        'secret'   => $recaptcha_secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verify_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($verify_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $google_response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        if ($curl_error) {
            error_log("reCAPTCHA cURL Error (contact.php): " . $curl_error);
            die('Error al verificar reCAPTCHA (conexión con Google).');
        }
    } else { // Fallback a file_get_contents si cURL no está disponible
        $options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($verify_data), 'timeout' => 10]];
        $context  = stream_context_create($options);
        $google_response = file_get_contents($verify_url, false, $context);
    }

    if ($google_response === false) {
        error_log("reCAPTCHA (contact.php): No se pudo obtener respuesta de Google.");
        die('Error al verificar reCAPTCHA (no se pudo conectar con Google).');
    }
    $response_keys = json_decode($google_response, true);

    if (!(isset($response_keys["success"]) && $response_keys["success"] == true)) {
        $error_codes = isset($response_keys['error-codes']) ? implode(', ', $response_keys['error-codes']) : 'desconocido';
        error_log("reCAPTCHA verification failed (contact.php). Error codes: " . $error_codes);
        die('Verificación reCAPTCHA fallida. Por favor, inténtalo de nuevo.');
    }
} else {
    die('Por favor, completa el verificador reCAPTCHA.');
}
// === Fin Verificación reCAPTCHA ===

// --- Obtener datos del POST y sanearlos ---
$nombre_contacto = isset($_POST['nombre_contacto']) ? htmlspecialchars(trim($_POST['nombre_contacto'])) : 'No proporcionado';
$correo = isset($_POST['correo']) ? htmlspecialchars(trim($_POST['correo'])) : 'No proporcionado';
$nombre_empresa = isset($_POST['nombre_empresa']) ? htmlspecialchars(trim($_POST['nombre_empresa'])) : 'No proporcionada';
$mensaje_usuario = isset($_POST['mensaje']) ? htmlspecialchars(trim($_POST['mensaje'])) : 'Sin mensaje.';
$servicio_interes_hidden = isset($_POST['servicio_interes']) ? htmlspecialchars(trim($_POST['servicio_interes'])) : 'No especificado';

// --- Validación básica de campos ---
if (empty($nombre_contacto) || $nombre_contacto === 'No proporcionado' ||
    empty($correo) || $correo === 'No proporcionado' || !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    empty($nombre_empresa) || $nombre_empresa === 'No proporcionada' ||
    empty($mensaje_usuario) || $mensaje_usuario === 'Sin mensaje.') {
    die('Por favor, completa todos los campos obligatorios con información válida.');
}

// --- Enviar Notificación por Email usando PHPMailer ---
$mail = new PHPMailer(true);

try {
    // === Configuraciones del servidor SMTP (!!! REEMPLAZA CON TUS DATOS DE IONOS !!!) ===
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomenta para depuración detallada
    $mail->isSMTP();
    $mail->Host       = 'smtp.ionos.es';         // Servidor SMTP de IONOS (o el que uses)
    $mail->SMTPAuth   = true;
    $mail->Username   = 'hola@blissindustrial.eu'; // TU dirección de correo completa de IONOS
    $mail->Password   = '*STS#ssoluttionss#2023*';    // TU contraseña de ese correo
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usar STARTTLS con IONOS
    $mail->Port       = 587;                   // Puerto para STARTTLS (o 465 para SMTPS)
    $mail->CharSet    = 'UTF-8';                 // Para caracteres españoles

    // === Remitente y Destinatarios ===
    // Usa un email de tu dominio que exista y esté configurado para enviar.
    $mail->setFrom('hola@blissindustrial.eu', 'Web Contacto Bliss');
    $mail->addAddress($receiving_email_address, 'Admin Bliss');
    $mail->addReplyTo($correo, $nombre_contacto); // Para poder responder al cliente fácilmente

    // === Contenido del Email ===
    $mail->isHTML(false); // Enviar como texto plano
    $mail->Subject = 'Nuevo Mensaje de Contacto Web: ' . $nombre_empresa;

    // Construir cuerpo del email
    $emailBody = "Has recibido un nuevo mensaje de contacto desde la web:\n\n";
    $emailBody .= "Nombre Contacto: " . $nombre_contacto . "\n";
    $emailBody .= "Email: " . $correo . "\n";
    $emailBody .= "Empresa: " . $nombre_empresa . "\n";
    if ($servicio_interes_hidden !== 'No especificado' && $servicio_interes_hidden !== 'No Aplicable (Formulario Final Index)') {
         $emailBody .= "Asunto/Servicio (Info): " . $servicio_interes_hidden . "\n";
    }
    $emailBody .= "Mensaje:\n" . $mensaje_usuario . "\n\n";
    $emailBody .= "IP Origen: " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida') . "\n";
    $emailBody .= "Fecha Envío: " . date('Y-m-d H:i:s') . "\n";

    $mail->Body = $emailBody;

    if ($mail->send()) {
        // El script validate.js espera "OK" en caso de éxito
        echo "OK";
    } else {
         error_log("PHPMailer Error en contact.php: {$mail->ErrorInfo}");
         // El script validate.js mostrará este mensaje en el div .error-message
         die("El mensaje no pudo ser enviado. Por favor, inténtalo más tarde. Error: {$mail->ErrorInfo}");
    }

} catch (Exception $e) {
    error_log("PHPMailer Exception en contact.php: {$mail->ErrorInfo}");
    // El script validate.js mostrará este mensaje en el div .error-message
    die("El mensaje no pudo ser enviado. Por favor, inténtalo más tarde. Excepción: {$mail->ErrorInfo}");
}
?>