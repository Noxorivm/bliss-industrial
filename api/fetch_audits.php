<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// Incluir el verificador de sesión PRIMERO
require_once '../dashboard/auth_check.php'; // Ajusta ruta si es necesario

// Indicar que la respuesta será JSON y con UTF-8
header('Content-Type: application/json; charset=utf-8');

// --- Configuración DB ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bliss_bd');
define('DB_USER', 'phpmyadmin');
define('DB_PASS', 'Bliss2025!');
define('DB_TABLE', 'auditoria_bliss');

// --- Conexión DB (ASEGÚRATE QUE CHARSET ESTÁ PRESENTE) ---
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
    error_log("Error conexión DB fetch_audits: " . $e->getMessage());
    // Devolver error en formato JSON
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor [DB Connect].']);
    exit;
}

// --- Consulta SQL ---
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
        ORDER BY fecha_envio DESC";

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    // DataTables espera un objeto con una clave "data"
    // Asegurarse que los datos se devuelven como UTF-8 (json_encode lo hace por defecto si PHP está bien configurado)
    echo json_encode(['data' => $results]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error fetching audits: " . $e->getMessage());
    // Devolver error en formato JSON
    echo json_encode(['data' => [], 'error' => 'Error al obtener los datos de auditoría.']);
    exit;
}
?>