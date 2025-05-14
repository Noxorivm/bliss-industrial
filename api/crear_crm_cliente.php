<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// Incluir el verificador de sesión PRIMERO
// La ruta es relativa DESDE /api/ HACIA /dashboard/
require_once '../dashboard/auth_check.php';

// Indicar que la respuesta será JSON y con UTF-8
header('Content-Type: application/json; charset=utf-8');

// --- Configuración DB (igual que en otros scripts API) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bliss_bd');
define('DB_USER', 'phpmyadmin');
define('DB_PASS', 'Bliss2025!');
define('CRM_CLIENTS_TABLE', 'crm_clientes');

// --- Recibir los datos JSON enviados por JavaScript ---
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true); // true para obtener un array asociativo

// --- Validación básica de datos ---
if ($data === null || !is_array($data)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o vacíos.']);
    exit;
}

// --- Mapeo y limpieza de datos del formulario ---
// Los nombres aquí deben coincidir con los atributos 'name' del formulario HTML en el modal
$nombre_empresa = isset($data['nombre_empresa']) ? trim($data['nombre_empresa']) : null;
$persona_contacto = isset($data['persona_contacto']) ? trim($data['persona_contacto']) : null;
$email_contacto = isset($data['email_contacto']) ? trim($data['email_contacto']) : null;
$telefono_contacto = isset($data['telefono_contacto']) ? trim($data['telefono_contacto']) : null;
$origen_lead = isset($data['origen_lead']) ? trim($data['origen_lead']) : 'Manual CRM'; // Valor por defecto
$estado_lead = isset($data['estado_lead']) ? trim($data['estado_lead']) : 'Nuevo';       // Valor por defecto
$notas_generales_cliente = isset($data['notas_generales_cliente']) ? trim($data['notas_generales_cliente']) : null;
// id_auditoria_bliss y asignado_a_usuario_id se manejarán más adelante o se pueden dejar NULL

// --- Validación de campos obligatorios del servidor ---
if (empty($nombre_empresa)) {
     http_response_code(400);
     echo json_encode(['success' => false, 'message' => 'El nombre de la empresa es obligatorio.']);
     exit;
}
if (!empty($email_contacto) && !filter_var($email_contacto, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
    exit;
}


// --- Conexión a la Base de Datos (PDO) ---
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
    error_log("Error conexión DB crear_crm_cliente: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor [DB Connect].']);
    exit;
}

// --- Preparar la consulta SQL INSERT ---
$sql = "INSERT INTO " . CRM_CLIENTS_TABLE . "
            (nombre_empresa, persona_contacto, email_contacto, telefono_contacto, origen_lead, estado_lead, notas_generales_cliente, fecha_creacion)
        VALUES
            (:nombre_empresa, :persona_contacto, :email_contacto, :telefono_contacto, :origen_lead, :estado_lead, :notas_generales_cliente, NOW())";

try {
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':nombre_empresa', $nombre_empresa);
    $stmt->bindParam(':persona_contacto', $persona_contacto);
    $stmt->bindParam(':email_contacto', $email_contacto);
    $stmt->bindParam(':telefono_contacto', $telefono_contacto);
    $stmt->bindParam(':origen_lead', $origen_lead);
    $stmt->bindParam(':estado_lead', $estado_lead);
    $stmt->bindParam(':notas_generales_cliente', $notas_generales_cliente);
    // id_auditoria_bliss y asignado_a_usuario_id no se bindean aquí, se insertarán como NULL si no se proporcionan

    $stmt->execute();
    $new_client_id = $pdo->lastInsertId(); // Obtener el ID del cliente recién creado

    // Éxito
    http_response_code(201); // 201 Created
    echo json_encode([
        'success' => true,
        'message' => 'Cliente añadido con éxito.',
        'new_client_id' => $new_client_id // Devolver el ID del nuevo cliente (opcional)
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    // Comprobar error de duplicado de email si tienes la restricción UNIQUE
    if ($e->getCode() == 23000) { // Código de error SQLSTATE para violación de integridad (puede variar)
        error_log("Error INSERT crm_cliente (duplicado?): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al guardar el cliente. Es posible que el email ya exista.']);
    } else {
        error_log("Error INSERT crm_cliente: " . $e->getMessage() . " Data: " . print_r($data, true));
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor al guardar el cliente.']);
    }
    exit;
}
?>