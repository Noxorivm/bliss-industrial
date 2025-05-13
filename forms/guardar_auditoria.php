<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// --- Incluir Autoloader de Composer (para PHPMailer) ---
$autoloader_path = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader_path)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Error crítico: No se encontró el archivo autoload.php. Verifica la instalación de Composer. Ruta buscada: ' . realpath(__DIR__ . '/../') . '/vendor/autoload.php']);
    exit;
}
require $autoloader_path;

// --- Usar clases de PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Indicar que la respuesta será JSON y con UTF-8 (después de posible error de autoload)
header('Content-Type: application/json; charset=utf-8');

// --- Definiciones (DB, Email, reCAPTCHA Secret Key) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bliss_bd');
define('DB_USER', 'phpmyadmin');
define('DB_PASS', 'Bliss2025!');
define('DB_TABLE', 'auditoria_bliss');
define('ADMIN_EMAIL', 'hola@blissindustrial.eu');
define('RECAPTCHA_SECRET_KEY', '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N'); // TU CLAVE SECRETA


// --- Recibir datos JSON ---
$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);

// --- Validación básica de datos ---
if ($formData === null || !is_array($formData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos válidos (JSON).']);
    exit;
}

// === Verificación reCAPTCHA ===
if (isset($formData['g-recaptcha-response']) && !empty($formData['g-recaptcha-response'])) {
    $recaptcha_response = $formData['g-recaptcha-response'];
    // Eliminarlo de $formData para que no se intente guardar en la DB ni enviar por email
    unset($formData['g-recaptcha-response']);

    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $verify_data = [
        'secret'   => RECAPTCHA_SECRET_KEY,
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
            error_log("reCAPTCHA cURL Error: " . $curl_error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al verificar reCAPTCHA (conexión con Google).']);
            exit;
        }
    } else {
        $options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($verify_data), 'timeout' => 10]];
        $context  = stream_context_create($options);
        $google_response = file_get_contents($verify_url, false, $context);
    }

    if ($google_response === false) {
        error_log("reCAPTCHA: No se pudo obtener respuesta de Google.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al verificar reCAPTCHA (no se pudo conectar con Google).']);
        exit;
    }

    $response_keys = json_decode($google_response, true);

    if (!(isset($response_keys["success"]) && $response_keys["success"] == true)) {
        $error_codes = isset($response_keys['error-codes']) ? implode(', ', $response_keys['error-codes']) : 'desconocido';
        error_log("reCAPTCHA verification failed. Error codes: " . $error_codes);
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Verificación reCAPTCHA fallida. Por favor, inténtalo de nuevo.']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor, completa el verificador reCAPTCHA.']);
    exit;
}
// === Fin Verificación reCAPTCHA ===


// --- Función auxiliar y Mapeo de datos ---
function get_form_data($key, $default = null, $is_array = false) {
    global $formData;
    if (!isset($formData[$key])) { return $default; }
    if ($is_array) { return is_array($formData[$key]) ? implode(', ', array_map('trim', array_map('strval', $formData[$key]))) : $default; }
    return is_scalar($formData[$key]) ? trim(strval($formData[$key])) : $default;
}

// --- Mapeo de todas las variables del formulario ---
$empresa_nombre = get_form_data('empresa_nombre');
$contacto_nombre = get_form_data('contacto_nombre');
$contacto_email = get_form_data('contacto_email');
$contacto_telefono = get_form_data('contacto_telefono', '');
$contacto_whatsapp = get_form_data('contacto_whatsapp') === 'Sí' ? 1 : 0;
$perfil_cargo = get_form_data('perfil_cargo');
$cargo_otro_texto = get_form_data('cargo_otro_texto', '');
$sectores = get_form_data('sector', '', true);
$sector_otro_texto = get_form_data('sector_otro_texto', '');
$empresa_empleados = get_form_data('empresa_empleados');
$areas_mejora = get_form_data('areas_mejora', '', true);
$sistemas = get_form_data('sistemas', '', true);
$sis_otro_texto = get_form_data('sis_otro_texto', '');
$retos = get_form_data('retos', '', true);
$reto_otro_texto = get_form_data('reto_otro_texto', '');
$soluciones = get_form_data('soluciones', '', true);
$sol_otro_texto = get_form_data('sol_otro_texto', '');
$cuando_empezar = get_form_data('cuando_empezar');
$urgencia_nivel = get_form_data('urgencia_nivel');
$como_recibir = get_form_data('como_recibir');
$recibir_whatsapp = get_form_data('recibir_whatsapp') === 'Sí' ? 1 : 0;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

// --- Validación campos obligatorios del servidor ---
$required_fields = [
    'Empresa' => $empresa_nombre, 'Nombre Contacto' => $contacto_nombre, 'Email Contacto' => $contacto_email,
    'Cargo' => $perfil_cargo, 'Tamaño Empresa' => $empresa_empleados,
    'Sectores' => (empty($sectores) && empty($sector_otro_texto)) ? '' : ($sectores ?: $sector_otro_texto), // Al menos uno debe tener valor
    'Áreas Mejora' => $areas_mejora,
    'Cuándo Empezar' => $cuando_empezar, 'Nivel Urgencia' => $urgencia_nivel,
    'Cómo Recibir' => $como_recibir
];
$missing_fields = [];
foreach ($required_fields as $label => $value) {
    if ($value === null || $value === '') { $missing_fields[] = $label; }
}
if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios (Servidor): ' . implode(', ', $missing_fields)]);
    exit;
}
if (!filter_var($contacto_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido (Servidor).']);
    exit;
}

// --- Conexión a la Base de Datos (PDO) ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false ];
try { $pdo = new PDO($dsn, DB_USER, DB_PASS, $options); }
catch (\PDOException $e) { /* ... (Manejo de error igual que antes) ... */ exit; }

// --- Preparar SQL INSERT ---
$sql = "INSERT INTO " . DB_TABLE . " (
            empresa_nombre, contacto_nombre, contacto_email, contacto_telefono, contacto_whatsapp,
            perfil_cargo, cargo_otro_texto, sectores, sector_otro_texto, empresa_empleados, areas_mejora,
            sistemas, sis_otro_texto, retos, reto_otro_texto, soluciones, sol_otro_texto,
            cuando_empezar, urgencia_nivel, como_recibir, recibir_whatsapp, ip_address, fecha_envio
        ) VALUES (
            :empresa_nombre, :contacto_nombre, :contacto_email, :contacto_telefono, :contacto_whatsapp,
            :perfil_cargo, :cargo_otro_texto, :sectores, :sector_otro_texto, :empresa_empleados, :areas_mejora,
            :sistemas, :sis_otro_texto, :retos, :reto_otro_texto, :soluciones, :sol_otro_texto,
            :cuando_empezar, :urgencia_nivel, :como_recibir, :recibir_whatsapp, :ip_address, NOW()
        )";
try {
    $stmt = $pdo->prepare($sql);
    // --- Bind parameters ---
    $stmt->bindParam(':empresa_nombre', $empresa_nombre);
    $stmt->bindParam(':contacto_nombre', $contacto_nombre);
    $stmt->bindParam(':contacto_email', $contacto_email);
    // ... (todos los demás bindParam igual que antes) ...
    $stmt->bindParam(':contacto_telefono', $contacto_telefono);
    $stmt->bindParam(':contacto_whatsapp', $contacto_whatsapp, PDO::PARAM_INT);
    $stmt->bindParam(':perfil_cargo', $perfil_cargo);
    $stmt->bindParam(':cargo_otro_texto', $cargo_otro_texto);
    $stmt->bindParam(':sectores', $sectores);
    $stmt->bindParam(':sector_otro_texto', $sector_otro_texto);
    $stmt->bindParam(':empresa_empleados', $empresa_empleados);
    $stmt->bindParam(':areas_mejora', $areas_mejora);
    $stmt->bindParam(':sistemas', $sistemas);
    $stmt->bindParam(':sis_otro_texto', $sis_otro_texto);
    $stmt->bindParam(':retos', $retos);
    $stmt->bindParam(':reto_otro_texto', $reto_otro_texto);
    $stmt->bindParam(':soluciones', $soluciones);
    $stmt->bindParam(':sol_otro_texto', $sol_otro_texto);
    $stmt->bindParam(':cuando_empezar', $cuando_empezar);
    $stmt->bindParam(':urgencia_nivel', $urgencia_nivel);
    $stmt->bindParam(':como_recibir', $como_recibir);
    $stmt->bindParam(':recibir_whatsapp', $recibir_whatsapp, PDO::PARAM_INT);
    $stmt->bindParam(':ip_address', $ip_address);

    $stmt->execute();

    // --- Enviar Notificación por Email usando PHPMailer ---
    $mail = new PHPMailer(true);
    $email_sent_successfully = false;
    try {
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomenta para depuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.ionos.es'; // Reemplaza si es necesario
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu_email_de_envio@blissindustrial.eu'; // TU email de IONOS
        $mail->Password   = 'tu_contraseña_de_ese_email';    // TU contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('no-reply@blissindustrial.eu', 'Auditoría Web Bliss');
        $mail->addAddress(ADMIN_EMAIL, 'Admin Bliss');
        $mail->addReplyTo($contacto_email, $contacto_nombre);
        $mail->isHTML(false);
        $mail->Subject = 'Nueva Auditoría Recibida: ' . ($empresa_nombre ?: 'Empresa no indicada');
        // ... (construcción del emailBody igual que antes) ...
        $emailBody = "Se ha recibido una nueva solicitud de auditoría:\n\n";
        // ... (añadir todos los campos al cuerpo del email como en la versión anterior)
        $mail->Body = $emailBody; // Asegúrate de que $emailBody se construya con todos los datos
        if ($mail->send()) { $email_sent_successfully = true; }
        else { error_log("PHPMailer Error en guardar_auditoria: {$mail->ErrorInfo}"); }
    } catch (Exception $e) { error_log("PHPMailer Exception en guardar_auditoria: {$mail->ErrorInfo}"); }

    // --- Respuesta JSON final ---
    http_response_code(200);
    $response_message = 'Auditoría enviada correctamente.';
    if (!$email_sent_successfully) { $response_message .= ' (Aviso: Hubo un problema enviando la notificación por email.)'; }
    echo json_encode(['success' => true, 'message' => $response_message]);
    exit;
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error INSERT DB auditoria: " . $e->getMessage() . " --- SQL: " . $sql . " --- Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al guardar los datos. [Código: DBInsert]']);
    exit;
}
?>