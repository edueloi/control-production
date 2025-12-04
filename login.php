<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

$pageTitle = 'Login';
?>

<?php include __DIR__ . '/components/header.php'; ?>

<div class="auth-body">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon-bg">
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
                <div class="auth-input-wrapper">
                    <i class="fas fa-envelope icon-left"></i>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    
                    <input type="password" id="password" name="password" placeholder="Sua senha secreta" required>
                    
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <div class="auth-actions">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="checkmark"></span>
                    Lembrar-me
                </label>
                <a href="<?php echo BASE_URL; ?>forgot-password.php" style="color: #4f46e5; font-weight: 500; text-decoration: none;">Esqueceu a senha?</a>
            </div>
            
            <button type="submit" class="btn-auth">
                ENTRAR
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            Não tem uma conta? <a href="<?php echo BASE_URL; ?>register.php">Registe-se aqui</a>
        </div>
    </div>
</div>

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

<?php include __DIR__ . '/components/footer.php'; ?>
