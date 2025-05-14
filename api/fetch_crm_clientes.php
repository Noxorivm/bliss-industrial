<?php
require_once '../dashboard/auth_check.php'; // Seguridad

header('Content-Type: application/json; charset=utf-8');

// --- Configuración DB (igual que en guardar_auditoria.php y contact.php) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bliss_bd');
define('DB_USER', 'phpmyadmin');
define('DB_PASS', 'Bliss2025!');
define('CRM_CLIENTS_TABLE', 'crm_clientes'); // Tabla de clientes

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
    error_log("Error conexión DB fetch_crm_clientes: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor [DB Connect].']);
    exit;
}

// --- Consulta SQL ---
// Selecciona las columnas que quieres mostrar en la tabla principal del CRM
$sql = "SELECT
            id,
            nombre_empresa,
            persona_contacto,
            email_contacto,
            telefono_contacto,
            origen_lead,
            estado_lead,
            fecha_creacion
            -- Puedes añadir más columnas si las necesitas directamente en la tabla
        FROM " . CRM_CLIENTS_TABLE . "
        ORDER BY fecha_creacion DESC";

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    echo json_encode(['data' => $results]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error fetching crm_clientes: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error al obtener los datos de clientes.']);
    exit;
}
?>