<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Redefinir Senha';
$token = $_GET['token'] ?? null;
$error = null;
$user = null;

if (!$token) {
    $error = "Token de recuperação não fornecido ou inválido.";
} else {
    $tokenHash = hash('sha256', $token);
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > datetime('now', 'localtime')");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "Token inválido ou expirado. Por favor, solicite uma nova recuperação de senha.";
    }
}
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
        }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .icon-bg {
            width: 60px; height: 60px; background: #e0e7ff; color: #4f46e5;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 15px; font-size: 24px;
        }
        .auth-header h1 { font-size: 22px; color: #1e293b; margin-bottom: 8px; }
        .auth-header p { color: #64748b; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .input-wrapper input {
            width: 100%; padding: 14px 15px 14px 45px;
            border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 14px; outline: none; background: #f8fafc;
        }
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(to right, #4f46e5, #4338ca);
            color: white; border: none; border-radius: 12px; font-size: 15px;
            cursor: pointer;
        }
        .auth-footer { margin-top: 25px; text-align: center; font-size: 13px; }
        .auth-footer a { color: #4f46e5; font-weight: 600; text-decoration: none; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-header">
        <div class="icon-bg"><i class="fas fa-shield-alt"></i></div>
        <h1>Redefinir Senha</h1>
    </div>
    
    <?php include __DIR__ . '/components/alerts.php'; ?>

    <?php if ($error): ?>
        <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <div class="auth-footer">
            <a href="<?php echo BASE_URL; ?>forgot-password.php">Solicitar novo link</a>
        </div>
    <?php elseif ($user): ?>
        <p style="text-align:center; color: #64748b; margin-bottom: 20px;">Olá, <?php echo htmlspecialchars($user['name']); ?>. Por favor, insira sua nova senha.</p>
        <form method="POST" action="<?php echo BASE_URL; ?>controllers/auth_controller.php" class="auth-form">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="password">Nova Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" minlength="6" required>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Nova Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Salvar Nova Senha</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>