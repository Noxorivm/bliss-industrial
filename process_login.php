<?php
session_start(); // Iniciar sesión

// --- ¡¡¡IMPORTANTE!!! ---
// --- ESTO ES SOLO PARA DESARROLLO ---
// --- REEMPLAZAR CON UN SISTEMA DE AUTENTICACIÓN SEGURO ---
$valid_username = 'admin_bliss'; // Cambia esto
$valid_password = 'Testeillo69'; // Cambia esto por una contraseña fuerte
// --- FIN SECCIÓN INSEGURA ---

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// --- Verificación (¡¡Insegura!!) ---
if ($username === $valid_username && $password === $valid_password) {
    // Credenciales correctas
    $_SESSION['user_logged_in'] = true;
    $_SESSION['username'] = $username; // Guardar nombre de usuario (opcional)

    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);

    // Redirigir al dashboard
    header('Location: dashboard/index.php');
    exit;
} else {
    // Credenciales incorrectas
    header('Location: login.php?error=invalid');
    exit;
}
?>