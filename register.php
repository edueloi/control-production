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

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-user-plus"></i>
                <h1>Criar Conta</h1>
                <p>Preencha os dados para se cadastrar</p>
            </div>
            
            <?php include __DIR__ . '/components/alerts.php'; ?>
            
            <form method="POST" action="<?php echo BASE_URL; ?>controllers/auth_controller.php" class="auth-form" id="registerForm">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i>
                        Nome Completo
                    </label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        E-mail
                    </label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Senha
                    </label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Mínimo de 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirmar Senha
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i>
                    Cadastrar
                </button>
            </form>
            
            <div class="auth-footer">
                <a href="<?php echo BASE_URL; ?>login.php">Já tenho uma conta</a>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .auth-wrapper {
        width: 100%;
        padding: 20px;
    }
    
    .auth-container {
        max-width: 450px;
        margin: 0 auto;
    }
    
    .auth-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 40px;
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .auth-header i {
        font-size: 48px;
        color: #667eea;
        margin-bottom: 15px;
    }
    
    .auth-header h1 {
        font-size: 24px;
        color: #333;
        margin-bottom: 10px;
    }
    
    .auth-header p {
        color: #666;
        font-size: 14px;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        font-size: 14px;
    }
    
    .auth-footer a {
        color: #667eea;
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .auth-footer a:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    
    small {
        color: #999;
        font-size: 12px;
    }
</style>

<script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('As senhas não coincidem!');
            return false;
        }
    });
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
