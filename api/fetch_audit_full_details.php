<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

require_once '../dashboard/auth_check.php';
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$audit_main_id = $_GET['id'] ?? null; // Recibir el ID de la auditoría principal

if (!$audit_main_id || !filter_var($audit_main_id, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de auditoría inválido o no proporcionado.']);
    exit;
}

try {
    $pdo = getPDO();
    $full_audit_data = [];

    // 1. Obtener datos de la tabla principal
    $stmtMain = $pdo->prepare("SELECT * FROM " . DB_TABLE_AUDITORIA_MAIN . " WHERE id = :audit_main_id");
    $stmtMain->bindParam(':audit_main_id', $audit_main_id, PDO::PARAM_INT);
    $stmtMain->execute();
    $main_data = $stmtMain->fetch(PDO::FETCH_ASSOC);

    if (!$main_data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Auditoría principal no encontrada con ID: ' . htmlspecialchars($audit_main_id)]);
        exit;
    }
    $full_audit_data['main'] = $main_data;

    // 2. Función auxiliar para obtener respuestas de tablas departamentales
    if (!function_exists('getDepartmentResponses')) { // Definir solo si no existe
        function getDepartmentResponses($pdo_conn, $tableNameConstant, $current_audit_id) { // Nombre de variable cambiado
            if (!defined($tableNameConstant)) {
                error_log("Constante de tabla no definida: " . $tableNameConstant);
                return [];
            }
            try {
                $stmt = $pdo_conn->prepare("SELECT * FROM " . constant($tableNameConstant) . " WHERE auditoria_main_id = :audit_main_id LIMIT 1");
                $stmt->bindParam(':audit_main_id', $current_audit_id, PDO::PARAM_INT); // Usar variable correcta
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (\PDOException $e) {
                error_log("Error fetching department " . $tableNameConstant . " for audit " . $current_audit_id . ": " . $e->getMessage());
                return [];
            }
        }
    }

    // 3. Obtener datos de cada tabla de respuestas usando la variable correcta '$audit_main_id'
    $full_audit_data['global'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_GLOBAL', $audit_main_id);
    $full_audit_data['calidad'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_CALIDAD', $audit_main_id);
    $full_audit_data['rrhh'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_RRHH', $audit_main_id);
    $full_audit_data['produccion'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_PRODUCCION', $audit_main_id);
    $full_audit_data['comercial'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_COMERCIAL', $audit_main_id);
    $full_audit_data['marketing'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_MARKETING', $audit_main_id);
    $full_audit_data['logistica'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_LOGISTICA', $audit_main_id);
    $full_audit_data['id_dpto'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_ID', $audit_main_id);
    $full_audit_data['prl'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_PRL', $audit_main_id);
    $full_audit_data['adminfin'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_ADMINFIN', $audit_main_id);
    $full_audit_data['transporte'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_TRANSPORTE', $audit_main_id);
    $full_audit_data['compras'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_COMPRAS', $audit_main_id);
    $full_audit_data['ingenieria'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_INGENIERIA', $audit_main_id);
    $full_audit_data['sat'] = getDepartmentResponses($pdo, 'DB_TABLE_AUDITORIA_RESP_SAT', $audit_main_id);


    echo json_encode(['success' => true, 'data' => $full_audit_data]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("PDOException en fetch_audit_full_details para ID {$audit_main_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos al obtener detalles. Detalles: ' . $e->getMessage()]);
    exit;
} catch (\Exception $e) {
    http_response_code(500);
    error_log("General error en fetch_audit_full_details para ID {$audit_main_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error general del servidor al obtener detalles.']);
    exit;
}
?>