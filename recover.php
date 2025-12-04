<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

// Processar formulário de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'send_code') {
        $email = sanitizeInput($_POST['email']);
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Gerar código de 6 dígitos
            $code = rand(100000, 999999);
            
            // Salvar código na sessão (em produção, use banco de dados)
            $_SESSION['reset_code'] = $code;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_time'] = time();
            
            // Em produção, enviar e-mail aqui
            // mail($email, "Código de Recuperação", "Seu código é: $code");
            
            setSuccessMessage("Código enviado! (Código de teste: $code)");
            $_SESSION['show_code_form'] = true;
        } else {
            setSuccessMessage('Se o e-mail estiver cadastrado, você receberá o código.');
        }
    }
    
    if ($action === 'verify_code') {
        $code = $_POST['code'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verificar se código é válido (máximo 10 minutos)
        if (isset($_SESSION['reset_code']) && isset($_SESSION['reset_time'])) {
            if (time() - $_SESSION['reset_time'] > 600) {
                setErrorMessage('Código expirado! Solicite um novo código.');
            } elseif ($code != $_SESSION['reset_code']) {
                setErrorMessage('Código inválido!');
            } elseif ($new_password !== $confirm_password) {
                setErrorMessage('As senhas não coincidem!');
            } elseif (strlen($new_password) < 6) {
                setErrorMessage('A senha deve ter pelo menos 6 caracteres!');
            } else {
                // Atualizar senha
                $db = Database::getInstance()->getConnection();
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashedPassword, $_SESSION['reset_email']]);
                
                // Limpar sessão
                unset($_SESSION['reset_code']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_time']);
                unset($_SESSION['show_code_form']);
                
                setSuccessMessage('Senha alterada com sucesso! Faça login com a nova senha.');
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            }
        } else {
            setErrorMessage('Sessão expirada! Solicite um novo código.');
        }
    }
}

$pageTitle = 'Recuperar Senha';
?>

<?php include __DIR__ . '/components/header.php'; ?>

<div class="main-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="icon-bg">
                <i class="fas fa-key"></i>
            </div>
            <h1>Recuperar Senha</h1>
            <p>Digite seu e-mail para receber o código de recuperação</p>
        </div>
        
        <?php include __DIR__ . '/components/alerts.php'; ?>
        
        <?php if (!isset($_SESSION['show_code_form'])): ?>
        <!-- Formulário para solicitar código -->
        <form method="POST" class="auth-form">
            <input type="hidden" name="action" value="send_code">
            
            <div class="form-group">
                <label for="email">E-mail</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                ENVIAR CÓDIGO
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
        <?php else: ?>
        <!-- Formulário para verificar código e trocar senha -->
        <form method="POST" class="auth-form">
            <input type="hidden" name="action" value="verify_code">
            
            <div class="form-group">
                <label for="code">Código de Verificação</label>
                <div class="input-wrapper">
                    <i class="fas fa-shield-alt"></i>
                    <input type="text" id="code" name="code" placeholder="123456" required autofocus maxlength="6" pattern="[0-9]{6}">
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">Nova Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password" placeholder="Mínimo 6 caracteres" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordField('new_password')" style="cursor: pointer; position: absolute; right: 15px; color: #94a3b8;"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Digite novamente" required>
                    <i class="fas fa-eye toggle-password-confirm" onclick="togglePasswordField('confirm_password')" style="cursor: pointer; position: absolute; right: 15px; color: #94a3b8;"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                ALTERAR SENHA
                <i class="fas fa-check"></i>
            </button>
        </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            Lembrou sua senha? <a href="<?php echo BASE_URL; ?>login.php">Fazer Login</a>
        </div>
    </div>
</div>

<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html, body {
        height: 100%;
        overflow: hidden;
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
        transition: color 0.3s;
    }

    .toggle-password:hover,
    .toggle-password-confirm:hover {
        color: #1e40af !important;
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
function togglePasswordField(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = fieldId === 'new_password' ? 
        document.querySelector('.toggle-password') : 
        document.querySelector('.toggle-password-confirm');
    
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

<?php include __DIR__ . '/components/footer.php'; ?>
