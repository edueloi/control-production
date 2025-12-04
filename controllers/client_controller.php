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
                INSERT INTO clients (name, type, cpf, cnpj, company_name, email, phone, whatsapp, address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $type, $cpf, $cnpj, $companyName, $email, $phone, $whatsapp, $address]);
            
            setSuccessMessage('Cliente cadastrado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'update':
            $id = intval($_POST['id']);
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
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $cpf, $cnpj, $companyName, $email, $phone, $whatsapp, $address, $id]);
            
            setSuccessMessage('Cliente atualizado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            
            setSuccessMessage('Cliente excluído com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
