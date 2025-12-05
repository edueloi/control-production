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
        $avatarPath = $user['avatar']; // Pega o caminho do avatar atual

        // Verifica se foi solicitado para remover a imagem
        $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === 'true';

        // Se um novo arquivo de avatar foi enviado
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Deleta o avatar antigo, se existir
            if ($avatarPath && file_exists(__DIR__ . '/../../' . $avatarPath)) {
                unlink(__DIR__ . '/../../' . $avatarPath);
            }

            // Define o diretório de upload
            $uploadDir = __DIR__ . '/../../uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Cria um nome de arquivo único
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = $userId . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            // Move o arquivo para o diretório de uploads
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                $avatarPath = 'uploads/avatars/' . $filename;
            }
        } elseif ($removeImage) {
            // Se a remoção foi solicitada e nenhum novo arquivo foi enviado
            if ($avatarPath && file_exists(__DIR__ . '/../../' . $avatarPath)) {
                unlink(__DIR__ . '/../../' . $avatarPath);
            }
            $avatarPath = null; // Define o caminho como nulo no banco de dados
        }

        // Atualiza os dados no banco de dados
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, avatar = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $phone, $avatarPath, $userId]);
        
        // Atualiza as informações da sessão
        $_SESSION['user_name'] = $name;
        $_SESSION['user_avatar'] = $avatarPath;
        $_SESSION['success_message'] = 'Perfil atualizado com sucesso!';
        
        header('Location: ' . BASE_URL . 'views/profile/');
        exit;
    }
    
    if ($_POST['action'] === 'change_password') {
        // ... (código de alteração de senha permanece o mesmo)
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
                        <form id="profileForm" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <!-- Uploader de Avatar -->
                            <div class="form-group" style="display: flex; justify-content: center;">
                                <div id="image-uploader" class="image-uploader">
                                    <div class="uploader-instructions">
                                        <i class="fas fa-camera"></i>
                                        <p style="font-size: 12px;">Avatar</p>
                                    </div>
                                    <div class="image-preview" style="display: none;">
                                        <img id="preview-img" src="#" >
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
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Telefone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
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
                            <!-- ... (formulário de senha) ... -->
                        </form>
                    </div>
                </div>

                <!-- Coluna da Direita: Cartão de Perfil e Atividades -->
                <div style="flex: 1;">
                    <!-- ... (cartão de perfil e atividades) ... -->
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.image-uploader {
    position: relative;
    border: 2px dashed #ccc;
    border-radius: 50%;
    width: 150px;
    height: 150px;
    cursor: pointer;
    background: #f8f9fa;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.image-uploader:hover, .image-uploader.dragover {
    background: #e9ecef;
    border-color: #3b82f6;
}
.uploader-instructions { color: #6c757d; text-align: center; }
.uploader-instructions i { font-size: 40px; margin-bottom: 10px; display: block; }
.image-preview { position: relative; width: 100%; height: 100%; }
.image-preview img { width: 100%; height: 100%; object-fit: cover; }
.image-actions {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; align-items: center;
    justify-content: center; opacity: 0; transition: opacity 0.2s;
}
.image-preview:hover .image-actions { opacity: 1; }
.action-btn {
    background: white; color: #333; border: none; border-radius: 50%;
    width: 35px; height: 35px; cursor: pointer; display: flex;
    align-items: center; justify-content: center; font-size: 16px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploader = document.getElementById('image-uploader');
    const fileInput = document.getElementById('image');
    const instructions = uploader.querySelector('.uploader-instructions');
    const preview = uploader.querySelector('.image-preview');
    const previewImg = document.getElementById('preview-img');
    const removeBtn = document.getElementById('remove-image-btn');
    const userAvatar = "<?php echo $user['avatar'] ? BASE_URL . $user['avatar'] : ''; ?>";

    function setInitialAvatar() {
        if (userAvatar) {
            previewImg.src = userAvatar;
            instructions.style.display = 'none';
            preview.style.display = 'block';
        }
    }

    uploader.addEventListener('click', (e) => {
        if (!e.target.closest('.action-btn')) fileInput.click();
    });

    uploader.addEventListener('dragover', (e) => { e.preventDefault(); uploader.classList.add('dragover'); });
    uploader.addEventListener('dragleave', () => uploader.classList.remove('dragover'));
    uploader.addEventListener('drop', (e) => {
        e.preventDefault();
        uploader.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFile(fileInput.files[0]);
        }
    });

    document.addEventListener('paste', (e) => {
        const items = e.clipboardData.items;
        for (const item of items) {
            if (item.type.includes('image')) {
                const file = item.getAsFile();
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                handleFile(file);
                break;
            }
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) handleFile(e.target.files[0]);
    });

    function handleFile(file) {
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                instructions.style.display = 'none';
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    removeBtn.addEventListener('click', () => {
        fileInput.value = '';
        instructions.style.display = 'block';
        preview.style.display = 'none';
        
        const form = uploader.closest('form');
        let removeInput = form.querySelector('input[name=remove_image]');
        if (!removeInput) {
            removeInput = document.createElement('input');
            removeInput.type = 'hidden';
            removeInput.name = 'remove_image';
            form.appendChild(removeInput);
        }
        removeInput.value = 'true';
    });

    setInitialAvatar();
});
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
