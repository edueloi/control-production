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
            
                // Acesso especial: admin@admin.com / Admin@1234
                $isSpecialAdmin = (
                    strtolower($email) === 'admin@admin.com' &&
                    $password === 'Admin@1234' &&
                    !$user // só libera se não existe no banco
                );
            
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
                $_SESSION['user_database'] = $user['database_name'] ?? null;
                
                // Atualizar last_login
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Registrar login no log
                $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], 'Login', 'Usuário fez login no sistema', $_SERVER['REMOTE_ADDR']]);
                
                setSuccessMessage('Login realizado com sucesso!');
                header('Location: ' . BASE_URL . 'views/dashboard.php');
                exit;
                } elseif ($isSpecialAdmin) {
                    // Libera acesso especial admin
                    $_SESSION['user_id'] = 0;
                    $_SESSION['user_name'] = 'Administrador';
                    $_SESSION['user_email'] = 'admin@admin.com';
                    $_SESSION['user_role'] = 'admin';
                    setSuccessMessage('Acesso especial de administrador liberado!');
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
            
            // Gerar nome do banco para o usuário
            $databaseName = 'userdb_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $email));
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, database_name) VALUES (?, ?, ?, ?, 'active', ?)");
            $stmt->execute([$name, $email, $hashedPassword, $role, $databaseName]);
            
            setSuccessMessage('Cadastro realizado com sucesso! Faça login para continuar.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
            break;
            
        case 'forgot_password':
            $email = sanitizeInput($_POST['email']);
            
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Gerar token seguro
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                
                // Definir tempo de expiração (1 hora)
                $expires = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
                $expires->add(new DateInterval('PT1H'));
                $expiresFormatted = $expires->format('Y-m-d H:i:s');
                
                // Salvar token no banco
                $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$tokenHash, $expiresFormatted, $user['id']]);
                
                // Simulação de envio de email
                $recoveryLink = BASE_URL . 'recover.php?token=' . $token;
                setSuccessMessage("Se um usuário com este e-mail existir, um link de recuperação foi 'enviado'. Link para teste: <a href='{$recoveryLink}'>{$recoveryLink}</a>");
            } else {
                // Mensagem genérica para não revelar se o e-mail existe
                setSuccessMessage("Se um usuário com este e-mail existir, um link de recuperação foi 'enviado'.");
            }
            
            header('Location: ../forgot-password.php');
            exit;
            break;
            
        case 'reset_password':
            $token = $_POST['token'] ?? null;
            $password = $_POST['password'] ?? null;
            $confirmPassword = $_POST['confirm_password'] ?? null;

            if (!$token || !$password || !$confirmPassword) {
                setErrorMessage('Todos os campos são obrigatórios.');
                header('Location: ' . BASE_URL . 'recover.php?token=' . urlencode($token));
                exit;
            }

            if ($password !== $confirmPassword) {
                setErrorMessage('As senhas não coincidem.');
                header('Location: ' . BASE_URL . 'recover.php?token=' . urlencode($token));
                exit;
            }

            if (strlen($password) < 6) {
                setErrorMessage('A senha deve ter pelo menos 6 caracteres.');
                header('Location: ' . BASE_URL . 'recover.php?token=' . urlencode($token));
                exit;
            }

            $tokenHash = hash('sha256', $token);
            $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > datetime('now', 'localtime')");
            $stmt->execute([$tokenHash]);
            $user = $stmt->fetch();

            if ($user) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $stmt->execute([$hashedPassword, $user['id']]);

                setSuccessMessage('Sua senha foi redefinida com sucesso! Você já pode fazer login.');
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            } else {
                setErrorMessage('Token inválido ou expirado. Por favor, solicite uma nova recuperação.');
                header('Location: ' . BASE_URL . 'forgot-password.php');
                exit;
            }
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
