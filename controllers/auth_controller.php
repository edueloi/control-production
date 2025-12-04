<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Verificar se usuário está ativo
                if (isset($user['status']) && $user['status'] === 'inactive') {
                    setErrorMessage('Usuário inativo! Entre em contato com o administrador.');
                    header('Location: ' . BASE_URL . 'login.php');
                    exit;
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                
                // Atualizar last_login
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Registrar login no log
                $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], 'Login', 'Usuário fez login no sistema', $_SERVER['REMOTE_ADDR']]);
                
                setSuccessMessage('Login realizado com sucesso!');
                header('Location: ' . BASE_URL . 'views/dashboard.php');
                exit;
            } else {
                setErrorMessage('E-mail ou senha incorretos!');
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            }
            break;
            
        case 'register':
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($password !== $confirmPassword) {
                setErrorMessage('As senhas não coincidem!');
                header('Location: ' . BASE_URL . 'register.php');
                exit;
            }
            
            if (strlen($password) < 6) {
                setErrorMessage('A senha deve ter pelo menos 6 caracteres!');
                header('Location: ' . BASE_URL . 'register.php');
                exit;
            }
            
            // Verificar se e-mail já existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                setErrorMessage('Este e-mail já está cadastrado!');
                header('Location: ' . BASE_URL . 'register.php');
                exit;
            }
            
            // Criar usuário
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Primeiro usuário é automaticamente admin
            $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $role = ($count == 0) ? 'admin' : 'user';
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $email, $hashedPassword, $role]);
            
            setSuccessMessage('Cadastro realizado com sucesso! Faça login para continuar.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
            break;
            
        case 'recover':
            $email = sanitizeInput($_POST['email']);
            
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                // Aqui você implementaria o envio de e-mail
                // Por enquanto, vamos apenas mostrar uma mensagem de sucesso
                setSuccessMessage('Instruções de recuperação foram enviadas para seu e-mail!');
            } else {
                setSuccessMessage('Se o e-mail estiver cadastrado, você receberá as instruções.');
            }
            
            header('Location: ../login.php');
            exit;
            break;
            
        default:
            setErrorMessage('Ação inválida!');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
    }
} catch (Exception $e) {
    setErrorMessage('Erro: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
?>
