<?php
require_once '../dashboard/auth_check.php'; // Proteger el endpoint
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    // Por ahora, seleccionamos todos los activos.
    // La búsqueda y filtrado se hacen en cliente, pero podrían pasarse aquí con GET params.
    $stmt = $pdo->query("SELECT id, nombre_automatizacion, descripcion_corta, descripcion_completa, precio_estimado, moneda_precio, servicios_asociados, categoria_principal, tags, imagen_url FROM " . DB_TABLE_CATALOGO . " WHERE activo = 1 ORDER BY nombre_automatizacion ASC");
    $automations = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $automations]);

} catch (\PDOException $e) {
    error_log("Error fetching catalogo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener datos del catálogo.', 'error' => $e->getMessage()]);
}
?>