<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Meu Perfil';
$db = Database::getInstance()->getConnection();

// Buscar dados do usuário logado
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $phone, $userId]);
        
        $_SESSION['success'] = 'Perfil atualizado com sucesso!';
        header('Location: ' . BASE_URL . 'views/profile/');
        exit;
    }
    
    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$hashed, $userId]);
                
                $_SESSION['success'] = 'Senha alterada com sucesso!';
                header('Location: ' . BASE_URL . 'views/profile/');
                exit;
            } else {
                $_SESSION['error'] = 'As senhas não coincidem!';
            }
        } else {
            $_SESSION['error'] = 'Senha atual incorreta!';
        }
    }
}

// Buscar últimas atividades
$activities = $db->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$activities->execute([$userId]);
$activityList = $activities->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-user-circle"></i> Meu Perfil</h1>
                <p>Gerencie suas informações pessoais e configurações de conta</p>
            </div>

            <div class="form-row">
                <!-- Coluna da Esquerda: Formulários -->
                <div style="flex: 2;">
                    <!-- Informações do Perfil -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-edit"></i> Informações Pessoais</h3>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <!-- Uploader de Avatar -->
                            <div class="form-group" style="display: flex; justify-content: center;">
                                <div id="image-uploader" class="image-uploader" style="width: 150px; height: 150px; border-radius: 50%;">
                                    <div class="uploader-instructions">
                                        <i class="fas fa-camera"></i>
                                        <p style="font-size: 12px;">Avatar</p>
                                    </div>
                                    <div class="image-preview" style="display: none;">
                                        <img id="preview-img" src="#" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                        <div class="image-actions">
                                            <button type="button" id="remove-image-btn" class="action-btn" title="Remover imagem"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <input type="file" id="image" name="avatar" accept="image/*" style="display: none;">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nome Completo *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> E-mail</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small style="color: var(--text-muted);">O e-mail não pode ser alterado</small>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Telefone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="(00) 00000-0000">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </form>
                    </div>

                    <!-- Alterar Senha -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lock"></i> Alterar Senha</h3>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> Senha Atual *</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Nova Senha *</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Confirmar Nova Senha *</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-shield-alt"></i> Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Coluna da Direita: Cartão de Perfil e Atividades -->
                <div style="flex: 1;">
                    <div class="card" style="text-align: center;">
                        <h2 style="font-size: 24px; margin-bottom: var(--spacing-xs); color: var(--text-primary);">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </h2>
                        <p style="color: var(--text-muted); margin-bottom: var(--spacing-lg);">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                            <div style="padding: var(--spacing-md); background: var(--bg-secondary); border-radius: var(--radius-md);">
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: var(--spacing-xs);">Função</div>
                                <div style="font-weight: 700; color: var(--primary-color); text-transform: uppercase;">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </div>
                            </div>
                            <div style="padding: var(--spacing-md); background: var(--bg-secondary); border-radius: var(--radius-md);">
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: var(--spacing-xs);">Status</div>
                                <div style="font-weight: 700; color: var(--success-color); text-transform: uppercase;">
                                    <?php echo htmlspecialchars($user['status']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Atividades Recentes</h3>
                        </div>
                        <?php if (count($activityList) > 0): ?>
                            <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                                <?php foreach ($activityList as $activity): ?>
                                    <div style="padding: var(--spacing-sm); background: var(--bg-secondary); border-radius: var(--radius-sm); border-left: 3px solid var(--primary-color);">
                                        <div style="font-size: 13px; font-weight: 600; margin-bottom: 4px;">
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-muted); text-align: center; padding: var(--spacing-lg);">
                                Nenhuma atividade recente
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../../components/footer.php'; ?>
