<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

$pageTitle = 'Cadastro';
?>

<?php include __DIR__ . '/components/header.php'; ?>

<div class="auth-body">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon-bg">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Criar Conta</h1>
            <p>Preencha os dados para se cadastrar</p>
        </div>

        <?php include __DIR__ . '/components/alerts.php'; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>controllers/auth_controller.php" class="auth-form" id="registerForm">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label for="name">Nome Completo</label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" id="name" name="name" placeholder="Seu nome completo" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-envelope icon-left"></i>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" id="password" name="password" placeholder="Crie uma senha forte" required minlength="6">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                </div>
                <small style="color: #64748b; font-size: 12px; margin-top: 5px; display: block;">Mínimo de 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Senha</label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repita sua senha" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                </div>
            </div>

            <button type="submit" class="btn-auth">
                CADASTRAR
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="auth-footer">
            Já tem uma conta? <a href="<?php echo BASE_URL; ?>login.php">Faça login aqui</a>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, icon) {
    const passwordInput = document.getElementById(inputId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('As senhas não coincidem!');
    }
});
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
