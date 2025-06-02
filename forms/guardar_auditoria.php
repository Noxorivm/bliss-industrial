<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
$autoloader_path = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader_path)) {
    http_response_code(500); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Error autoload.php: ' . $autoloader_path]); exit;
}
require $autoloader_path;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

if (!defined('ADMIN_EMAIL')) { define('ADMIN_EMAIL', 'hola@blissindustrial.eu'); }
if (!defined('RECAPTCHA_SECRET_KEY')) { define('RECAPTCHA_SECRET_KEY', '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N');}

$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);

if ($formData === null || !is_array($formData)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'No JSON data.']); exit; }

// === reCAPTCHA Verification ===
if (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== 'TU_CLAVE_SECRETA_DE_RECAPTCHA_AQUI' && RECAPTCHA_SECRET_KEY !== '' && RECAPTCHA_SECRET_KEY !== '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N') {
    if (!isset($formData['g-recaptcha-response']) || empty($formData['g-recaptcha-response'])) {
        http_response_code(400); echo json_encode(['success' => false, 'message' => 'Por favor, completa el verificador reCAPTCHA (token faltante).']); exit;
    }
    $recaptcha_token = $formData['g-recaptcha-response']; unset($formData['g-recaptcha-response']);
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = ['secret' => RECAPTCHA_SECRET_KEY, 'response' => $recaptcha_token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null];
    $google_response_json = null;
    if (function_exists('curl_init')) {
        $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $recaptcha_url); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($recaptcha_data)); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 10); $google_response_json = curl_exec($ch); $curl_error = curl_error($ch); curl_close($ch);
        if ($curl_error) { error_log("reCAPTCHA cURL Error: " . $curl_error); http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error verificando reCAPTCHA (conexión).']); exit; }
    } else {
        $options = ['http' => ['header'  => "Content-type: application/x-www-form-urlencoded\r\n", 'method'  => 'POST', 'content' => http_build_query($recaptcha_data), 'timeout' => 10]]; $context  = stream_context_create($options); $google_response_json = @file_get_contents($recaptcha_url, false, $context);
    }
    if ($google_response_json === false) { error_log("reCAPTCHA: No se obtuvo respuesta de Google."); http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error verificando reCAPTCHA (sin respuesta de Google).']); exit; }
    $recaptcha_result = json_decode($google_response_json, true);
    if (!(isset($recaptcha_result["success"]) && $recaptcha_result["success"] == true)) {
        $error_codes = isset($recaptcha_result['error-codes']) ? implode(', ', $recaptcha_result['error-codes']) : 'desconocido'; error_log("reCAPTCHA verification failed. Error codes: " . $error_codes); http_response_code(403); echo json_encode(['success' => false, 'message' => 'Verificación reCAPTCHA fallida.']); exit;
    }
}
// === End reCAPTCHA ===

if (!function_exists('get_form_data')) {
    function get_form_data($key, $default = null, $is_array = false) {
        global $formData; if (!isset($formData[$key])) { return $default; }
        if ($is_array) { return is_array($formData[$key]) ? implode(', ', array_map('trim', array_map('strval', $formData[$key]))) : $default; }
        return is_scalar($formData[$key]) ? trim(strval($formData[$key])) : $default;
    }
}

// === Mapeo de Datos a Arrays por Sección ===
$datos_main_auditoria = [
    'empresa_nombre' => get_form_data('empresa_nombre'), 'contacto_nombre' => get_form_data('contacto_nombre'), 'contacto_email' => get_form_data('contacto_email'),
    'contacto_telefono' => get_form_data('contacto_telefono', ''), 'contacto_whatsapp' => get_form_data('contacto_whatsapp') === 'Sí' ? 1 : 0,
    'perfil_cargo' => get_form_data('perfil_cargo'), 'cargo_otro_texto' => get_form_data('cargo_otro_texto', ''),
    'empresa_empleados' => get_form_data('empresa_empleados'), 'cuando_empezar' => get_form_data('cuando_empezar'),
    'urgencia_nivel' => get_form_data('urgencia_nivel'), 'como_recibir' => get_form_data('como_recibir'),
    'recibir_whatsapp_informe' => get_form_data('recibir_whatsapp') === 'Sí' ? 1 : 0,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
];

$respuestas_global = [];
$map_global_simple = ['sector' => 'dg_sectores', 'sector_otro_texto' => 'dg_sector_otro_texto', 'areas_mejora' => 'dg_areas_mejora', 'sistemas' => 'dg_sistemas', 'sis_otro_texto' => 'dg_sis_otro_texto', 'retos' => 'dg_retos', 'reto_otro_texto' => 'dg_reto_otro_texto', 'soluciones' => 'dg_soluciones', 'sol_otro_texto' => 'dg_sol_otro_texto'];
$map_global_arrays = ['sector', 'areas_mejora', 'sistemas', 'retos', 'soluciones'];
foreach ($map_global_simple as $formKey => $dbKey) { $is_array = in_array($formKey, $map_global_arrays); $respuestas_global[$dbKey] = get_form_data($formKey, null, $is_array); }
foreach ($formData as $key => $value) { if (strpos($key, 'dg_') === 0 && !array_key_exists($key, $respuestas_global)) $respuestas_global[$key] = get_form_data($key); }

$respuestas_calidad = []; foreach ($formData as $key => $value) { if (strpos($key, 'dq_') === 0) $respuestas_calidad[$key] = get_form_data($key); }
$respuestas_rrhh = []; foreach ($formData as $key => $value) { if (strpos($key, 'dr_') === 0) $respuestas_rrhh[$key] = get_form_data($key); }
$respuestas_produccion = []; foreach ($formData as $key => $value) { if (strpos($key, 'dp_') === 0) $respuestas_produccion[$key] = get_form_data($key); }
$respuestas_comercial = []; foreach ($formData as $key => $value) { if (strpos($key, 'dc_') === 0) $respuestas_comercial[$key] = get_form_data($key); }
$respuestas_marketing = []; foreach ($formData as $key => $value) { if (strpos($key, 'dm_') === 0) $respuestas_marketing[$key] = get_form_data($key); }
$respuestas_logistica = []; foreach ($formData as $key => $value) { if (strpos($key, 'dl_') === 0) $respuestas_logistica[$key] = get_form_data($key); }
$respuestas_id_dpto = []; foreach ($formData as $key => $value) { if (strpos($key, 'di_') === 0) $respuestas_id_dpto[$key] = get_form_data($key); }
$respuestas_prl = []; foreach ($formData as $key => $value) { if (strpos($key, 'dprl_') === 0) $respuestas_prl[$key] = get_form_data($key); }
$respuestas_adminfin = []; foreach ($formData as $key => $value) { if (strpos($key, 'daf_') === 0) $respuestas_adminfin[$key] = get_form_data($key); }
$respuestas_transporte = []; foreach ($formData as $key => $value) { if (strpos($key, 'dt_') === 0) $respuestas_transporte[$key] = get_form_data($key); }
$respuestas_compras = []; foreach ($formData as $key => $value) { if (strpos($key, 'dcomp_') === 0) $respuestas_compras[$key] = get_form_data($key); }
$respuestas_ingenieria = []; foreach ($formData as $key => $value) { if (strpos($key, 'ding_') === 0) $respuestas_ingenieria[$key] = get_form_data($key); }
$respuestas_sat = []; foreach ($formData as $key => $value) { if (strpos($key, 'dsat_') === 0) $respuestas_sat[$key] = get_form_data($key); }

// --- Validación campos obligatorios ---
$required_fields = [
    'Empresa' => $datos_main_auditoria['empresa_nombre'],
    'Nombre Contacto' => $datos_main_auditoria['contacto_nombre'],
    'Email Contacto' => $datos_main_auditoria['contacto_email'],
    'Cargo' => $datos_main_auditoria['perfil_cargo'],
    'Tamaño Empresa' => $datos_main_auditoria['empresa_empleados'],
    'Sectores (Global)' => (empty($respuestas_global['dg_sectores']) && empty($respuestas_global['dg_sector_otro_texto'])) ? '' : 'ok',
    'Áreas Mejora (Global)' => empty($respuestas_global['dg_areas_mejora']) ? '' : 'ok',
    'Cuándo Empezar' => $datos_main_auditoria['cuando_empezar'],
    'Nivel Urgencia' => $datos_main_auditoria['urgencia_nivel'],
    'Cómo Recibir' => $datos_main_auditoria['como_recibir']
];
$missing_fields = [];
foreach ($required_fields as $label => $value) {
    if ($value === null || (is_string($value) && trim($value) === '')) { $missing_fields[] = $label; }
}
if (!empty($missing_fields)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios: ' . implode(', ', $missing_fields)]); exit; }
if (!filter_var($datos_main_auditoria['contacto_email'], FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido.']); exit;}

// --- Conexión DB e Iniciar Transacción ---
try { $pdo = getPDO(); $pdo->beginTransaction(); }
catch (\Exception $e) { http_response_code(500); error_log("Error fatal pre-transacción: " . $e->getMessage()); echo json_encode(['success' => false, 'message' => 'Error interno del servidor [InitDB].']); exit; }

try {
    // 1. INSERTAR EN LA TABLA PRINCIPAL (DB_TABLE_AUDITORIA_MAIN)
    $sqlMain = "INSERT INTO " . DB_TABLE_AUDITORIA_MAIN . " (
                    empresa_nombre, contacto_nombre, contacto_email, contacto_telefono, contacto_whatsapp,
                    perfil_cargo, cargo_otro_texto, empresa_empleados, cuando_empezar, urgencia_nivel,
                    como_recibir, recibir_whatsapp_informe, ip_address, fecha_envio
                ) VALUES (
                    :empresa_nombre, :contacto_nombre, :contacto_email, :contacto_telefono, :contacto_whatsapp,
                    :perfil_cargo, :cargo_otro_texto, :empresa_empleados, :cuando_empezar, :urgencia_nivel,
                    :como_recibir, :recibir_whatsapp_informe, :ip_address, NOW()
                )";
    $stmtMain = $pdo->prepare($sqlMain);
    $stmtMain->bindParam(':empresa_nombre', $datos_main_auditoria['empresa_nombre']);
    $stmtMain->bindParam(':contacto_nombre', $datos_main_auditoria['contacto_nombre']);
    $stmtMain->bindParam(':contacto_email', $datos_main_auditoria['contacto_email']);
    $stmtMain->bindParam(':contacto_telefono', $datos_main_auditoria['contacto_telefono']);
    $stmtMain->bindParam(':contacto_whatsapp', $datos_main_auditoria['contacto_whatsapp'], PDO::PARAM_INT);
    $stmtMain->bindParam(':perfil_cargo', $datos_main_auditoria['perfil_cargo']);
    $stmtMain->bindParam(':cargo_otro_texto', $datos_main_auditoria['cargo_otro_texto']);
    $stmtMain->bindParam(':empresa_empleados', $datos_main_auditoria['empresa_empleados']);
    $stmtMain->bindParam(':cuando_empezar', $datos_main_auditoria['cuando_empezar']);
    $stmtMain->bindParam(':urgencia_nivel', $datos_main_auditoria['urgencia_nivel']);
    $stmtMain->bindParam(':como_recibir', $datos_main_auditoria['como_recibir']);
    $stmtMain->bindParam(':recibir_whatsapp_informe', $datos_main_auditoria['recibir_whatsapp_informe'], PDO::PARAM_INT);
    $stmtMain->bindParam(':ip_address', $datos_main_auditoria['ip_address']);
    $stmtMain->execute();
    $last_audit_main_id = $pdo->lastInsertId();

    // --- Función auxiliar para insertar respuestas departamentales ---
    if (!function_exists('insertDepartmentResponses')) {
        function insertDepartmentResponses($pdo_conn, $tableNameConstant, $auditMainId, $responsesArray) {
            if (empty(array_filter(array_values($responsesArray)))) return;
            $columns = ['auditoria_main_id']; $placeholders = [':auditoria_main_id']; $bindValues = [':auditoria_main_id' => $auditMainId];
            foreach ($responsesArray as $key => $value) {
                if ($value !== null && $value !== '') { $columns[] = $key; $placeholders[] = ':' . $key; $bindValues[':' . $key] = $value; }
            }
            if (count($columns) === 1) return;
            $sql = "INSERT INTO " . constant($tableNameConstant) . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo_conn->prepare($sql);
            foreach ($bindValues as $placeholderKey => $val) { $stmt->bindValue($placeholderKey, $val); }
            $stmt->execute();
        }
    }

    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_GLOBAL', $last_audit_main_id, $respuestas_global);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_CALIDAD', $last_audit_main_id, $respuestas_calidad);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_RRHH', $last_audit_main_id, $respuestas_rrhh);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_PRODUCCION', $last_audit_main_id, $respuestas_produccion);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_COMERCIAL', $last_audit_main_id, $respuestas_comercial);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_MARKETING', $last_audit_main_id, $respuestas_marketing);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_LOGISTICA', $last_audit_main_id, $respuestas_logistica);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_ID', $last_audit_main_id, $respuestas_id_dpto);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_PRL', $last_audit_main_id, $respuestas_prl);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_ADMINFIN', $last_audit_main_id, $respuestas_adminfin);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_TRANSPORTE', $last_audit_main_id, $respuestas_transporte);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_COMPRAS', $last_audit_main_id, $respuestas_compras);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_INGENIERIA', $last_audit_main_id, $respuestas_ingenieria);
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_SAT', $last_audit_main_id, $respuestas_sat);

    $pdo->commit();

    // --- Lógica para crear/actualizar cliente CRM e interacción ---
    if ($last_audit_main_id && defined('DB_TABLE_CRM') && defined('DB_TABLE_INTERACCIONES')) {
        $cliente_crm_id = null;
        $stmtCheckClient = $pdo->prepare("SELECT id FROM " . DB_TABLE_CRM . " WHERE email_contacto = :email_contacto LIMIT 1");
        $stmtCheckClient->bindParam(':email_contacto', $datos_main_auditoria['contacto_email']);
        $stmtCheckClient->execute();
        $existingClient = $stmtCheckClient->fetch(PDO::FETCH_ASSOC);
        if ($existingClient) { $cliente_crm_id = $existingClient['id'];
        } else {
            $sqlNewClient = "INSERT INTO " . DB_TABLE_CRM . " (nombre_empresa, persona_contacto, email_contacto, telefono_contacto, origen_lead, estado_lead) VALUES (:ne, :pc, :ec, :tc, 'Form Auditoría', 'Nuevo')";
            $stmtNewClient = $pdo->prepare($sqlNewClient);
            if ($stmtNewClient->execute([
                ':ne' => $datos_main_auditoria['empresa_nombre'], ':pc' => $datos_main_auditoria['contacto_nombre'],
                ':ec' => $datos_main_auditoria['contacto_email'], ':tc' => $datos_main_auditoria['contacto_telefono']
            ])) {
                $cliente_crm_id = $pdo->lastInsertId();
            } else { error_log("Error creando cliente CRM para: " . $datos_main_auditoria['contacto_email']); }
        }
        if ($cliente_crm_id) {
            date_default_timezone_set('Europe/Madrid'); $fecha_actual_interaccion = date('Y-m-d H:i:s');
            $tipo_interaccion_auto = "Formulario Auditoría";
            $resumen_interaccion_auto = "Cliente completó el formulario de auditoría gratuita. ID Auditoría Principal: " . $last_audit_main_id;
            $usuario_registro_auto = $_SESSION['username'] ?? "Sistema (Auditoría Web)";
            $sqlInteraction = "INSERT INTO " . DB_TABLE_INTERACCIONES . " (cliente_id, usuario_crm_id, tipo_interaccion, fecha_interaccion, resumen_interaccion, creado_en) VALUES (:cid, :uid, :tipo, :fecha_int, :resumen, NOW())";
            $stmtInteraction = $pdo->prepare($sqlInteraction);
            if (!$stmtInteraction->execute([':cid' => $cliente_crm_id, ':uid' => $usuario_registro_auto, ':tipo' => $tipo_interaccion_auto, ':fecha_int' => $fecha_actual_interaccion, ':resumen' => $resumen_interaccion_auto])) {
                error_log("Error creando interacción automática para CRM ID: " . $cliente_crm_id . " desde auditoría ID: " . $last_audit_main_id);
            }
        }
    }

    // --- Enviar Notificación por Email usando PHPMailer ---
    $mail = new PHPMailer(true); $email_sent_successfully = false;
    try {
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP(); $mail->Host = 'smtp.ionos.es'; $mail->SMTPAuth = true; $mail->Username = 'hola@blissindustrial.eu'; $mail->Password = '*STS#ssoluttionss#2023*'; $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587; $mail->CharSet = 'UTF-8';
        $mail->setFrom('hola@blissindustrial.eu', 'Auditoría Web Bliss');
        $mail->addAddress(ADMIN_EMAIL, 'Admin Bliss');
        $mail->addReplyTo($datos_main_auditoria['contacto_email'], $datos_main_auditoria['contacto_nombre']);
        $mail->isHTML(false);
        $mail->Subject = 'Nueva Auditoría Recibida: ' . ($datos_main_auditoria['empresa_nombre'] ?: 'Empresa no indicada');
        $emailBody = "Se ha recibido una nueva solicitud de auditoría:\n\n";
        $emailBody .= "--- Datos de Contacto Principales ---\n";
        foreach ($datos_main_auditoria as $key => $value) {
            if ($key === 'ip_address' || $key === 'recibir_whatsapp_informe') continue;
            $label = ucwords(str_replace('_', ' ', $key)); if ($key === 'contacto_whatsapp') $label = 'Permiso WhatsApp (Contacto)';
            $emailBody .= $label . ": " . (is_array($value) ? implode(', ', $value) : ($value ?: 'N/A')) . "\n";
        }
        if(isset($datos_main_auditoria['recibir_whatsapp_informe'])) $emailBody .= "Recibir Informe WhatsApp: " . ($datos_main_auditoria['recibir_whatsapp_informe'] ? 'Sí' : 'No') . "\n";

        if (!function_exists('addSectionToEmailBodyForHumans')) { // Prevenir redeclaración si se incluye en otro lado
            function addSectionToEmailBodyForHumans(&$body, $title, $dataArray) {
                $hasData = false; foreach($dataArray as $val) { if($val !== null && $val !== '' && !is_array($val) || (is_array($val) && !empty(array_filter($val))) ) { $hasData = true; break; } }
                if ($hasData) {
                    $body .= "\n--- $title ---\n";
                    foreach ($dataArray as $key => $value) {
                        if ($value !== null && $value !== '') {
                            $label = ucwords(str_replace(['dq_', 'dg_', 'dr_', 'dp_', 'dc_', 'dm_', 'dl_', 'di_', 'dprl_', 'daf_', 'dt_', 'dcomp_', 'ding_', 'dsat_', '_'], ['', '', '', '', '', '', '', '', '', '', '', '', '', '', ' '], $key));
                            $label = trim($label); if (strpos($key, '_otro_texto') !== false) { $original_key = str_replace('_otro_texto', '', $key); $original_label_base = ucwords(str_replace(['dq_', 'dg_', 'dr_', 'dp_', 'dc_', 'dm_', 'dl_', 'di_', 'dprl_', 'daf_', 'dt_', 'dcomp_', 'ding_', 'dsat_', '_'], ['', '', '', '', '', '', '', '', '', '', '', '', '', '', ' '], $original_key)); $label = trim($original_label_base) . " (Otro)"; }
                            $emailBodyValue = is_array($value) ? implode('; ', $value) : $value; $body .= $label . ": " . ($emailBodyValue ?: 'N/A') . "\n";
                        }
                    }
                }
            }
        }
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Global Empresa", $respuestas_global);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Calidad", $respuestas_calidad);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico RRHH", $respuestas_rrhh);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Producción", $respuestas_produccion);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Comercial", $respuestas_comercial);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Marketing", $respuestas_marketing);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Logística", $respuestas_logistica);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico I+D", $respuestas_id_dpto);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico PRL", $respuestas_prl);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Admin./Finanzas", $respuestas_adminfin);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Transporte", $respuestas_transporte);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Compras", $respuestas_compras);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Ingeniería", $respuestas_ingenieria);
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico SAT", $respuestas_sat);

        $emailBody .= "\nIP Origen: " . ($datos_main_auditoria['ip_address'] ?: 'N/A') . "\n";
        $emailBody .= "Fecha Envío (Servidor): " . date('Y-m-d H:i:s') . "\n";
        $mail->Body = $emailBody;

        if ($mail->send()) { $email_sent_successfully = true; }
        else { error_log("PHPMailer Error en guardar_auditoria: {$mail->ErrorInfo}"); }
    } catch (Exception $e) { error_log("PHPMailer Exception en guardar_auditoria: {$e->getMessage()}"); }

    http_response_code(200);
    $response_message = 'Auditoría enviada correctamente.';
    if (!$email_sent_successfully) { $response_message .= ' (Aviso: Hubo un problema enviando la notificación por email.)'; }
    echo json_encode(['success' => true, 'message' => $response_message]);
    exit;

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    error_log("Error guardando auditoría completa (transacción): " . $e->getMessage() . " --- Data: " . print_r($formData, true));
    $errorMessageDetail = ($e instanceof \PDOException) ? " [DB Error: " . $e->getCode() . "]" : "";
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al guardar los datos.' . $errorMessageDetail . ' Detalles: ' . $e->getMessage()]);
    exit;
}
?>