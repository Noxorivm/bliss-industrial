<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar/Eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Incluir Configuración y Autoloader ---
require_once '../config/database.php';
$autoloader_path = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader_path)) {
    http_response_code(500); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Error crítico: No se encontró el archivo autoload.php. Ruta buscada: ' . $autoloader_path]); exit;
}
require $autoloader_path;

// --- Usar clases de PHPMailer y Google ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- Configuración de Cabeceras y Constantes ---
header('Content-Type: application/json; charset=utf-8');
if (!defined('ADMIN_EMAIL')) { define('ADMIN_EMAIL', 'hola@blissindustrial.eu'); }
if (!defined('RECAPTCHA_SECRET_KEY')) { define('RECAPTCHA_SECRET_KEY', '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N');}

// --- Recepción y Validación de Datos JSON ---
$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);
if ($formData === null || !is_array($formData)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'No se recibieron datos JSON válidos.']); exit; }

// === Verificación reCAPTCHA ===
if (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N' && RECAPTCHA_SECRET_KEY !== '') {
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
// === Fin Verificación reCAPTCHA ===

// --- Función auxiliar para obtener y limpiar datos del formulario ---
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
    'proyecto_punto_actual' => get_form_data('proyecto_punto_actual'),
    'proyecto_presupuesto' => get_form_data('proyecto_presupuesto'),
    'proyecto_presupuesto_especificar' => get_form_data('proyecto_presupuesto_especificar', ''),
    'proyecto_decision_final' => get_form_data('proyecto_decision_final'),
    'proyecto_apoyo_valorado' => get_form_data('proyecto_apoyo_valorado', null, true),
    'proyecto_otras_iniciativas' => get_form_data('proyecto_otras_iniciativas', null, true),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
];

// --- Recopilación de respuestas departamentales por prefijo ---
$respuestas_global = [];
// Mapeo de campos del formulario a columnas de la tabla 'auditoria_resp_global'
$map_global_simple = [
    'sector' => 'dg_sectores', // El nuevo campo de texto
    'areas_mejora' => 'dg_areas_mejora', 
    'sistemas' => 'dg_sistemas', 
    'sis_otro_texto' => 'dg_sis_otro_texto', 
    'retos' => 'dg_retos', 
    'reto_otro_texto' => 'dg_reto_otro_texto', 
    'soluciones' => 'dg_soluciones', 
    'sol_otro_texto' => 'dg_sol_otro_texto'
];
// Define cuáles de los campos anteriores son arrays (checkboxes)
$map_global_arrays = ['areas_mejora', 'sistemas', 'retos', 'soluciones'];

// Procesamos el mapeo
foreach ($map_global_simple as $formKey => $dbKey) { 
    $is_array = in_array($formKey, $map_global_arrays); 
    $respuestas_global[$dbKey] = get_form_data($formKey, null, $is_array); 
}

// Recogemos el resto de campos que empiezan con dg_ (si los hubiera)
foreach ($formData as $key => $value) { 
    if (strpos($key, 'dg_') === 0 && !array_key_exists($key, $respuestas_global)) {
        $respuestas_global[$key] = get_form_data($key);
    }
}

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

// --- Validación de Campos Obligatorios ---
$required_fields = [
    'Empresa' => $datos_main_auditoria['empresa_nombre'], 'Nombre Contacto' => $datos_main_auditoria['contacto_nombre'],
    'Email Contacto' => $datos_main_auditoria['contacto_email'], 'Cargo' => $datos_main_auditoria['perfil_cargo'],
    'Tamaño Empresa' => $datos_main_auditoria['empresa_empleados'],
    'Sectores (Global)' => (empty($respuestas_global['dg_sectores']) && empty($respuestas_global['dg_sector_otro_texto'])) ? '' : 'ok',
    'Áreas Mejora (Global)' => empty($respuestas_global['dg_areas_mejora']) ? '' : 'ok',
    'Cuándo Empezar' => $datos_main_auditoria['cuando_empezar'], 'Nivel Urgencia' => $datos_main_auditoria['urgencia_nivel'],
    'Cómo Recibir' => $datos_main_auditoria['como_recibir']
];
$missing_fields = [];
foreach ($required_fields as $label => $value) { if ($value === null || (is_string($value) && trim($value) === '')) { $missing_fields[] = $label; } }
if (!empty($missing_fields)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios: ' . implode(', ', $missing_fields)]); exit; }
if (!filter_var($datos_main_auditoria['contacto_email'], FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido.']); exit;}

// --- Conexión a Base de Datos e Inicio de Transacción ---
try { $pdo = getPDO(); $pdo->beginTransaction(); }
catch (\Exception $e) { http_response_code(500); error_log("Error fatal pre-transacción: " . $e->getMessage()); echo json_encode(['success' => false, 'message' => 'Error interno del servidor [InitDB].']); exit; }

try {
    // 1. Insertar en la tabla principal (auditoria_bliss_main)
    $sqlMain = "INSERT INTO " . DB_TABLE_AUDITORIA_MAIN . " (
                    empresa_nombre, contacto_nombre, contacto_email, contacto_telefono, contacto_whatsapp,
                    perfil_cargo, cargo_otro_texto, empresa_empleados, cuando_empezar, urgencia_nivel,
                    como_recibir, recibir_whatsapp_informe, 
                    proyecto_punto_actual, proyecto_presupuesto, proyecto_presupuesto_especificar, 
                    proyecto_decision_final, proyecto_apoyo_valorado, proyecto_otras_iniciativas,
                    ip_address, fecha_envio
                ) VALUES (
                    :empresa_nombre, :contacto_nombre, :contacto_email, :contacto_telefono, :contacto_whatsapp,
                    :perfil_cargo, :cargo_otro_texto, :empresa_empleados, :cuando_empezar, :urgencia_nivel,
                    :como_recibir, :recibir_whatsapp_informe,
                    :proyecto_punto_actual, :proyecto_presupuesto, :proyecto_presupuesto_especificar,
                    :proyecto_decision_final, :proyecto_apoyo_valorado, :proyecto_otras_iniciativas,
                    :ip_address, NOW()
                )";
    $stmtMain = $pdo->prepare($sqlMain);
    $stmtMain->execute($datos_main_auditoria);
    $last_audit_main_id = $pdo->lastInsertId();

    // 2. Insertar en las tablas de respuestas departamentales
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

    // 3. Lógica para CRM (Crear/Actualizar Cliente y registrar Interacción)
    if ($last_audit_main_id && defined('DB_TABLE_CRM') && defined('DB_TABLE_INTERACCIONES')) {
        // ... (código CRM sin cambios)
    }

    // 4. Enviar Notificación por Email
    $mail = new PHPMailer(true); $email_sent_successfully = false;
    try {
        $mail->isSMTP(); $mail->Host = 'smtp.ionos.es'; $mail->SMTPAuth = true; $mail->Username = 'hola@blissindustrial.eu'; $mail->Password = '*STS#ssoluttionss#2023*'; $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587; $mail->CharSet = 'UTF-8';
        $mail->setFrom('hola@blissindustrial.eu', 'Auditoría Web Bliss'); $mail->addAddress(ADMIN_EMAIL, 'Admin Bliss'); $mail->addReplyTo($datos_main_auditoria['contacto_email'], $datos_main_auditoria['contacto_nombre']);
        $mail->isHTML(false); $mail->Subject = 'Nueva Auditoría Recibida: ' . ($datos_main_auditoria['empresa_nombre'] ?: 'Empresa no indicada');
        
        $emailBody = "Se ha recibido una nueva solicitud de auditoría:\n\n";
        $emailBody .= "--- Datos de Contacto Principales ---\n";
        foreach ($datos_main_auditoria as $key => $value) {
            if (in_array($key, ['ip_address', 'proyecto_punto_actual', 'proyecto_presupuesto', 'proyecto_presupuesto_especificar', 'proyecto_decision_final', 'proyecto_apoyo_valorado', 'proyecto_otras_iniciativas'])) continue;
            $label = ucwords(str_replace('_', ' ', $key));
            if ($key === 'contacto_whatsapp') { $label = 'Permiso WhatsApp (Contacto)'; $value = $value ? 'Sí' : 'No'; }
            if ($key === 'recibir_whatsapp_informe') { $label = 'Recibir Informe por WhatsApp'; $value = $value ? 'Sí' : 'No'; }
            $emailBody .= $label . ": " . ($value ?: 'N/A') . "\n";
        }
        
        $emailBody .= "\n--- Detalles del Proyecto de Mejora ---\n";
        $emailBody .= "Punto actual del proyecto: " . ($datos_main_auditoria['proyecto_punto_actual'] ?: 'N/A') . "\n";
        $emailBody .= "Presupuesto asignado: " . ($datos_main_auditoria['proyecto_presupuesto'] ?: 'N/A') . "\n";
        if (!empty($datos_main_auditoria['proyecto_presupuesto_especificar'])) {
            $emailBody .= "Presupuesto (especificado): " . $datos_main_auditoria['proyecto_presupuesto_especificar'] . "\n";
        }
        $emailBody .= "Decisión final por: " . ($datos_main_auditoria['proyecto_decision_final'] ?: 'N/A') . "\n";
        $emailBody .= "Apoyo más valorado: " . ($datos_main_auditoria['proyecto_apoyo_valorado'] ?: 'N/A') . "\n";
        $emailBody .= "Otras iniciativas previstas: " . ($datos_main_auditoria['proyecto_otras_iniciativas'] ?: 'N/A') . "\n";
        
        if (!function_exists('addSectionToEmailBodyForHumans')) {
            function addSectionToEmailBodyForHumans(&$body, $title, $dataArray) {
                if (!empty(array_filter($dataArray))) {
                    $body .= "\n--- $title ---\n";
                    foreach ($dataArray as $key => $value) {
                        if (!empty($value)) {
                            $label = ucwords(str_replace(['dq_', 'dg_', 'dr_', 'dp_', 'dc_', 'dm_', 'dl_', 'di_', 'dprl_', 'daf_', 'dt_', 'dcomp_', 'ding_', 'dsat_', '_'], ['', '', '', '', '', '', '', '', '', '', '', '', '', '', ' '], $key));
                            $label = trim(str_replace('Otro Texto', '(Otro)', $label));
                            $body .= $label . ": " . $value . "\n";
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

        // 5. Guardar en Google Sheets
        if ($last_audit_main_id) {
            try {
                $spreadsheetId = '1x-YkgIDmfCKkozuboxq0rpyr3OhaBUH1ILkdKlrKFfQ';
                $sheetName = 'Respuestas Completas V2';
                $credentialsFilePath = '/var/www/blissindustrial.eu/google_credentials/bliss-463215-f801f217c19c.json';
    
                if (!file_exists($credentialsFilePath)) {
                    error_log("Google Sheets: Archivo de credenciales no encontrado en: " . $credentialsFilePath);
                } else {
                    $client = new \Google\Client();
                    $client->setApplicationName("BLISS Auditoria Sheets Updater");
                    $client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
                    $client->setAuthConfig($credentialsFilePath);
                    $client->setAccessType('offline');
                    $service = new \Google\Service\Sheets($client);
    
                    // --- Construir la fila para Google Sheets de forma explícita y ordenada ---
                    $rowData = [];
                    
                    // === Bloque 1: Datos Principales ===
                    $rowData[] = $last_audit_main_id;
                    $rowData[] = $datos_main_auditoria['empresa_nombre'] ?? '';
                    $rowData[] = $datos_main_auditoria['contacto_nombre'] ?? '';
                    $rowData[] = $datos_main_auditoria['contacto_email'] ?? '';
                    $rowData[] = $datos_main_auditoria['contacto_telefono'] ?? '';
                    $rowData[] = ($datos_main_auditoria['contacto_whatsapp'] == 1) ? 'Sí' : 'No';
                    $rowData[] = $datos_main_auditoria['perfil_cargo'] ?? '';
                    $rowData[] = $datos_main_auditoria['cargo_otro_texto'] ?? '';
                    $rowData[] = $datos_main_auditoria['empresa_empleados'] ?? '';
                    $rowData[] = $datos_main_auditoria['cuando_empezar'] ?? '';
                    $rowData[] = $datos_main_auditoria['urgencia_nivel'] ?? '';
                    $rowData[] = $datos_main_auditoria['como_recibir'] ?? '';
                    $rowData[] = ($datos_main_auditoria['recibir_whatsapp_informe'] == 1) ? 'Sí' : 'No';
                    $rowData[] = date('Y-m-d H:i:s');
                    $rowData[] = $datos_main_auditoria['proyecto_punto_actual'] ?? '';
                    $rowData[] = $datos_main_auditoria['proyecto_presupuesto'] ?? '';
                    $rowData[] = $datos_main_auditoria['proyecto_presupuesto_especificar'] ?? '';
                    $rowData[] = $datos_main_auditoria['proyecto_decision_final'] ?? '';
                    $rowData[] = $datos_main_auditoria['proyecto_apoyo_valorado'] ?? '';
                    $rowData[] = $datos_main_auditoria['proyecto_otras_iniciativas'] ?? '';
    
                    // === Bloque 2: Diagnóstico Global (dg_*) ===
                    $rowData[] = $respuestas_global['dg_sectores'] ?? '';
                    $rowData[] = $respuestas_global['dg_sector_otro_texto'] ?? '';
                    $rowData[] = $respuestas_global['dg_areas_mejora'] ?? '';
                    $rowData[] = $respuestas_global['dg_sistemas'] ?? '';
                    $rowData[] = $respuestas_global['dg_sis_otro_texto'] ?? '';
                    $rowData[] = $respuestas_global['dg_retos'] ?? '';
                    $rowData[] = $respuestas_global['dg_reto_otro_texto'] ?? '';
                    $rowData[] = $respuestas_global['dg_soluciones'] ?? '';
                    $rowData[] = $respuestas_global['dg_sol_otro_texto'] ?? '';
                    $rowData[] = $respuestas_global['dg_modelo_negocio'] ?? '';
                    $rowData[] = $respuestas_global['dg_perfil_clientes'] ?? '';
                    $rowData[] = $respuestas_global['dg_etapa_negocio'] ?? '';
                    $rowData[] = $respuestas_global['dg_reto_principal'] ?? '';
                    $rowData[] = $respuestas_global['dg_cambios_estructurales'] ?? '';
                    $rowData[] = $respuestas_global['dg_areas_mas_incidencias'] ?? '';
                    $rowData[] = $respuestas_global['dg_comunicacion_departamentos'] ?? '';
                    $rowData[] = $respuestas_global['dg_tareas_duplicadas'] ?? '';
                    $rowData[] = $respuestas_global['dg_tareas_tiempo_innecesario'] ?? '';
                    $rowData[] = $respuestas_global['dg_nivel_erp_actual'] ?? '';
                    $rowData[] = $respuestas_global['dg_integracion_herramientas'] ?? '';
                    $rowData[] = $respuestas_global['dg_gestion_datos_operativa'] ?? '';
                    $rowData[] = $respuestas_global['dg_digitalizacion_global_percibida'] ?? '';
                    $rowData[] = $respuestas_global['dg_compromiso_direccion_mejora'] ?? '';
                    $rowData[] = $respuestas_global['dg_reaccion_equipo_cambios'] ?? '';
                    $rowData[] = $respuestas_global['dg_formacion_nuevas_herramientas'] ?? '';
                    $rowData[] = $respuestas_global['dg_objetivos_prioritarios_proyecto'] ?? '';
                    $rowData[] = $respuestas_global['dg_urgencia_proyecto'] ?? '';
                    $rowData[] = $respuestas_global['dg_expectativa_roi'] ?? '';
    
                    // === Bloque 3: Diagnóstico Calidad (dq_*) ===
                    $rowData[] = $respuestas_calidad['dq_formacion_equipo'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_revision_roles'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_indicadores_medidos'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_dependencia_terceros'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_causa_no_conformidades'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_cuellos_botella_auditorias'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_tarea_mas_tiempo'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_datos_tiempo_real'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_porcentaje_errores_humanos'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_impacto_devolucion'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_informes_automaticos'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_duplicidad_registros'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_flujo_manual'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_integracion_calidad_sistemas'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_resistencia_tecnologia'] ?? '';
                    $rowData[] = $respuestas_calidad['dq_satisfaccion_sistemas_actuales'] ?? '';
    
                    // === Bloques Siguientes... (RRHH, Producción, etc.) ===
                    // Añadimos cada campo explícitamente para asegurar el orden.
                    $rowData[] = $respuestas_rrhh['dr_funciones_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_perfil_equipo_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_gestion_procesos_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_quien_gestiona_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_mayor_problema_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_nivel_errores_admin_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_mejora_impacto_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_impacto_esperado_mejoras_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_procesos_seleccion_ano'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_horas_tareas_admin_semana_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_herramientas_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_almacenamiento_documentos_personal'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_digitalizacion_formaciones_contratos'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_situacion_digital_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_automatizacion_actual_rrhh'] ?? '';
                    $rowData[] = $respuestas_rrhh['dr_colaboracion_rrhh_direccion'] ?? '';
    
                    $rowData[] = $respuestas_produccion['dp_modelo_turnos'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_variedad_productos'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_gestion_procesos_clave'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_quien_coordina_produccion'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_principal_cuello_botella'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_tareas_repetitivas_tiempo'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_donde_mas_errores_retrabajos'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_automatizar_digitalizar_primero'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_impacto_mejora_prioritaria'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_tiempo_tarea_operativa_clave'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_frecuencia_errores_paradas'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_conocimiento_coste_parada_error'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_herramientas_usadas_produccion'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_registro_informacion_diaria'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_sistemas_visuales_seguimiento'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_digitalizacion_produccion'] ?? '';
                    $rowData[] = $respuestas_produccion['dp_actitud_equipo_herramientas_digitales'] ?? '';
                    
                    // ... y así para todos los demás departamentos...
                    $rowData[] = $respuestas_comercial['dc_estructura_equipo'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_funciones_principales'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_gestion_procesos_comerciales'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_herramientas_presupuestos_pedidos'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_tarea_comercial_mas_tiempo'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_donde_mas_errores_clientes'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_problema_frecuente_equipo'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_mejora_impacto_comercial'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_beneficio_esperado_mejoras'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_tiempo_comercial_tareas_repetitivas'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_incidencias_errores_mes'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_canales_comunicacion_clientes'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_crm_activo'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_registro_interacciones'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_informes_automaticos_comercial'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_integracion_comercial_dptos'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_estado_digital_comercial'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_herramientas_automatizadas_uso'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_zonas_paises'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_idiomas_usados'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_funciones_equipo'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_gestion_pedidos'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_herramientas_coordinacion_clientes_int'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_mayores_cuellos_botella'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_generacion_documentacion'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_error_mas_habitual'] ?? '';
                    $rowData[] = $respuestas_comercial['dc_exp_automatizacion_util'] ?? '';
    
                    $rowData[] = $respuestas_marketing['dm_quien_encarga_marketing'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_tipo_marketing_realizado'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_presupuesto_anual_marketing'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_gestion_campanas_acciones'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_almacenamiento_contenidos_resultados'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_problema_limita_marketing'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_dependencia_genera_riesgo'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_mejora_prioritaria'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_impacto_esperado_mejoras'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_campanas_acciones_ano'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_leads_generados_mes'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_indicadores_por_campana'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_canales_digitales_activos'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_herramientas_gestion_marketing'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_conexion_marketing_crm_comercial'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_informes_paneles_resultados'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_estado_digital_marketing'] ?? '';
                    $rowData[] = $respuestas_marketing['dm_automatizacion_actual_marketing'] ?? '';
                    
                    $rowData[] = $respuestas_logistica['dl_tipo_productos_gestionados'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_numero_almacenes_ubicaciones'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_frecuencia_inventario'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_volumen_medio_movimientos_semanal'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_registro_entradas_salidas_ubicaciones'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_herramienta_picking_inventario'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_quien_tareas_operativas_clave'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_problema_frecuente_logistica'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_situacion_riesgo_operativo'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_digitalizar_automatizar_primero'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_impacto_esperado_mejoras_logistica'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_tiempo_tarea_logistica_frecuente'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_incidencias_logisticas_mes'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_conocimiento_coste_incidencia_logistica'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_penalizaciones_errores_entregas'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_herramientas_gestion_almacen'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_registro_movimientos_almacen'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_visibilidad_stock_otros_dptos'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_tecnologias_identificacion_productos'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_digitalizacion_almacen'] ?? '';
                    $rowData[] = $respuestas_logistica['dl_elementos_automatizados_almacen'] ?? '';
    
                    $rowData[] = $respuestas_id_dpto['di_tipo_innovacion_desarrollada'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_tipo_proyectos_id'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_colaboracion_id_areas'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_gestion_proyectos_desarrollo_pruebas'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_registro_resultados_validaciones'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_mayor_problema_id'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_situacion_repetida_frecuencia'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_mejora_util_id'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_impacto_esperado_mejoras_id'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_proyectos_id_ano'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_duracion_proyecto_medio_desarrollo'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_repeticion_errores_pruebas_falta_registro'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_personas_externas_aportan_ideas_id'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_herramienta_principal_gestion_proyectos'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_almacenamiento_documentacion_desarrollos'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_id_conectado_otros_sistemas'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_sistema_priorizar_medir_roi'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_digitalizacion_id'] ?? '';
                    $rowData[] = $respuestas_id_dpto['di_automatizaciones_sistemas_id'] ?? '';
    
                    $rowData[] = $respuestas_prl['dprl_gestion_prl_actual'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_actividades_gestion_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_tipo_actividad_industrial'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_gestion_procesos_clave_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_almacenamiento_documentos_historiales_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_problema_frecuente_gestion_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_dificulta_operativa_preventiva'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_mejora_util_corto_plazo_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_impacto_esperado_mejoras_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_partes_accidente_ano'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_formaciones_prl_ano'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_inspeccion_ultimos_2_anos'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_tiempo_semanal_tareas_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_herramientas_gestion_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_control_epis_digital'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_trabajadores_acceso_digital_doc_preventiva'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_digitalizacion_prl'] ?? '';
                    $rowData[] = $respuestas_prl['dprl_sistemas_automatizados_prl'] ?? '';
                    
                    $rowData[] = $respuestas_adminfin['daf_funciones_departamento'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_personas_equipo'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_gestion_procesos_clave'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_nivel_estandarizacion_procesos'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_mayor_problema_operativo'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_genera_mas_bloqueos_retrasos'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_mejora_impacto_administrativa'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_impacto_esperado_mejoras_admin'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_tiempo_semanal_tareas_repetitivas'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_errores_administrativos_mes'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_dependencia_personas_concretas'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_software_principal_usado'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_nivel_automatizacion'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_dashboards_informes_automaticos'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_sistema_conectado_otros_dptos'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_estado_digital_area'] ?? '';
                    $rowData[] = $respuestas_adminfin['daf_sistemas_en_uso'] ?? '';
                    
                    $rowData[] = $respuestas_transporte['dt_gestion_transporte_actual'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_tipo_envios_habituales'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_numero_agencias_transportistas'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_gestion_comunicacion_agencias'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_planificacion_registro_envios'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_almacenamiento_datos_transporte'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_mayor_problema_transporte'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_dificulta_dia_a_dia_logistica'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_mejorar_primero_gestion_transportes'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_impacto_esperado_mejoras_transporte'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_envios_semanales'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_incidencias_mes_entregas_fallidas_errores'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_tiempo_semanal_coordinar_transportes'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_conocimiento_costes_medios_envio_ruta'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_herramienta_gestion_envios'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_generacion_documentacion_logistica'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_informes_transporte_disponibles'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_digitalizacion_actual_transporte'] ?? '';
                    $rowData[] = $respuestas_transporte['dt_elementos_digitales_activos_transporte'] ?? '';
                    
                    $rowData[] = $respuestas_compras['dcomp_campo_1'] ?? '';
                    $rowData[] = $respuestas_compras['dcomp_campo_2'] ?? '';
                    
                    $rowData[] = $respuestas_ingenieria['ding_campo_1'] ?? '';
                    $rowData[] = $respuestas_ingenieria['ding_campo_2'] ?? '';
                    
                    $rowData[] = $respuestas_sat['dsat_campo_1'] ?? '';
                    $rowData[] = $respuestas_sat['dsat_campo_2'] ?? '';
    
                    $valueRange = new \Google\Service\Sheets\ValueRange(['values' => [$rowData]]);
                    $options = ['valueInputOption' => 'USER_ENTERED'];
                    $service->spreadsheets_values->append($spreadsheetId, $sheetName, $valueRange, $options);
                    error_log("Google Sheets: Datos de auditoría ID {$last_audit_main_id} guardados correctamente en la hoja '{$sheetName}'.");
                }
            } catch (\Exception $e) {
                error_log("Google Sheets Error (Auditoría ID: {$last_audit_main_id}): " . $e->getMessage());
            }
        }

    // 6. Enviar Respuesta Final de Éxito
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
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al guardar los datos.' . $errorMessageDetail]);
    exit;
}