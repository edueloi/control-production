<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $clientId = $data['client_id'] ?? null;
            $subtotal = floatval($data['subtotal']);
            $discount = floatval($data['discount']);
            $discountType = $data['discount_type'];
            $total = floatval($data['total']);
            $paymentMethod = $data['payment_method'];
            $items = $data['items'];
            
            $db->beginTransaction();
            
            // Inserir venda
            $stmt = $db->prepare("
                INSERT INTO sales (client_id, subtotal, discount, discount_type, total, payment_method)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$clientId, $subtotal, $discount, $discountType, $total, $paymentMethod]);
            $saleId = $db->lastInsertId();
            
            // Inserir itens e atualizar estoque
            $stmtItem = $db->prepare("
                INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($items as $item) {
                $itemSubtotal = $item['price'] * $item['quantity'];
                $stmtItem->execute([
                    $saleId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $itemSubtotal
                ]);
                
                // Atualizar estoque
                $stmtStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmtStock->execute([$item['quantity'], $item['product_id']]);
                
                // Registrar movimentação
                $stmtMov = $db->prepare("
                    INSERT INTO stock_movements (product_id, type, quantity, reference_id, reference_type, notes)
                    VALUES (?, 'saida', ?, ?, 'sale', 'Venda realizada')
                ");
                $stmtMov->execute([$item['product_id'], $item['quantity'], $saleId]);
            }
            
            $db->commit();
            
            setSuccessMessage('Venda realizada com sucesso!');
            echo json_encode(['success' => true, 'sale_id' => $saleId]);
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
