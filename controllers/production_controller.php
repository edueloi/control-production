<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $productId = intval($data['product_id']);
            $batchSize = intval($data['batch_size']);
            $totalCost = floatval($data['total_cost']);
            $unitCost = floatval($data['unit_cost']);
            $profitMargin = floatval($data['profit_margin']);
            $ingredients = $data['ingredients'];
            
            $db->beginTransaction();
            
            // Inserir produção
            $stmt = $db->prepare("
                INSERT INTO productions (product_id, batch_size, total_cost, unit_cost, profit_margin)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $batchSize, $totalCost, $unitCost, $profitMargin]);
            $productionId = $db->lastInsertId();
            
            // Inserir ingredientes
            $stmt = $db->prepare("
                INSERT INTO production_ingredients (production_id, product_id, quantity, cost)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($ingredients as $ingredient) {
                $stmt->execute([
                    $productionId,
                    $ingredient['product_id'],
                    $ingredient['quantity'],
                    $ingredient['cost']
                ]);
                
                // Atualizar estoque (dar baixa nos ingredientes)
                $stmtStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmtStock->execute([$ingredient['quantity'], $ingredient['product_id']]);
                
                // Registrar movimentação
                $stmtMov = $db->prepare("
                    INSERT INTO stock_movements (product_id, type, quantity, reference_id, reference_type, notes)
                    VALUES (?, 'saida', ?, ?, 'production', 'Usado na produção')
                ");
                $stmtMov->execute([$ingredient['product_id'], $ingredient['quantity'], $productionId]);
            }
            
            // Adicionar produto acabado ao estoque
            $stmtStock = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmtStock->execute([$batchSize, $productId]);
            
            // Registrar movimentação
            $stmtMov = $db->prepare("
                INSERT INTO stock_movements (product_id, type, quantity, reference_id, reference_type, notes)
                VALUES (?, 'entrada', ?, ?, 'production', 'Produção concluída')
            ");
            $stmtMov->execute([$productId, $batchSize, $productionId]);
            
            $db->commit();
            
            setSuccessMessage('Produção registrada com sucesso!');
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
