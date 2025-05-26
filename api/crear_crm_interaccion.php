<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

require_once '../dashboard/auth_check.php';
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);

if ($formData === null || !is_array($formData) || !isset($formData['cliente_id_interaction'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o ID de cliente faltante para la interacción.']);
    exit;
}

// --- ESTABLECER FECHA/HORA EN EL SERVIDOR (Zona Horaria Madrid) ---
date_default_timezone_set('Europe/Madrid');
$fecha_interaccion_mysql = date('Y-m-d H:i:s'); // Fecha y hora actual del servidor

// Mapeo de datos (ya no se recibe 'fecha_interaccion' del form)
$cliente_id = filter_var($formData['cliente_id_interaction'] ?? null, FILTER_VALIDATE_INT);
$tipo_interaccion_input = isset($formData['tipo_interaccion']) ? trim($formData['tipo_interaccion']) : null;
$descripcion_input = isset($formData['descripcion']) ? trim($formData['descripcion']) : null; // Este se guardará en 'resumen_interaccion'
$resultado_actual_input = isset($formData['resultado_interaccion']) ? trim($formData['resultado_interaccion']) : ''; // Del campo "Resultado de esta interacción"
$proximo_paso_input = isset($formData['proximo_paso']) ? trim($formData['proximo_paso']) : '';
$fecha_proximo_paso_input = isset($formData['fecha_proximo_paso']) && !empty($formData['fecha_proximo_paso']) ? trim($formData['fecha_proximo_paso']) : null;
$usuario_actual_sesion = $_SESSION['username'] ?? 'Sistema'; // Usuario logueado

// Validación
if (!$cliente_id || empty($tipo_interaccion_input) || empty($descripcion_input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios (Cliente ID, Tipo, Descripción).']);
    exit;
}

try {
    $pdo = getPDO();
    // --- Consulta SQL INSERT con nombres de columna de TU tabla ---
    // Asegúrate que 'resultado_interaccion' sea el nombre correcto de tu columna para guardar el resultado
    // 'creado_en' se asume que tiene DEFAULT CURRENT_TIMESTAMP en la DB
    $sql = "INSERT INTO " . DB_TABLE_INTERACCIONES . "
                (cliente_id, usuario_crm_id, tipo_interaccion, fecha_interaccion, resumen_interaccion, resultado_interaccion, proximo_paso, fecha_proximo_paso)
            VALUES
                (:cliente_id, :usuario_crm_id, :tipo_interaccion, :fecha_interaccion, :resumen_interaccion, :resultado_interaccion, :proximo_paso, :fecha_proximo_paso)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmt->bindParam(':usuario_crm_id', $usuario_actual_sesion);
    $stmt->bindParam(':tipo_interaccion', $tipo_interaccion_input);
    $stmt->bindParam(':fecha_interaccion', $fecha_interaccion_mysql); // Usar fecha del servidor
    $stmt->bindParam(':resumen_interaccion', $descripcion_input);    // Guardar 'descripcion' del form en 'resumen_interaccion'
    $stmt->bindParam(':resultado_interaccion', $resultado_actual_input); // Guardar 'resultado_interaccion_actual' del form
    $stmt->bindParam(':proximo_paso', $proximo_paso_input);
    $stmt->bindParam(':fecha_proximo_paso', $fecha_proximo_paso_input);

    $stmt->execute();
    $new_interaction_id = $pdo->lastInsertId();

    http_response_code(201); // Created
    echo json_encode(['success' => true, 'message' => 'Interacción guardada.', 'new_interaction_id' => $new_interaction_id]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error INSERT DB crear_crm_interaccion: " . $e->getMessage() . " Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar la interacción. Detalles: ' . $e->getMessage()]);
    exit;
}
?>