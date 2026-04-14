<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lolita's DB | Acceso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #ff85a2;
            --bg-soft: #fdf2f5;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-soft);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 15px 35px rgba(255, 133, 162, 0.15);
            width: 100%;
            max-width: 400px;
        }

        .brand-logo {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.8rem;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #eee;
            background-color: #fafafa;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(255, 133, 162, 0.2);
            border-color: var(--primary-color);
        }

        .btn-login {
            background-color: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-login:hover {
            background-color: #f76d8e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 133, 162, 0.4);
        }

        .alert-error {
            background-color: #fff1f0;
            color: #d85c5c;
            border-radius: 10px;
            padding: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #ffa39e;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-logo">
            <i class="fa-solid fa-cake-candles"></i>Lolita's Manager
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation me-2"></i> Usuario o contraseña incorrectos.
            </div>
        <?php endif; ?>

        <form action="../../app/controllers/AuthController.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="Ej: admin_lolitas" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                Entrar al Sistema
            </button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">¿Olvidaste tus credenciales?<br>Contacta al administrador.</small>
        </div>
    </div>

</body>
</html>