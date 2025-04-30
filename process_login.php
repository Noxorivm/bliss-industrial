<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

session_start(); // Iniciar sesión PRIMERO

// --- ¡¡¡IMPORTANTE!!! ---
// --- ESTO ES SOLO PARA DESARROLLO ---
// --- REEMPLAZAR CON UN SISTEMA DE AUTENTICACIÓN SEGURO ---
$valid_username = 'admin_bliss'; // Cambia esto
$valid_password = 'Bliss2025!'; // Cambia esto por una contraseña fuerte
// --- FIN SECCIÓN INSEGURA ---

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// --- Verificación (¡¡Insegura!!) ---
// Más adelante, aquí compararías el hash de la contraseña:
// if ($user && password_verify($password, $user['password_hash'])) { ... }
if ($username === $valid_username && $password === $valid_password) {
    // Credenciales correctas
    // Regenerar ID de sesión ANTES de establecer variables de sesión sensibles
    session_regenerate_id(true);

    $_SESSION['user_logged_in'] = true;
    $_SESSION['username'] = $username; // Guardar nombre de usuario (opcional)

    // Redirigir al dashboard
    header('Location: dashboard/index.php'); // Ruta relativa correcta
    exit;
} else {
    // Credenciales incorrectas
    // Destruir cualquier sesión potencial existente
    session_destroy();
    header('Location: login.php?error=invalid'); // Ruta relativa correcta
    exit;
}
?>