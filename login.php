<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEICTECH System Production</title>
    <link rel="icon" type="image/png" href="images/icon-seictech.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>

<div class="main-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="icon-bg">
                <i class="fas fa-industry"></i>
            </div>
            <h1>Bem-vindo ao <?php echo APP_NAME; ?></h1>
            <p>Entre com suas credenciais para continuar</p>
        </div>
        
        <?php include __DIR__ . '/components/alerts.php'; ?>
        
        <form method="POST" action="<?php echo BASE_URL; ?>controllers/auth_controller.php" class="auth-form" id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope icon-left"></i>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    
                    <input type="password" id="password" name="password" placeholder="Sua senha secreta" required>
                    
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <div class="form-actions">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark"></span>
                    Lembrar-me
                </label>
                <a href="<?php echo BASE_URL; ?>forgot-password.php" class="forgot-link">Esqueceu a senha?</a>
            </div>
            
            <button type="submit" class="btn-login">
                ENTRAR
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            <!-- O link de registro foi removido a pedido do cliente -->
        </div>
    </div>
</div>

<style>
    /* Reset Básico para remover scroll */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html, body {
        height: 100%;
        overflow: hidden; /* O Segredo para não ter scroll */
    }

    body {
        background: linear-gradient(135deg, #1e40af 0%, #0f172a 100%);
        font-family: 'Inter', 'Poppins', 'Segoe UI', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main-container {
        width: 100%;
        max-width: 480px;
        padding: 20px;
    }

    .auth-card {
        background: white;
        border-radius: 24px;
        padding: 50px 40px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
    }

    .auth-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .icon-bg {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        box-shadow: 0 10px 30px rgba(30, 64, 175, 0.3);
    }

    .icon-bg i {
        font-size: 42px;
        color: white;
    }

    .auth-header h1 {
        font-size: 28px;
        color: #0f172a;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .auth-header p {
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
    }

    .auth-form {
        margin-top: 30px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-wrapper i:first-child {
        position: absolute;
        left: 16px;
        color: #94a3b8;
        font-size: 16px;
    }

    .input-wrapper input {
        width: 100%;
        padding: 14px 45px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s;
        font-family: inherit;
    }

    .input-wrapper input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .toggle-password,
    .toggle-password-confirm {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #94a3b8;
        font-size: 16px;
    }

    .toggle-password:hover,
    .toggle-password-confirm:hover {
        color: #1e40af;
    }


    .btn-login {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(30, 64, 175, 0.4);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .auth-footer {
        margin-top: 30px;
        text-align: center;
        font-size: 14px;
        color: #64748b;
    }

    .auth-footer a {
        color: #1e40af;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.3s;
    }

    .auth-footer a:hover {
        color: #3b82f6;
    }
</style>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.querySelector('.toggle-password');
    
    // Alternar entre password e text
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>