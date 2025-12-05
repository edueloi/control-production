<?php
require_once __DIR__ . '/config/config.php';
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}
$pageTitle = 'Recuperar Senha';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="icon" type="image/png" href="images/icon-seictech.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #4f46e5 0%, #0f172a 100%);
            display: flex; justify-content: center; align-items: center;
            height: 100vh; font-family: 'Poppins', sans-serif;
        }
        .auth-card {
            background: #fff; max-width: 420px; padding: 40px;
            border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .icon-bg {
            width: 60px; height: 60px; background: #e0e7ff; color: #4f46e5;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 15px; font-size: 24px;
        }
        .auth-header h1 { font-size: 22px; color: #1e293b; margin-bottom: 8px; }
        .auth-header p { color: #64748b; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #334155; font-size: 13px; margin-bottom: 8px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .input-wrapper input {
            width: 100%; padding: 14px 15px 14px 45px;
            border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 14px; outline: none; background: #f8fafc;
        }
        .input-wrapper input:focus { border-color: #4f46e5; }
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(to right, #4f46e5, #4338ca);
            color: white; border: none; border-radius: 12px; font-size: 15px;
            cursor: pointer; transition: all 0.2s;
        }
        .auth-footer { margin-top: 25px; text-align: center; font-size: 13px; }
        .auth-footer a { color: #4f46e5; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-header">
        <div class="icon-bg"><i class="fas fa-key"></i></div>
        <h1>Recuperar Senha</h1>
        <p>Insira seu e-mail para receber o link de recuperação.</p>
    </div>
    
    <?php include __DIR__ . '/components/alerts.php'; ?>
    
    <form method="POST" action="<?php echo BASE_URL; ?>controllers/auth_controller.php" class="auth-form">
        <input type="hidden" name="action" value="forgot_password">
        <div class="form-group">
            <label for="email">E-mail</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>
            </div>
        </div>
        <button type="submit" class="btn-submit">Enviar Link de Recuperação</button>
    </form>
    
    <div class="auth-footer">
        Lembrou a senha? <a href="<?php echo BASE_URL; ?>login.php">Faça login</a>
    </div>
</div>
</body>
</html>