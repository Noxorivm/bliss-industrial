<?php
// ... (inicio, auth_check, include config/database.php igual que antes) ...
require_once '../dashboard/auth_check.php';
require_once '../config/database.php'; // Asegúrate que define DB_TABLE_INTERACCIONES

header('Content-Type: application/json; charset=utf-8');

$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);

if ($formData === null || !is_array($formData) || !isset($formData['cliente_id_interaction'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o ID de cliente faltante para la interacción.']);
    exit;
}

// Mapeo con tus posibles nombres de input del modal
$cliente_id = filter_var($formData['cliente_id_interaction'], FILTER_VALIDATE_INT);
$fecha_interaccion_input = isset($formData['fecha_interaccion']) ? trim($formData['fecha_interaccion']) : date('Y-m-d H:i:s'); // Del input datetime-local
$tipo_interaccion_input = isset($formData['tipo_interaccion']) ? trim($formData['tipo_interaccion']) : null;
$descripcion_input = isset($formData['descripcion']) ? trim($formData['descripcion']) : null; // Este será 'resumen_interaccion' en la DB
$resultado_input = isset($formData['resultado']) ? trim($formData['resultado']) : '';       // Este será 'proximo_paso' en la DB (si así lo mapeaste)
$proximo_paso_input = isset($formData['proximo_paso']) ? trim($formData['proximo_paso']) : ''; // Este será 'proximo_paso'
$fecha_proximo_paso_input = isset($formData['fecha_proximo_paso']) && !empty($formData['fecha_proximo_paso']) ? trim($formData['fecha_proximo_paso']) : null;
$usuario_actual = $_SESSION['username'] ?? 'Sistema'; // Asumo que quieres el usuario de la sesión actual

// Validación
if (!$cliente_id || empty($tipo_interaccion_input) || empty($descripcion_input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios (Cliente ID, Tipo, Descripción).']);
    exit;
}
try {
    $dt = new DateTime($fecha_interaccion_input);
    $fecha_interaccion_mysql = $dt->format('Y-m-d H:i:s');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de fecha de interacción inválido.']);
    exit;
}

try {
    $pdo = getPDO();
    // --- Consulta SQL INSERT con TUS nombres de columna ---
    $sql = "INSERT INTO " . DB_TABLE_INTERACCIONES . "
                (cliente_id, usuario_crm_id, tipo_interaccion, fecha_interaccion, resumen_interaccion, proximo_paso, fecha_proximo_paso, creado_en)
            VALUES
                (:cliente_id, :usuario_crm_id, :tipo_interaccion, :fecha_interaccion, :resumen_interaccion, :proximo_paso, :fecha_proximo_paso, NOW())";
            // Nota: 'creado_en' usualmente se maneja con DEFAULT CURRENT_TIMESTAMP en la DB,
            // pero lo incluyo aquí por si tu tabla no lo tiene así y lo quieres explícito.
            // Si tu tabla sí tiene DEFAULT, puedes quitar creado_en de la lista de columnas y :creado_en de VALUES y el bindParam.

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmt->bindParam(':usuario_crm_id', $usuario_actual); // Asumiendo que quieres guardar el usuario actual del dashboard
    $stmt->bindParam(':tipo_interaccion', $tipo_interaccion_input);
    $stmt->bindParam(':fecha_interaccion', $fecha_interaccion_mysql);
    $stmt->bindParam(':resumen_interaccion', $descripcion_input); // Mapea 'descripcion' del form a 'resumen_interaccion'
    $stmt->bindParam(':proximo_paso', $proximo_paso_input);       // Mapea 'proximo_paso' del form
    $stmt->bindParam(':fecha_proximo_paso', $fecha_proximo_paso_input); // Mapea 'fecha_proximo_paso' del form
    // No necesitamos bindParam para 'creado_en' si usa NOW() o DEFAULT en la DB

    $stmt->execute();
    $new_interaction_id = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Interacción guardada.', 'new_interaction_id' => $new_interaction_id]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error INSERT DB crear_crm_interaccion: " . $e->getMessage() . " Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno al guardar la interacción. Detalles: ' . $e->getMessage()]);
    exit;
}
?>