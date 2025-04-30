<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

session_start(); // Acceder a la sesión PRIMERO

// Destruir todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir a la página de login
// Ruta relativa desde /dashboard/ a /login.php
header('Location: ../login.php?loggedout=true'); // Añadir parámetro opcional
exit;
?>