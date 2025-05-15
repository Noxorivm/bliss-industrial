<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
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
// Añade más constantes de tablas aquí si es necesario

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
        error_log("Error de conexión PDO: " . $e->getMessage());
        // Para el usuario, podrías lanzar una excepción más genérica o manejar el error
        throw new \PDOException("Error de conexión a la base de datos.", (int)$e->getCode());
    }
}
?>