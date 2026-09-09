<?php
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Security\Csrf;

ob_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_SESSION['id_usuario'])) {
    header("Location: index.php?view=dashboard", true, 303);
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <title>Lolita's DB | Acceso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/views/login.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/login.css') ?>">
</head>
<body>

    <div class="login-card">
        <div class="brand-logo">
            <i class="fa-solid fa-cake-candles"></i>
            <span>Lolita's Manager</span>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation me-2"></i> Credenciales incorrectas.
            </div>
        <?php endif; ?>

        <form action="auth.php?action=login" method="POST">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label small fw-bold text-uppercase">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control shadow-none" placeholder="Ingresa tu usuario" required autocomplete="username">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-uppercase">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control shadow-none" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                Iniciar Sesión <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-5 footer-text">
            ¿Problemas con el acceso?<br>
            <span class="text-muted fw-bold">Contacta al Administrador de TI</span>
        </div>
    </div>

    <script src="js/views/login.js"></script>
</body>
</html>
