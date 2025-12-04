<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

$pageTitle = 'Login';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php include __DIR__ . '/components/header.php'; ?>

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
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Sua senha secreta" required>
                </div>
            </div>
            
            <div class="form-actions">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark"></span>
                    Lembrar-me
                </label>
                <a href="<?php echo BASE_URL; ?>recover.php" class="forgot-link">Esqueceu a senha?</a>
            </div>
            
            <button type="submit" class="btn-login">
                ENTRAR
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            Não tem uma conta? <a href="<?php echo BASE_URL; ?>register.php">Registe-se aqui</a>
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
        font-family: 'Poppins', sans-serif;
    }

    /* Fundo Moderno */
    body {
        background: linear-gradient(135deg, #4f46e5 0%, #0f172a 100%);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .main-container {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    /* Cartão de Login */
    .auth-card {
        background: rgba(255, 255, 255, 1);
        width: 100%;
        max-width: 420px;
        padding: 40px;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        position: relative;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* Cabeçalho do Cartão */
    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .icon-bg {
        width: 60px;
        height: 60px;
        background: #e0e7ff;
        color: #4f46e5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 24px;
    }

    .auth-header h1 {
        font-size: 22px;
        color: #1e293b;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .auth-header p {
        color: #64748b;
        font-size: 14px;
    }

    /* Formulário e Inputs */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        color: #334155;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        transition: color 0.3s;
    }

    .input-wrapper input {
        width: 100%;
        padding: 14px 15px 14px 45px; /* Espaço para o ícone */
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.3s;
        outline: none;
        background: #f8fafc;
    }

    .input-wrapper input:focus {
        border-color: #4f46e5;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .input-wrapper input:focus + i {
        color: #4f46e5;
    }

    /* Checkbox e Links */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        font-size: 13px;
    }

    .checkbox-container {
        display: flex;
        align-items: center;
        cursor: pointer;
        color: #64748b;
    }

    .checkbox-container input {
        margin-right: 8px;
        accent-color: #4f46e5;
        width: 16px;
        height: 16px;
    }

    .forgot-link {
        color: #4f46e5;
        text-decoration: none;
        font-weight: 500;
    }

    .forgot-link:hover {
        text-decoration: underline;
    }

    /* Botão */
    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(to right, #4f46e5, #4338ca);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
    }

    .btn-login:active {
        transform: translateY(0);
    }

    /* Rodapé */
    .auth-footer {
        margin-top: 25px;
        text-align: center;
        font-size: 13px;
        color: #64748b;
    }

    .auth-footer a {
        color: #4f46e5;
        font-weight: 600;
        text-decoration: none;
    }
</style>

<?php include __DIR__ . '/components/footer.php'; ?>