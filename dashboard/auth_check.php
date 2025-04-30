<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// Iniciar o reanudar la sesión ANTES de cualquier salida
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // No está logueado, redirigir a la página de login
    // La ruta es relativa DESDE el script que INCLUYE este archivo
    // Si index.php incluye auth_check.php (ambos en /dashboard), '../login.php' es correcto.
    // Si fetch_audits.php (en /api) incluye ../dashboard/auth_check.php, la redirección aún debe ser a ../login.php (relativo a fetch_audits)
    // Para evitar confusión, usar una ruta absoluta desde la raíz del sitio es más seguro si la estructura de directorios cambia.
     $login_page_url = '/login.php'; // Asume que login.php está en la raíz del sitio
    // $login_page_url = '../login.php'; // Usar si estás seguro de la estructura relativa

    header('Location: ' . $login_page_url . '?error=unauthorized');
    exit; // Detener la ejecución del script actual
}

// Si llega aquí, el usuario está logueado y puede continuar cargando la página.
?>