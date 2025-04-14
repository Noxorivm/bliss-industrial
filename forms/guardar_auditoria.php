<?php
// Indicar que la respuesta será JSON
header('Content-Type: application/json');

// --- Configuración de la Base de Datos ---
// !!! REEMPLAZA CON TUS CREDENCIALES REALES DE IONOS !!!
define('DB_HOST', 'tu_host_ionos.db.es'); // Ej: db123456789.hosting-data.io
define('DB_NAME', 'tu_nombre_db');       // Ej: db123456789
define('DB_USER', 'tu_usuario_db');       // Ej: dbo123456789
define('DB_PASS', 'tu_contraseña_db');   // Tu contraseña

// --- Nombre de la tabla ---
define('DB_TABLE', 'auditoria_bliss'); // Nombre sugerido

// --- Recibir los datos JSON ---
$json_data = file_get_contents('php://input');
$formData = json_decode($json_data, true);

// --- Validación básica ---
if ($formData === null || !is_array($formData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos válidos.']);
    exit;
}

// --- Mapeo de datos y asignación a variables (con valores por defecto) ---
// Paso 1
$empresa_nombre = isset($formData['empresa_nombre']) ? trim($formData['empresa_nombre']) : null;
$contacto_nombre = isset($formData['contacto_nombre']) ? trim($formData['contacto_nombre']) : null;
$contacto_email = isset($formData['contacto_email']) ? trim($formData['contacto_email']) : null;
$contacto_telefono = isset($formData['contacto_telefono']) ? trim($formData['contacto_telefono']) : '';
$contacto_whatsapp = isset($formData['contacto_whatsapp']) && $formData['contacto_whatsapp'] === 'Sí' ? 1 : 0; // Guardar como 1 o 0

// Paso 2
$perfil_cargo = isset($formData['perfil_cargo']) ? trim($formData['perfil_cargo']) : null;
$cargo_otro_texto = isset($formData['cargo_otro_texto']) ? trim($formData['cargo_otro_texto']) : '';

// Paso 3
$sectores = isset($formData['sector']) && is_array($formData['sector']) ? implode(', ', $formData['sector']) : ''; // Convertir array a string
$sector_otro_texto = isset($formData['sector_otro_texto']) ? trim($formData['sector_otro_texto']) : '';
$empresa_empleados = isset($formData['empresa_empleados']) ? trim($formData['empresa_empleados']) : null;
$areas_mejora = isset($formData['areas_mejora']) && is_array($formData['areas_mejora']) ? implode(', ', $formData['areas_mejora']) : '';

// Paso 4
$sistemas = isset($formData['sistemas']) && is_array($formData['sistemas']) ? implode(', ', $formData['sistemas']) : '';
$sis_otro_texto = isset($formData['sis_otro_texto']) ? trim($formData['sis_otro_texto']) : '';
$retos = isset($formData['retos']) && is_array($formData['retos']) ? implode(', ', $formData['retos']) : '';
$reto_otro_texto = isset($formData['reto_otro_texto']) ? trim($formData['reto_otro_texto']) : '';

// Paso 5
$soluciones = isset($formData['soluciones']) && is_array($formData['soluciones']) ? implode(', ', $formData['soluciones']) : '';
$sol_otro_texto = isset($formData['sol_otro_texto']) ? trim($formData['sol_otro_texto']) : '';
$cuando_empezar = isset($formData['cuando_empezar']) ? trim($formData['cuando_empezar']) : null;
$urgencia_nivel = isset($formData['urgencia_nivel']) ? trim($formData['urgencia_nivel']) : null;

// Paso 6
$como_recibir = isset($formData['como_recibir']) ? trim($formData['como_recibir']) : null; // Debería ser 'Email' por defecto
$recibir_whatsapp = isset($formData['recibir_whatsapp']) && $formData['recibir_whatsapp'] === 'Sí' ? 1 : 0;

// --- Validación campos obligatorios (ejemplo) ---
if (empty($empresa_nombre) || empty($contacto_nombre) || empty($contacto_email) || empty($perfil_cargo) || empty($empresa_empleados) || empty($cuando_empezar) || empty($urgencia_nivel) || empty($como_recibir)) {
     http_response_code(400);
     echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']);
     exit;
}
if (!filter_var($contacto_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido.']);
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
    error_log("Error conexión DB auditoria: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor [DB Connect].']);
    exit;
}

// --- Preparar SQL INSERT ---
// Ajusta los nombres de columna a los EXACTOS de tu tabla
$sql = "INSERT INTO " . DB_TABLE . " (
            empresa_nombre, contacto_nombre, contacto_email, contacto_telefono, contacto_whatsapp,
            perfil_cargo, cargo_otro_texto,
            sectores, sector_otro_texto, empresa_empleados, areas_mejora,
            sistemas, sis_otro_texto, retos, reto_otro_texto,
            soluciones, sol_otro_texto, cuando_empezar, urgencia_nivel,
            como_recibir, recibir_whatsapp, fecha_envio
        ) VALUES (
            :empresa_nombre, :contacto_nombre, :contacto_email, :contacto_telefono, :contacto_whatsapp,
            :perfil_cargo, :cargo_otro_texto,
            :sectores, :sector_otro_texto, :empresa_empleados, :areas_mejora,
            :sistemas, :sis_otro_texto, :retos, :reto_otro_texto,
            :soluciones, :sol_otro_texto, :cuando_empezar, :urgencia_nivel,
            :como_recibir, :recibir_whatsapp, NOW()
        )";

try {
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':empresa_nombre', $empresa_nombre);
    $stmt->bindParam(':contacto_nombre', $contacto_nombre);
    $stmt->bindParam(':contacto_email', $contacto_email);
    $stmt->bindParam(':contacto_telefono', $contacto_telefono);
    $stmt->bindParam(':contacto_whatsapp', $contacto_whatsapp, PDO::PARAM_INT);
    $stmt->bindParam(':perfil_cargo', $perfil_cargo);
    $stmt->bindParam(':cargo_otro_texto', $cargo_otro_texto);
    $stmt->bindParam(':sectores', $sectores);
    $stmt->bindParam(':sector_otro_texto', $sector_otro_texto);
    $stmt->bindParam(':empresa_empleados', $empresa_empleados);
    $stmt->bindParam(':areas_mejora', $areas_mejora);
    $stmt->bindParam(':sistemas', $sistemas);
    $stmt->bindParam(':sis_otro_texto', $sis_otro_texto);
    $stmt->bindParam(':retos', $retos);
    $stmt->bindParam(':reto_otro_texto', $reto_otro_texto);
    $stmt->bindParam(':soluciones', $soluciones);
    $stmt->bindParam(':sol_otro_texto', $sol_otro_texto);
    $stmt->bindParam(':cuando_empezar', $cuando_empezar);
    $stmt->bindParam(':urgencia_nivel', $urgencia_nivel);
    $stmt->bindParam(':como_recibir', $como_recibir);
    $stmt->bindParam(':recibir_whatsapp', $recibir_whatsapp, PDO::PARAM_INT);

    $stmt->execute();

    // Éxito
    echo json_encode(['success' => true, 'message' => 'Auditoría enviada correctamente.']);

    // Opcional: Enviar notificación por email al admin
    // mail($receiving_email_address, "Nueva Auditoría Recibida: " . $empresa_nombre, print_r($formData, true), "From: noreply@blissindustrial.eu");


} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error INSERT DB auditoria: " . $e->getMessage() . " Data: " . print_r($formData, true));
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor [DB Insert].']);
    exit;
}

?>