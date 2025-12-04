<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// Verificar se é admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado!']);
    exit;
}

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $name = trim($input['name']);
            $email = trim($input['email']);
            $phone = trim($input['phone'] ?? '');
            $password = $input['password'];
            $role = $input['role'];
            $status = $input['status'];
            
            // Verificar se e-mail já existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado!']);
                exit;
            }
            
            // Criar usuário
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hashedPassword, $role, $status]);
            
            // Registrar log
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'Criar Usuário', "Criou usuário: $name ($email)", $_SERVER['REMOTE_ADDR']]);
            
            echo json_encode(['success' => true, 'message' => 'Usuário cadastrado com sucesso!']);
            break;
            
        case 'update':
            $id = $input['id'];
            $name = trim($input['name']);
            $email = trim($input['email']);
            $phone = trim($input['phone'] ?? '');
            $role = $input['role'];
            $status = $input['status'];
            
            // Verificar se e-mail já existe para outro usuário
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado!']);
                exit;
            }
            
            // Atualizar usuário
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $role, $status, $id]);
            
            // Atualizar senha se fornecida
            if (!empty($input['password'])) {
                $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $id]);
            }
            
            // Registrar log
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'Atualizar Usuário', "Atualizou usuário ID: $id", $_SERVER['REMOTE_ADDR']]);
            
            echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            break;
            
        case 'toggle_status':
            $id = $input['id'];
            $status = $input['status'];
            
            // Não permitir desativar o próprio usuário
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Você não pode desativar seu próprio usuário!']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            // Registrar log
            $action_desc = $status === 'active' ? 'Ativou' : 'Desativou';
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'Alterar Status', "$action_desc usuário ID: $id", $_SERVER['REMOTE_ADDR']]);
            
            echo json_encode(['success' => true, 'message' => 'Status alterado com sucesso!']);
            break;
            
        case 'delete':
            $id = $input['id'];
            
            // Não permitir excluir o próprio usuário
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Você não pode excluir seu próprio usuário!']);
                exit;
            }
            
            // Verificar se é o último admin
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $userRole = $stmt->fetchColumn();
            
            if ($userRole === 'admin' && $adminCount <= 1) {
                echo json_encode(['success' => false, 'message' => 'Não é possível excluir o último administrador ativo!']);
                exit;
            }
            
            // Excluir usuário
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            // Registrar log
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'Excluir Usuário', "Excluiu usuário ID: $id", $_SERVER['REMOTE_ADDR']]);
            
            echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida!']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>
