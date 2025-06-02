<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar/Eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// Incluir el verificador de sesión PRIMERO
require_once '../dashboard/auth_check.php'; // Asume que auth_check.php está en /dashboard/
// Incluir configuración de base de datos y getPDO()
require_once '../config/database.php';    // Asume que config/database.php está en /config/

// Indicar que la respuesta será JSON y con UTF-8 (después de los includes)
header('Content-Type: application/json; charset=utf-8');

// Verificar si las constantes necesarias están definidas (desde config/database.php)
if (!defined('DB_TABLE_AUDITORIA_MAIN') || !function_exists('getPDO')) {
    http_response_code(500);
    error_log("Error crítico en fetch_audits.php: Constantes de DB o getPDO() no definidas. Verifica config/database.php.");
    echo json_encode(['data' => [], 'error' => 'Error de configuración interna del servidor.']);
    exit;
}

try {
    $pdo = getPDO(); // Obtener la instancia de PDO desde database.php
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error conexión DB en fetch_audits (getPDO): " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor [DB Connect - fetch_audits].']);
    exit;
} catch (\Exception $e) { // Capturar otras excepciones de getPDO
    http_response_code(500);
    error_log("Error general en getPDO desde fetch_audits (catch Exception): " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor al configurar la conexión [fetch_audits].']);
    exit;
}

// --- Consulta SQL para la tabla principal de auditorías ---
// Selecciona las columnas que están en 'auditoria_bliss_main' y que se mostrarán en la tabla DataTables.
// Los detalles completos (de las tablas _resp_) se cargarán en el modal.
$sql = "SELECT
            id,
            fecha_envio,
            empresa_nombre,
            contacto_nombre,
            contacto_email,
            contacto_telefono,
            perfil_cargo,
            -- cargo_otro_texto, -- Opcional para la tabla principal, sí para el modal
            empresa_empleados,
            urgencia_nivel,
            cuando_empezar
            -- Para el modal de detalle, necesitaremos TODAS las columnas de auditoria_bliss_main
            -- y luego haremos consultas adicionales para las tablas _resp_ o un JOIN complejo.
            -- Por ahora, esta consulta es para la vista de tabla del dashboard.
            -- Si quieres que el modal use los datos ya cargados por DataTables,
            -- entonces ESTE SELECT debe traer todas las columnas de auditoria_bliss_main.
            -- Ejemplo incluyendo todas las de auditoria_bliss_main para el modal:
            -- id, fecha_envio, empresa_nombre, contacto_nombre, contacto_email,
            -- contacto_telefono, contacto_whatsapp, perfil_cargo, cargo_otro_texto,
            -- empresa_empleados, cuando_empezar, urgencia_nivel,
            -- como_recibir, recibir_whatsapp_informe, ip_address
        FROM " . DB_TABLE_AUDITORIA_MAIN . " -- Usa la constante para la tabla principal
        ORDER BY fecha_envio DESC";

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    // Si el modal de detalle necesita TODOS los datos de la tabla principal,
    // y no quieres hacer otra llamada AJAX para el modal,
    // asegúrate que el SELECT de arriba incluye todas esas columnas.
    // La configuración 'columns' de DataTables en dashboard/index.php solo usará las que definas allí.

    echo json_encode(['data' => $results]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error SQL en fetch_audits: " . $e->getMessage() . " --- SQL: " . $sql);
    echo json_encode(['data' => [], 'error' => 'Error al obtener los datos de auditorías. Detalles: ' . $e->getMessage()]);
    exit;
}
?>