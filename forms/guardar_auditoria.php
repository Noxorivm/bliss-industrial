<?php
// --- Habilitar Errores (SOLO PARA DEPURACI�N - Comentar en producci�n) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// --- Incluir Autoloader de Composer (para PHPMailer) ---
$autoloader_path = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader_path)) {
    http_response_code(500);
    // Cambiar header a JSON antes de salir con error
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Error cr�tico: No se encontr� el archivo autoload.php. Ejecuta "composer install" en la ra�z del proyecto. Ruta buscada: ' . realpath(__DIR__ . '/../') . '/vendor/autoload.php']);
    exit;
}
require $autoloader_path;

// --- Usar clases de PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Indicar que la respuesta ser� JSON y con UTF-8
header('Content-Type: application/json; charset=utf-8');

// --- Configuraci�n DB y Email ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bliss_bd');
define('DB_USER', 'phpmyadmin');
define('DB_PASS', 'Bliss2025!');
define('DB_TABLE', 'auditoria_bliss');
define('ADMIN_EMAIL', 'hola@blissindustrial.eu');

// --- Recibir datos JSON ---
$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);

// --- Validaci�n b�sica ---
if ($formData === null || !is_array($formData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos v�lidos (JSON).']);
    exit;
}

// --- Funci�n auxiliar ---
function get_form_data($key, $default = null, $is_array = false) {
    global $formData;
    if (!isset($formData[$key])) { return $default; }
    if ($is_array) { return is_array($formData[$key]) ? implode(', ', array_map('trim', array_map('strval', $formData[$key]))) : $default; }
    return is_scalar($formData[$key]) ? trim(strval($formData[$key])) : $default;
}

// --- Mapeo de datos ---
// ... (mapeo igual que antes) ...
$empresa_nombre = get_form_data('empresa_nombre');
$contacto_nombre = get_form_data('contacto_nombre');
$contacto_email = get_form_data('contacto_email');
$contacto_telefono = get_form_data('contacto_telefono', '');
$contacto_whatsapp = get_form_data('contacto_whatsapp') === 'S�' ? 1 : 0;
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
$recibir_whatsapp = get_form_data('recibir_whatsapp') === 'S�' ? 1 : 0;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;


// --- Validaci�n campos obligatorios ---
// ... (validaci�n igual que antes) ...
$required_fields = [
    'Empresa' => $empresa_nombre, 'Nombre Contacto' => $contacto_nombre, 'Email Contacto' => $contacto_email,
    'Cargo' => $perfil_cargo, 'Tama�o Empresa' => $empresa_empleados, '�reas Mejora' => $areas_mejora,
    'Sectores' => $sectores, 'Cu�ndo Empezar' => $cuando_empezar, 'Nivel Urgencia' => $urgencia_nivel,
    'C�mo Recibir' => $como_recibir
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
    echo json_encode(['success' => false, 'message' => 'Correo electr�nico inv�lido (Servidor).']);
    exit;
}

// --- Conexi�n a la Base de Datos (PDO - ASEG�RATE DEL CHARSET) ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4"; // charset=utf8mb4 es CLAVE
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error conexi�n DB auditoria: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor [DB Connect].']);
    exit;
}

// --- Preparar SQL INSERT ---
// ... (SQL INSERT igual que antes) ...
$sql = "INSERT INTO " . DB_TABLE . " (
            empresa_nombre, contacto_nombre, contacto_email, contacto_telefono, contacto_whatsapp,
            perfil_cargo, cargo_otro_texto,
            sectores, sector_otro_texto, empresa_empleados, areas_mejora,
            sistemas, sis_otro_texto, retos, reto_otro_texto,
            soluciones, sol_otro_texto, cuando_empezar, urgencia_nivel,
            como_recibir, recibir_whatsapp, ip_address, fecha_envio
        ) VALUES (
            :empresa_nombre, :contacto_nombre, :contacto_email, :contacto_telefono, :contacto_whatsapp,
            :perfil_cargo, :cargo_otro_texto,
            :sectores, :sector_otro_texto, :empresa_empleados, :areas_mejora,
            :sistemas, :sis_otro_texto, :retos, :reto_otro_texto,
            :soluciones, :sol_otro_texto, :cuando_empezar, :urgencia_nivel,
            :como_recibir, :recibir_whatsapp, :ip_address, NOW()
        )";

try {
    $stmt = $pdo->prepare($sql);

    // --- Bind parameters ---
    // ... (bindParam igual que antes) ...
    $stmt->bindParam(':empresa_nombre', $empresa_nombre);
    $stmt->bindParam(':contacto_nombre', $contacto_nombre);
    $stmt->bindParam(':contacto_email', $contacto_email);
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

    // --- Inserci�n en DB exitosa ---

    // --- Enviar Notificaci�n por Email usando PHPMailer ---
    $mail = new PHPMailer(true);
    $email_sent_successfully = false;

    try {
        // === Configuraciones del servidor SMTP (!!! REEMPLAZA CON TUS DATOS !!!) ===
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = 'smtp.ionos.es';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu_email@tu_dominio_en_ionos.es'; // <<<!!! TU EMAIL IONOS
        $mail->Password   = 'tu_contrase�a_de_ese_correo'; // <<<!!! TU CONTRASE�A IONOS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // === Remitente y Destinatarios ===
        $mail->setFrom('remitente_verificado@tu_dominio_en_ionos.es', 'Web Bliss - Auditoria'); // <<<!!! EMAIL V�LIDO IONOS
        $mail->addAddress(ADMIN_EMAIL, 'Admin Bliss');
        $mail->addReplyTo($contacto_email, $contacto_nombre);

        // === Contenido del Email ===
        $mail->isHTML(false);
        $mail->Subject = 'Nueva Auditor�a Recibida: ' . ($empresa_nombre ?: 'Empresa no indicada');
        // ... (construcci�n del emailBody igual que antes) ...
        $emailBody = "Se ha recibido una nueva solicitud de auditor�a:\n\n";
        $emailBody .= "--- Datos de Contacto ---\n";
        $emailBody .= "Empresa: " . ($empresa_nombre ?: 'N/A') . "\n";
        $emailBody .= "Nombre Contacto: " . ($contacto_nombre ?: 'N/A') . "\n";
        $emailBody .= "Email: " . ($contacto_email ?: 'N/A') . "\n";
        $emailBody .= "Tel�fono: " . ($contacto_telefono ?: 'N/A') . "\n";
        $emailBody .= "Contacto WhatsApp: " . ($contacto_whatsapp ? 'S�' : 'No') . "\n\n";
        $emailBody .= "--- Perfil y Empresa ---\n";
        $emailBody .= "Cargo: " . ($perfil_cargo ?: 'N/A') . ($cargo_otro_texto ? ' (' . htmlspecialchars($cargo_otro_texto) . ')' : '') . "\n";
        $emailBody .= "Sectores: " . ($sectores ?: 'N/A') . ($sector_otro_texto ? ' (Otro: ' . htmlspecialchars($sector_otro_texto) . ')' : '') . "\n";
        $emailBody .= "N� Empleados: " . ($empresa_empleados ?: 'N/A') . "\n";
        $emailBody .= "�reas a mejorar: " . ($areas_mejora ?: 'N/A') . "\n\n";
        $emailBody .= "--- Situaci�n Actual ---\n";
        $emailBody .= "Sistemas usados: " . ($sistemas ?: 'N/A') . ($sis_otro_texto ? ' (Otro: ' . htmlspecialchars($sis_otro_texto) . ')' : '') . "\n";
        $emailBody .= "Retos actuales: " . ($retos ?: 'N/A') . ($reto_otro_texto ? ' (Otro: ' . htmlspecialchars($reto_otro_texto) . ')' : '') . "\n\n";
        $emailBody .= "--- Intereses y Prioridades ---\n";
        $emailBody .= "Soluciones de inter�s: " . ($soluciones ?: 'N/A') . ($sol_otro_texto ? ' (Otro: ' . htmlspecialchars($sol_otro_texto) . ')' : '') . "\n";
        $emailBody .= "Cu�ndo empezar: " . ($cuando_empezar ?: 'N/A') . "\n";
        $emailBody .= "Nivel de Urgencia: " . ($urgencia_nivel ?: 'N/A') . "\n\n";
        $emailBody .= "--- Preferencias de Informe ---\n";
        $emailBody .= "Recibir por Email: S�\n";
        $emailBody .= "Recibir tambi�n por WhatsApp: " . ($recibir_whatsapp ? 'S�' : 'No') . "\n\n";
        $emailBody .= "IP Origen: " . ($ip_address ?: 'N/A') . "\n";
        $emailBody .= "Fecha Env�o: " . date('Y-m-d H:i:s') . "\n";
        $mail->Body = $emailBody;

        if ($mail->send()) {
            $email_sent_successfully = true;
        } else {
             error_log("PHPMailer Error en guardado auditoria: {$mail->ErrorInfo}");
        }

    } catch (Exception $e) {
        error_log("PHPMailer Exception en guardado auditoria: {$mail->ErrorInfo}");
    }

    // --- Respuesta JSON final ---
    http_response_code(200);
    $response_message = 'Auditor�a enviada correctamente.';
    if (!$email_sent_successfully) {
        $response_message .= ' (Aviso: Hubo un problema enviando la notificaci�n por email.)';
        error_log("Auditor�a de $empresa_nombre guardada en DB, pero notificaci�n email fall�.");
    }
    echo json_encode(['success' => true, 'message' => $response_message]);
    exit;

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error INSERT DB auditoria: " . $e->getMessage() . " --- SQL: " . $sql . " --- Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al guardar los datos. [C�digo: DBInsert]']);
    exit;
}

?>