<?php
session_start(); // Iniciar o reanudar la sesión

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // No está logueado, redirigir a la página de login
    // Ajusta la ruta a login.php si es necesario (ej. /login.php si está en la raíz)
    header('Location: ../login.php?error=unauthorized');
    exit; // Detener la ejecución del script actual
}

// Si llega aquí, el usuario está logueado y puede continuar cargando la página.
?>