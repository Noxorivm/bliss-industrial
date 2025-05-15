<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// Incluir el verificador de sesión PRIMERO
require_once '../dashboard/auth_check.php';
// Incluir configuración de base de datos y getPDO()
require_once '../config/database.php';

// Indicar que la respuesta será JSON y con UTF-8
header('Content-Type: application/json; charset=utf-8');

// --- NO SE NECESITAN define() para DB aquí porque vienen de database.php ---
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'bliss_bd');
// define('DB_USER', 'bliss_user');
// define('DB_PASS', 'HiiJI7y)8jL@[s_G');
// define('DB_TABLE', 'auditoria_bliss'); // <-- ESTA LÍNEA SE ELIMINA O YA NO SE USA ASÍ

try {
    $pdo = getPDO(); // Obtener la instancia de PDO desde database.php
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error conexión DB fetch_audits: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor [DB Connect].']);
    exit;
}

// --- Consulta SQL (Usar la constante correcta DB_TABLE_AUDITORIA) ---
$sql = "SELECT
            id, fecha_envio, empresa_nombre, contacto_nombre, contacto_email,
            contacto_telefono, contacto_whatsapp, perfil_cargo, cargo_otro_texto,
            sectores, sector_otro_texto, empresa_empleados, areas_mejora,
            sistemas, sis_otro_texto, retos, reto_otro_texto,
            soluciones, sol_otro_texto, cuando_empezar, urgencia_nivel,
            como_recibir, recibir_whatsapp, ip_address
        FROM " . DB_TABLE_AUDITORIA . " -- <<<--- CAMBIO AQUÍ: Usar DB_TABLE_AUDITORIA
        ORDER BY fecha_envio DESC";

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    echo json_encode(['data' => $results]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error fetching audits: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error al obtener los datos de auditoría.']);
    exit;
}
?>