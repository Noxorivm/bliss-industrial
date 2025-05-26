<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// --- Configuración de la Base de Datos ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'bliss_bd');
define('DB_USER', 'phpmyadmin');
define('DB_PASS', 'Bliss2025!');
define('DB_CHARSET', 'utf8mb4');

// --- Nombres de Tablas ---
define('DB_TABLE_CRM', 'crm_clientes');
define('DB_TABLE_AUDITORIA', 'auditoria_bliss');
define('DB_TABLE_INTERACCIONES', 'crm_interacciones');
define('DB_TABLE_CATALOGO', 'catalogo_automatizaciones');

// --- Función para obtener la conexión PDO ---
function getPDO() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        // Loguear el error en lugar de mostrarlo directamente en producción
        error_log("Error de conexión PDO: " . $e->getMessage() . " (Host: " . DB_HOST . ", DB: " . DB_NAME . ", User: " . DB_USER . ")");
        // Para el usuario, podrías lanzar una excepción más genérica o manejar el error
        // Es importante no revelar detalles de la DB en producción.
        // En un entorno de desarrollo, podrías querer ver $e->getMessage()
        // die("Error de conexión a la base de datos. Por favor, contacta al administrador.");
        throw new \PDOException("Error de conexión a la base de datos. Por favor, revisa la configuración y los logs del servidor.", (int)$e->getCode());
    }
}
?>