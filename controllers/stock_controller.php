<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'adjust':
            $productId = intval($_POST['product_id']);
            $type = sanitizeInput($_POST['type']);
            $quantity = floatval($_POST['quantity']);
            $notes = sanitizeInput($_POST['notes'] ?? '');
            
            $db->beginTransaction();
            
            $userId = $_SESSION['user_id'];
            // Atualizar estoque apenas do produto do usuário
            $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ? AND user_id = ?");
            if ($type === 'entrada') {
                $stmt->execute([$quantity, $productId, $userId]);
            } elseif ($type === 'saida') {
                $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $productId, $userId]);
            } else { // ajuste
                $stmt = $db->prepare("UPDATE products SET stock = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $productId, $userId]);
            }

            // Registrar movimentação
            $stmt = $db->prepare("
                INSERT INTO stock_movements (user_id, product_id, type, quantity, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $productId, $type, $quantity, $notes]);
            
            $db->commit();
            
            setSuccessMessage('Movimentação registrada com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
