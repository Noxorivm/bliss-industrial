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
define('DB_TABLE_AUDITORIA_MAIN', 'auditoria_bliss_main');

// Tablas de Respuestas Específicas de Auditorías Departamentales
define('DB_TABLE_AUDITORIA_RESP_CALIDAD', 'auditoria_resp_calidad');
define('DB_TABLE_AUDITORIA_RESP_GLOBAL', 'auditoria_resp_global'); // Asumiendo que la llamaste así
define('DB_TABLE_AUDITORIA_RESP_RRHH', 'auditoria_resp_rrhh');
define('DB_TABLE_AUDITORIA_RESP_PRODUCCION', 'auditoria_resp_produccion');
define('DB_TABLE_AUDITORIA_RESP_COMERCIAL', 'auditoria_resp_comercial');
define('DB_TABLE_AUDITORIA_RESP_MARKETING', 'auditoria_resp_marketing');
define('DB_TABLE_AUDITORIA_RESP_LOGISTICA', 'auditoria_resp_logistica');
define('DB_TABLE_AUDITORIA_RESP_ID', 'auditoria_resp_id'); // Para I+D
define('DB_TABLE_AUDITORIA_RESP_PRL', 'auditoria_resp_prl');
define('DB_TABLE_AUDITORIA_RESP_ADMINFIN', 'auditoria_resp_adminfin');
define('DB_TABLE_AUDITORIA_RESP_TRANSPORTE', 'auditoria_resp_transporte');
define('DB_TABLE_AUDITORIA_RESP_COMPRAS', 'auditoria_resp_compras');
define('DB_TABLE_AUDITORIA_RESP_INGENIERIA', 'auditoria_resp_ingenieria');
define('DB_TABLE_AUDITORIA_RESP_SAT', 'auditoria_resp_sat');

// Tabla del Catálogo de Automatizaciones
define('DB_TABLE_CATALOGO', 'catalogo_automatizaciones');

if (!defined('RECAPTCHA_SECRET_KEY')) {
    define('RECAPTCHA_SECRET_KEY', '6Lfl_jcrAAAAAKIimDKPMcoGEZvxQKsLCX611Q4N'); // TU CLAVE SECRETA REAL
}


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
        error_log("Error de conexión PDO: " . $e->getMessage() . " (Host: " . DB_HOST . ", DB: " . DB_NAME . ", User: " . DB_USER . ")");
        throw new \PDOException("Error de conexión a la base de datos. Por favor, revisa la configuración y los logs del servidor.", (int)$e->getCode());
    }
}
?>