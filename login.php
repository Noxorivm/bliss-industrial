<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

session_start(); // Iniciar sesión PRIMERO

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard/index.php'); // Ruta relativa al dashboard
    exit;
}

$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $error_message = 'Usuario o contraseña incorrectos.';
    } elseif ($_GET['error'] === 'unauthorized') {
         $error_message = 'Debes iniciar sesión para acceder al dashboard.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard BLISS</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet"> <!-- Estilos generales -->
    <link href="assets/css/dashboard.css" rel="stylesheet"> <!-- Estilos específicos login -->
    <link href="assets/img/favicon.png" rel="icon">
    <style>
        /* Estilos rápidos para centrar */
        html, body { height: 100%; margin: 0; } /* Asegurar altura completa */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-light-gray); /* Usar variable CSS */
        }
        .login-box {
            max-width: 400px;
            width: 100%;
            padding: 2.5rem;
            background-color: var(--color-white); /* Usar variable CSS */
            border-radius: 0.5rem;
            box-shadow: var(--shadow-md); /* Usar variable CSS */
        }
        .login-logo {
            max-height: 60px;
            margin-bottom: 1.5rem;
        }
        /* Añadir estilo para el botón cta-main si no está en dashboard.css */
         .cta-main {
            background: var(--color-black); color: var(--color-white); border: 2px solid var(--color-black);
            padding: 12px 35px; border-radius: 50px; text-transform: uppercase;
            font-weight: 700; transition: all var(--transition-speed-medium) var(--easing-smooth);
            display: inline-flex; align-items: center; gap: 8px;
        }
        .cta-main:hover {
            background: var(--accent-color); border-color: var(--accent-color); color: var(--color-black);
            transform: translateY(-3px) scale(1.02); box-shadow: 0 5px 15px rgba(250, 204, 21, 0.3);
        }
        .cta-main:hover i { transform: translateX(5px); }

    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box text-center">
            <img src="assets/img/logo.jfif" alt="BLISS Logo" class="login-logo">
            <h3 class="mb-4">Acceso al Dashboard</h3>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="process_login.php" method="post">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Usuario" required>
                    <label for="username">Usuario</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    <label for="password">Contraseña</label>
                </div>
                <button type="submit" class="btn btn-warning w-100 cta-main">Entrar</button>
            </form>
            <p class="mt-4 text-muted small"><a href="index.html">← Volver a la web</a></p>
        </div>
    </div>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>