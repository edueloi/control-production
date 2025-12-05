<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $name = sanitizeInput($_POST['name']);
            $type = sanitizeInput($_POST['type']);
            $cpf = $type === 'physical' ? sanitizeInput($_POST['cpf'] ?? '') : null;
            $cnpj = $type === 'legal' ? sanitizeInput($_POST['cnpj'] ?? '') : null;
            $companyName = $type === 'legal' ? sanitizeInput($_POST['company_name'] ?? '') : null;
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $whatsapp = sanitizeInput($_POST['whatsapp'] ?? '');
            $address = sanitizeInput($_POST['address']);
            
            // Validar CPF/CNPJ
            if ($type === 'physical' && !empty($cpf) && !validateCPF($cpf)) {
                echo json_encode(['success' => false, 'message' => 'CPF inválido']);
                exit;
            }
            
            if ($type === 'legal' && !empty($cnpj) && !validateCNPJ($cnpj)) {
                echo json_encode(['success' => false, 'message' => 'CNPJ inválido']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO clients (user_id, name, type, cpf, cnpj, company_name, email, phone, whatsapp, address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([getCurrentUserId(), $name, $type, $cpf, $cnpj, $companyName, $email, $phone, $whatsapp, $address]);
            
            setSuccessMessage('Cliente cadastrado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'update':
            $id = intval($_POST['id']);

            if (!checkOwnership($db, 'clients', $id)) {
                echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
                exit;
            }

            $name = sanitizeInput($_POST['name']);
            $type = sanitizeInput($_POST['type']);
            $cpf = $type === 'physical' ? sanitizeInput($_POST['cpf'] ?? '') : null;
            $cnpj = $type === 'legal' ? sanitizeInput($_POST['cnpj'] ?? '') : null;
            $companyName = $type === 'legal' ? sanitizeInput($_POST['company_name'] ?? '') : null;
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $whatsapp = sanitizeInput($_POST['whatsapp'] ?? '');
            $address = sanitizeInput($_POST['address']);
            
            $stmt = $db->prepare("
                UPDATE clients 
                SET name = ?, type = ?, cpf = ?, cnpj = ?, company_name = ?, 
                    email = ?, phone = ?, whatsapp = ?, address = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$name, $type, $cpf, $cnpj, $companyName, $email, $phone, $whatsapp, $address, $id, getCurrentUserId()]);
            
            setSuccessMessage('Cliente atualizado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            if (!checkOwnership($db, 'clients', $id)) {
                echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM clients WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, getCurrentUserId()]);
            
            setSuccessMessage('Cliente excluído com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'get':
            $filter = getUserFilter();
            $stmt = $db->prepare("SELECT * FROM clients WHERE {$filter}");
            $stmt->execute();
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $clients]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
