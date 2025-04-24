<?php
session_start();
// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard/index.php');
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
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-light-gray);
        }
        .login-box {
            max-width: 400px;
            width: 100%;
            padding: 2.5rem;
            background-color: var(--color-white);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-md);
        }
        .login-logo {
            max-height: 60px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box text-center">
            <img src="assets/img/logo.jfif" alt="BLISS Logo" class="login-logo">
            <h3 class="mb-4">Acceso al dashboard</h3>

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