<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

$receiving_email_address = 'hola@blissindustrial.eu';
// === Clave Secreta reCAPTCHA ===
$recaptcha_secret_key = '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N'; // TU CLAVE SECRETA
// ===============================

// Verificar reCAPTCHA ANTES de cargar la librería de email o procesar datos
if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
    $recaptcha_response = $_POST['g-recaptcha-response'];

    // Construir la URL de verificación
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $verify_data = [
        'secret'   => $recaptcha_secret_key,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null // Opcional pero recomendado
    ];

    // Usar cURL si está disponible (más robusto que file_get_contents para POST)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verify_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($verify_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos
        $google_response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log("reCAPTCHA cURL Error: " . $curl_error);
            die('Error al verificar reCAPTCHA (problema de conexión con Google).');
        }
    } else {
        // Fallback a file_get_contents (menos ideal para POST)
        $options = ['http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($verify_data),
            'timeout' => 10
        ]];
        $context  = stream_context_create($options);
        $google_response = file_get_contents($verify_url, false, $context);
    }


    if ($google_response === false) {
        error_log("reCAPTCHA: No se pudo obtener respuesta de Google.");
        die('Error al verificar reCAPTCHA (no se pudo conectar con Google).');
    }

    $response_keys = json_decode($google_response, true);

    if (isset($response_keys["success"]) && $response_keys["success"] == true) {
        // reCAPTCHA válido, continuar
    } else {
        // Falló reCAPTCHA
        $error_codes = isset($response_keys['error-codes']) ? implode(', ', $response_keys['error-codes']) : 'desconocido';
        error_log("reCAPTCHA verification failed. Error codes: " . $error_codes);
        die('Verificación reCAPTCHA fallida. Por favor, inténtalo de nuevo.');
    }
} else {
    die('Por favor, completa el verificador reCAPTCHA.');
}
// Si reCAPTCHA es válido, continuar con el resto del script...


// --- Cargar Librería PHP Email Form ---
// Asegúrate que la ruta es correcta relativa a la ubicación de este script (forms/contact.php)
$php_email_form_path = '../assets/vendor/php-email-form/php-email-form.php';
if( file_exists($php_email_form_path) ) {
  include( $php_email_form_path );
} else {
  // Este error se mostrará en el div .error-message del formulario
  die( 'Error: No se pudo cargar la librería de envío de correo.');
}
// --- Fin Cargar Librería ---

// --- Obtener datos del POST ---
// (Usar htmlspecialchars para sanear la entrada antes de usarla en el email)
$nombre_contacto = isset($_POST['nombre_contacto']) ? htmlspecialchars(trim($_POST['nombre_contacto'])) : 'No proporcionado';
$correo = isset($_POST['correo']) ? htmlspecialchars(trim($_POST['correo'])) : 'No proporcionado';
$nombre_empresa = isset($_POST['nombre_empresa']) ? htmlspecialchars(trim($_POST['nombre_empresa'])) : 'No proporcionada';
// Los siguientes campos son los que definiste en el formulario de contacto final
$servicio_interes = isset($_POST['servicio_interes']) ? htmlspecialchars(trim($_POST['servicio_interes'])) : 'No seleccionado';
$mensaje = isset($_POST['mensaje']) ? htmlspecialchars(trim($_POST['mensaje'])) : 'Sin mensaje.';

// --- Crear y configurar el objeto de email ---
$contact = new PHP_Email_Form;
$contact->ajax = true; // Importante para que validate.js maneje la respuesta

$contact->to = $receiving_email_address;
$contact->from_name = $nombre_contacto;
$contact->from_email = $correo;
$contact->subject = "Contacto Web BLISS: " . $nombre_empresa . " (" . $nombre_contacto . ")"; // Asunto más descriptivo

// --- Configuración SMTP (Opcional - Necesitarías tus credenciales SMTP de IONOS aquí) ---
/*
$contact->smtp = array(
  'host' => 'smtp.ionos.es', // O el que corresponda
  'username' => 'tu_email@blissindustrial.eu', // Tu dirección de email completa
  'password' => 'tu_contraseña_email',       // La contraseña de ese email
  'port' => '587',                          // Puerto SMTP (587 para TLS, 465 para SSL)
  'encryption' => 'tls'                     // O 'ssl'
);
*/

// --- Construir el cuerpo del mensaje ---
$contact->add_message( $nombre_contacto, 'De');
$contact->add_message( $correo, 'Email');
$contact->add_message( $nombre_empresa, 'Empresa');
$contact->add_message( $servicio_interes, 'Asunto/Servicio de Interés'); // Usar el campo hidden
$contact->add_message( $mensaje, 'Mensaje', 10);

// --- Enviar el email y devolver el resultado ---
// El script validate.js espera "OK" en caso de éxito o un mensaje de error
$send_result = $contact->send();
if ($send_result === true || strtolower(trim($send_result)) === "ok") { // Algunas versiones de la librería devuelven true, otras "OK"
    echo "OK";
} else {
    echo $send_result; // Devolver el mensaje de error de la librería
}
?>