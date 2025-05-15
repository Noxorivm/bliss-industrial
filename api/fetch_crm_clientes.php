<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

require_once '../dashboard/auth_check.php'; // Reutilizamos el verificador de sesión del dashboard
require_once '../config/database.php';    // <<<--- Incluir configuración de BD y getPDO()

header('Content-Type: application/json; charset=utf-8');

// --- NO SE NECESITAN define() para DB aquí ---
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'bliss_bd');
// define('DB_USER', 'bliss_user');
// define('DB_PASS', 'HiiJI7y)8jL@[s_G');
// define('DB_TABLE_CRM', 'crm_clientes'); // <-- ESTA LÍNEA SE ELIMINA O YA NO SE USA ASÍ

try {
    $pdo = getPDO(); // Obtener la instancia de PDO desde database.php
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error conexión DB fetch_crm_clientes: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor [DB Connect CRM].']);
    exit;
}

// --- Consulta SQL (Usar la constante correcta DB_TABLE_CRM) ---
$sql = "SELECT
            id,
            nombre_empresa,
            persona_contacto,
            email_contacto,
            telefono_contacto,
            origen_lead,
            estado_lead,
            fecha_creacion
        FROM " . DB_TABLE_CRM . " -- <<<--- CAMBIO AQUÍ: Usar DB_TABLE_CRM
        ORDER BY fecha_creacion DESC";

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    echo json_encode(['data' => $results]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error fetching CRM clients: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error al obtener los datos de clientes.']);
    exit;
}
?>