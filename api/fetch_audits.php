<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// Incluir el verificador de sesión PRIMERO
// La ruta es relativa DESDE /api/ HACIA /dashboard/
require_once '../dashboard/auth_check.php';

// Indicar que la respuesta será JSON (después de auth_check)
header('Content-Type: application/json');

// --- Configuración DB (debe ser idéntica a guardar_auditoria.php) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bliss_bd');
define('DB_USER', 'bliss_user');
define('DB_PASS', 'HiiJI7y)8jL@[s_G');
define('DB_TABLE', 'auditoria_bliss');

// --- Conexión DB ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error conexión DB fetch_audits: " . $e->getMessage());
    // Devolver error en formato JSON esperado por DataTables (aunque sea un error)
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor [DB Connect].']);
    exit;
}

// --- Consulta SQL ---
// Selecciona las columnas que quieres mostrar en la tabla
$sql = "SELECT
            id,
            fecha_envio,
            empresa_nombre,
            contacto_nombre,
            contacto_email,
            contacto_telefono,
            perfil_cargo,
            empresa_empleados,
            urgencia_nivel,
            cuando_empezar
            -- Añade más columnas si las necesitas en la tabla --
        FROM " . DB_TABLE . "
        ORDER BY fecha_envio DESC"; // Ordenar por fecha descendente

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    // DataTables espera un objeto con una clave "data"
    echo json_encode(['data' => $results]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error fetching audits: " . $e->getMessage());
    // Devolver error en formato JSON esperado por DataTables
    echo json_encode(['data' => [], 'error' => 'Error al obtener los datos de auditoría.']);
    exit;
}
?>