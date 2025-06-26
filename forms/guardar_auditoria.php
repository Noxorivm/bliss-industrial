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

// === Mapeo de Datos a Arrays por Sección (ACTUALIZADO CON NUEVOS CAMPOS) ===
$datos_main_auditoria = [
    'empresa_nombre' => get_form_data('empresa_nombre'),
    'contacto_nombre' => get_form_data('contacto_nombre'),
    'contacto_email' => get_form_data('contacto_email'),
    'contacto_telefono' => get_form_data('contacto_telefono', ''),
    'contacto_whatsapp' => get_form_data('contacto_whatsapp') === 'Sí' ? 1 : 0,
    'perfil_cargo' => get_form_data('perfil_cargo'),
    'cargo_otro_texto' => get_form_data('cargo_otro_texto', ''),
    'empresa_empleados' => get_form_data('empresa_empleados'),
    'como_recibir' => get_form_data('como_recibir'),
    'recibir_whatsapp_informe' => get_form_data('recibir_whatsapp') === 'Sí' ? 1 : 0,
    'proyecto_punto_actual' => get_form_data('proyecto_punto_actual'),
    'proyecto_presupuesto' => get_form_data('proyecto_presupuesto'),
    'proyecto_presupuesto_especificar' => get_form_data('proyecto_presupuesto_especificar'),
    'proyecto_decision_final' => get_form_data('proyecto_decision_final'),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
];

// Combina las respuestas de checkboxes con sus campos "otro" ANTES de añadirlos al array principal
$apoyo_valorado_base = get_form_data('proyecto_apoyo_valorado', null, true);
$apoyo_otro = get_form_data('proyecto_apoyo_valorado_otro_texto');
$datos_main_auditoria['proyecto_apoyo_valorado'] = trim($apoyo_valorado_base . ($apoyo_otro ? ', ' . $apoyo_otro : ''));

$otras_iniciativas_base = get_form_data('proyecto_otras_iniciativas', null, true);
$iniciativas_otro = get_form_data('proyecto_otras_iniciativas_otro_texto');
$datos_main_auditoria['proyecto_otras_iniciativas'] = trim($otras_iniciativas_base . ($iniciativas_otro ? ', ' . $iniciativas_otro : ''));

$respuestas_global = [
    'dg_sectores' => get_form_data('sector'),
    'dg_areas_mejora' => get_form_data('areas_mejora', null, true),
    'dg_areas_mejora_otro_texto' => get_form_data('areas_mejora_otro_texto'),
    'dg_sistemas' => get_form_data('sistemas', null, true),
    'dg_sis_otro_texto' => get_form_data('sis_otro_texto'),
    'dg_retos' => get_form_data('retos', null, true),
    'dg_reto_otro_texto' => get_form_data('reto_otro_texto'),
    'dg_soluciones' => get_form_data('soluciones', null, true),
    'dg_sol_otro_texto' => get_form_data('sol_otro_texto'),
];

// --- Validación de Campos Obligatorios ---
$required_fields = [
    'Empresa' => $datos_main_auditoria['empresa_nombre'],
    'Nombre Contacto' => $datos_main_auditoria['contacto_nombre'],
    'Email Contacto' => $datos_main_auditoria['contacto_email'],
    'Cargo' => $datos_main_auditoria['perfil_cargo'],
    'Punto actual del proyecto' => $datos_main_auditoria['proyecto_punto_actual'],
    'Presupuesto' => $datos_main_auditoria['proyecto_presupuesto'],
    'Decisión final' => $datos_main_auditoria['proyecto_decision_final'],
    'Cómo Recibir' => $datos_main_auditoria['como_recibir']
];
$missing_fields = [];
foreach ($required_fields as $label => $value) { if (empty(trim((string)$value))) { $missing_fields[] = $label; } }
if (!empty($missing_fields)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios: ' . implode(', ', $missing_fields)]); exit; }
if (!filter_var($datos_main_auditoria['contacto_email'], FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido.']); exit;}

// --- Conexión a Base de Datos e Inicio de Transacción ---
try { $pdo = getPDO(); $pdo->beginTransaction(); }
catch (\Exception $e) { http_response_code(500); error_log("Error fatal pre-transacción: " . $e->getMessage()); echo json_encode(['success' => false, 'message' => 'Error interno del servidor [InitDB].']); exit; }

try {
    // 1. Insertar en la tabla principal (auditoria_bliss_main)
     $sqlMain = "INSERT INTO " . DB_TABLE_AUDITORIA_MAIN . " (
                    empresa_nombre, contacto_nombre, contacto_email, contacto_telefono, contacto_whatsapp,
                    perfil_cargo, cargo_otro_texto, empresa_empleados, como_recibir, recibir_whatsapp_informe,
                    proyecto_punto_actual, proyecto_presupuesto, proyecto_presupuesto_especificar,
                    proyecto_decision_final, proyecto_apoyo_valorado, proyecto_otras_iniciativas,
                    ip_address, fecha_envio
                ) VALUES (
                    :empresa_nombre, :contacto_nombre, :contacto_email, :contacto_telefono, :contacto_whatsapp,
                    :perfil_cargo, :cargo_otro_texto, :empresa_empleados, :como_recibir, :recibir_whatsapp_informe,
                    :proyecto_punto_actual, :proyecto_presupuesto, :proyecto_presupuesto_especificar,
                    :proyecto_decision_final, :proyecto_apoyo_valorado, :proyecto_otras_iniciativas,
                    :ip_address, NOW()
                )";
    $stmtMain = $pdo->prepare($sqlMain);
    $stmtMain->execute($datos_main_auditoria);
    $last_audit_main_id = $pdo->lastInsertId();


    // 2. Insertar en las tablas de respuestas
    if (!function_exists('insertDepartmentResponses')) {
        function insertDepartmentResponses($pdo_conn, $tableNameConstant, $auditMainId, $responsesArray) {
            if (empty(array_filter(array_values($responsesArray)))) return;
            $columns = ['auditoria_main_id']; $bindValues = [':auditoria_main_id' => $auditMainId];
            foreach ($responsesArray as $key => $value) {
                if ($value !== null && $value !== '') { $columns[] = $key; $bindValues[':' . $key] = $value; }
            }
            if (count($columns) > 1) {
                $placeholders = implode(', ', array_keys($bindValues));
                $sql = "INSERT INTO " . constant($tableNameConstant) . " (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";
                $stmt = $pdo_conn->prepare($sql);
                $stmt->execute($bindValues);
            }
        }
    }
    insertDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_GLOBAL', $last_audit_main_id, $respuestas_global);

    $pdo->commit();

    // 3. Lógica para CRM (Crear/Actualizar Cliente y registrar Interacción)
    if ($last_audit_main_id && defined('DB_TABLE_CRM') && defined('DB_TABLE_INTERACCIONES')) {
        // ... (código CRM sin cambios)
    }

    // 4. Enviar Notificación por Email (ACTUALIZADO)
    $mail = new PHPMailer(true); $email_sent_successfully = false;
    try {
        $mail->isSMTP(); $mail->Host = 'smtp.ionos.es'; $mail->SMTPAuth = true; $mail->Username = 'hola@blissindustrial.eu'; $mail->Password = '*STS#ssoluttionss#2023*'; $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587; $mail->CharSet = 'UTF-8';
        $mail->setFrom('hola@blissindustrial.eu', 'Auditoría Web Bliss'); $mail->addAddress(ADMIN_EMAIL, 'Admin Bliss');
        $mail->addReplyTo($datos_main_auditoria['contacto_email'], $datos_main_auditoria['contacto_nombre']);
        $mail->isHTML(false); $mail->Subject = 'Nueva Auditoría Recibida: ' . ($datos_main_auditoria['empresa_nombre'] ?: 'Empresa no indicada');
        
        $emailBody = "Se ha recibido una nueva solicitud de auditoría:\n\n";
        $emailBody .= "--- Datos de Contacto Principales ---\n";
        foreach ($datos_main_auditoria as $key => $value) {
            if (strpos($key, 'proyecto_') === 0 || $key === 'ip_address') continue; // Omitir campos de proyecto aquí
            $label = ucwords(str_replace('_', ' ', $key));
            if ($key === 'contacto_whatsapp') { $label = 'Permiso WhatsApp (Contacto)'; $value = $value ? 'Sí' : 'No'; }
            if ($key === 'recibir_whatsapp_informe') { $label = 'Recibir Informe por WhatsApp'; $value = $value ? 'Sí' : 'No'; }
            $emailBody .= $label . ": " . ($value ?: 'N/A') . "\n";
        }
        
        // Función dinámica para secciones de diagnóstico
        if (!function_exists('addSectionToEmailBodyForHumans')) {
            function addSectionToEmailBodyForHumans(&$body, $title, $dataArray) {
                if (!empty(array_filter($dataArray))) {
                    $body .= "\n--- $title ---\n";
                    foreach ($dataArray as $key => $value) {
                        if (!empty($value)) {
                            $label = ucwords(str_replace(['dg_', '_'], ['', ' '], $key));
                            $label = trim(str_replace('Otro Texto', '(Otro)', $label));
                            $body .= $label . ": " . $value . "\n";
                        }
                    }
                }
            }
        }
        addSectionToEmailBodyForHumans($emailBody, "Diagnóstico Global Empresa", $respuestas_global);

        // Añadir sección de detalles del proyecto
        $emailBody .= "\n--- Detalles del Proyecto de Mejora ---\n";
        $emailBody .= "Punto actual del proyecto: " . ($datos_main_auditoria['proyecto_punto_actual'] ?: 'N/A') . "\n";
        $emailBody .= "Presupuesto: " . ($datos_main_auditoria['proyecto_presupuesto'] ?: 'N/A') . "\n";
        if(!empty($datos_main_auditoria['proyecto_presupuesto_especificar'])) {
            $emailBody .= "Presupuesto (especificado): " . $datos_main_auditoria['proyecto_presupuesto_especificar'] . "\n";
        }
        $emailBody .= "Toma de decisión: " . ($datos_main_auditoria['proyecto_decision_final'] ?: 'N/A') . "\n";
        $emailBody .= "Apoyo valorado: " . ($datos_main_auditoria['proyecto_apoyo_valorado'] ?: 'N/A') . "\n";
        if(!empty($datos_main_auditoria['proyecto_apoyo_valorado_otro_texto'])) {
            $emailBody .= "Apoyo valorado (Otro): " . $datos_main_auditoria['proyecto_apoyo_valorado_otro_texto'] . "\n";
        }
        $emailBody .= "Otras iniciativas: " . ($datos_main_auditoria['proyecto_otras_iniciativas'] ?: 'N/A') . "\n";
        if(!empty($datos_main_auditoria['proyecto_otras_iniciativas_otro_texto'])) {
            $emailBody .= "Otras iniciativas (Otro): " . $datos_main_auditoria['proyecto_otras_iniciativas_otro_texto'] . "\n";
        }
        
        $emailBody .= "\nIP Origen: " . ($datos_main_auditoria['ip_address'] ?: 'N/A') . "\n";
        $emailBody .= "Fecha Envío (Servidor): " . date('Y-m-d H:i:s') . "\n";
        $mail->Body = $emailBody;

        if ($mail->send()) { $email_sent_successfully = true; }
        else { error_log("PHPMailer Error: {$mail->ErrorInfo}"); }
    } catch (Exception $e) { error_log("PHPMailer Exception: {$e->getMessage()}"); }

        // 5. Guardar en Google Sheets (Sincronizado)
    if ($last_audit_main_id) {
        try {
            $spreadsheetId = '1x-YkgIDmfCKkozuboxq0rpyr3OhaBUH1ILkdKlrKFfQ';
            $sheetName = 'Respuestas Completas V2';
            $credentialsFilePath = '/var/www/blissindustrial.eu/google_credentials/bliss-463215-f801f217c19c.json';

            if (file_exists($credentialsFilePath)) {
                $client = new \Google\Client();
                $client->setApplicationName("BLISS Auditoria Sheets Updater");
                $client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
                $client->setAuthConfig($credentialsFilePath);
                $service = new \Google\Service\Sheets($client);
                
                $rowData = [];
                // Mapeo 1:1 con las columnas de tu Google Sheet
                $rowData[] = $last_audit_main_id;
                $rowData[] = $datos_main_auditoria['empresa_nombre'] ?? '';
                $rowData[] = $datos_main_auditoria['contacto_nombre'] ?? '';
                $rowData[] = $datos_main_auditoria['contacto_email'] ?? '';
                $rowData[] = $datos_main_auditoria['contacto_telefono'] ?? '';
                $rowData[] = ($datos_main_auditoria['contacto_whatsapp'] == 1) ? 'Sí' : 'No';
                $rowData[] = $datos_main_auditoria['perfil_cargo'] ?? '';
                $rowData[] = $datos_main_auditoria['cargo_otro_texto'] ?? '';
                $rowData[] = $datos_main_auditoria['empresa_empleados'] ?? '';
                $rowData[] = ''; // cuando_empezar (columna vacía)
                $rowData[] = ''; // urgencia_nivel (columna vacía)
                $rowData[] = $datos_main_auditoria['como_recibir'] ?? '';
                $rowData[] = ($datos_main_auditoria['recibir_whatsapp_informe'] == 1) ? 'Sí' : 'No';
                $rowData[] = date('Y-m-d H:i:s');
                $rowData[] = $datos_main_auditoria['proyecto_punto_actual'] ?? '';
                $rowData[] = $datos_main_auditoria['proyecto_presupuesto'] ?? '';
                $rowData[] = $datos_main_auditoria['proyecto_presupuesto_especificar'] ?? '';
                $rowData[] = $datos_main_auditoria['proyecto_decision_final'] ?? '';
                $rowData[] = $datos_main_auditoria['proyecto_apoyo_valorado'] ?? '';
                $rowData[] = $datos_main_auditoria['proyecto_otras_iniciativas'] ?? '';
                
                $rowData[] = $respuestas_global['dg_sectores'] ?? '';
                $rowData[] = ''; // dg_sector_otro_texto (no se usa)
                $rowData[] = $respuestas_global['dg_areas_mejora'] ?? '';
                $rowData[] = $respuestas_global['dg_areas_mejora_otro_texto'] ?? '';
                $rowData[] = $respuestas_global['dg_sistemas'] ?? '';
                $rowData[] = $respuestas_global['dg_sis_otro_texto'] ?? '';
                $rowData[] = $respuestas_global['dg_retos'] ?? '';
                $rowData[] = $respuestas_global['dg_reto_otro_texto'] ?? '';
                $rowData[] = $respuestas_global['dg_soluciones'] ?? '';
                $rowData[] = $respuestas_global['dg_sol_otro_texto'] ?? '';
                $rowData[] = ''; // dg_presupuesto (no se usa)
                $rowData[] = ''; // dg_presupuesto_otro_texto (no se usa)
                $rowData[] = ''; // dg_iniciativas_proximas (no se usa)
                $rowData[] = ''; // dg_iniciativas_proximas_otro_texto (no se usa)
                
                for ($i=0; $i<257; $i++) {
                    $rowData[] = '';
                }

                $valueRange = new \Google\Service\Sheets\ValueRange(['values' => [$rowData]]);
                $options = ['valueInputOption' => 'USER_ENTERED'];
                $service->spreadsheets_values->append($spreadsheetId, $sheetName, $valueRange, $options);
            }
        } catch (\Exception $e) {
            error_log("Google Sheets Error: " . $e->getMessage());
        }
    }


    // 6. Enviar Respuesta Final de Éxito
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Auditoría enviada correctamente.']);
    exit;

} catch (\PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    error_log("Error de base de datos: " . $e->getMessage() . " --- Código: " . $e->getCode() . " --- Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al guardar los datos. [DB Error: ' . $e->getCode() . ']']);
    exit;
} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    error_log("Error general: " . $e->getMessage() . " --- Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno general del servidor.']);
    exit;
}