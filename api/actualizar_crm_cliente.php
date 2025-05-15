<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

require_once '../dashboard/auth_check.php'; // Verificar sesión
require_once '../config/database.php';    // Incluir configuración de BD y getPDO()

header('Content-Type: application/json; charset=utf-8');

$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);

if ($formData === null || !is_array($formData) || !isset($formData['cliente_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o ID de cliente faltante.']);
    exit;
}

$cliente_id = filter_var($formData['cliente_id'], FILTER_VALIDATE_INT);
if (!$cliente_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cliente inválido.']);
    exit;
}

// Mapeo y limpieza de datos (similar a crear_crm_cliente.php)
$nombre_empresa = isset($formData['nombre_empresa']) ? trim($formData['nombre_empresa']) : null;
$persona_contacto = isset($formData['persona_contacto']) ? trim($formData['persona_contacto']) : '';
$email_contacto = isset($formData['email_contacto']) ? trim($formData['email_contacto']) : '';
$telefono_contacto = isset($formData['telefono_contacto']) ? trim($formData['telefono_contacto']) : '';
$origen_lead = isset($formData['origen_lead']) ? trim($formData['origen_lead']) : 'Manual CRM';
$estado_lead = isset($formData['estado_lead']) ? trim($formData['estado_lead']) : 'Nuevo';
$notas_generales_cliente = isset($formData['notas_generales_cliente']) ? trim($formData['notas_generales_cliente']) : '';

// Validación (ejemplo, expandir según sea necesario)
if (empty($nombre_empresa)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre de la empresa es obligatorio.']);
    exit;
}
if (!empty($email_contacto) && !filter_var($email_contacto, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de email inválido.']);
    exit;
}


try {
    $pdo = getPDO();
    $sql = "UPDATE " . DB_TABLE_CRM . " SET
                nombre_empresa = :nombre_empresa,
                persona_contacto = :persona_contacto,
                email_contacto = :email_contacto,
                telefono_contacto = :telefono_contacto,
                origen_lead = :origen_lead,
                estado_lead = :estado_lead,
                notas_generales_cliente = :notas_generales_cliente
                -- fecha_modificacion se actualiza automáticamente por la DB
            WHERE id = :cliente_id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':nombre_empresa', $nombre_empresa);
    $stmt->bindParam(':persona_contacto', $persona_contacto);
    $stmt->bindParam(':email_contacto', $email_contacto);
    $stmt->bindParam(':telefono_contacto', $telefono_contacto);
    $stmt->bindParam(':origen_lead', $origen_lead);
    $stmt->bindParam(':estado_lead', $estado_lead);
    $stmt->bindParam(':notas_generales_cliente', $notas_generales_cliente);
    $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente.']);
        } else {
            // No se actualizó ninguna fila, puede ser que los datos fueran los mismos
            echo json_encode(['success' => true, 'message' => 'No se realizaron cambios (datos idénticos) o cliente no encontrado.']);
        }
    } else {
        // Esto es redundante si ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION está activo,
        // pero se deja por si acaso se cambia la configuración de errores.
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la actualización.']);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error UPDATE DB actualizar_crm_cliente: " . $e->getMessage() . " Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno al actualizar el cliente. [DB Update CRM]. Detalles: ' . $e->getMessage()]);
    exit;
}
?>