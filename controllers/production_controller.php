<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
$userId = getCurrentUserId();

/**
 * Converte quantidade para unidade base
 */
function convertToBaseUnit($quantity, $fromUnit, $toUnit) {
    if ($fromUnit === $toUnit) return $quantity;
    if ($fromUnit === 'g' && $toUnit === 'kg') return $quantity / 1000;
    if ($fromUnit === 'kg' && $toUnit === 'g') return $quantity * 1000;
    if ($fromUnit === 'ml' && $toUnit === 'l') return $quantity / 1000;
    if ($fromUnit === 'l' && $toUnit === 'ml') return $quantity * 1000;
    return $quantity;
}

try {
    switch ($action) {
        case 'calculate':
            // Calcular custos
            $productId = $data['product_id'] ?? $_POST['product_id'];
            $batchSize = (int)($data['batch_size'] ?? $_POST['batch_size']);
            $ingredients = $data['ingredients'] ?? json_decode($_POST['ingredients'] ?? '[]', true);
            
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalCost = 0;
            $insufficientStock = [];
            $ingredientsDetails = [];
            
            foreach ($ingredients as $ing) {
                $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$ing['product_id']]);
                $ingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$ingProduct) continue;
                
                $quantityBase = convertToBaseUnit($ing['quantity'], $ing['unit'], $ingProduct['unit']);
                
                if ($ingProduct['stock'] < $quantityBase) {
                    $insufficientStock[] = [
                        'name' => $ingProduct['description'],
                        'needed' => $quantityBase,
                        'available' => $ingProduct['stock'],
                        'unit' => $ingProduct['unit']
                    ];
                }
                
                $cost = $ingProduct['cost'] * $quantityBase;
                $totalCost += $cost;
                
                $ingredientsDetails[] = [
                    'id' => $ingProduct['id'],
                    'name' => $ingProduct['description'],
                    'quantity' => $ing['quantity'],
                    'unit' => $ing['unit'],
                    'quantityBase' => $quantityBase,
                    'unitBase' => $ingProduct['unit'],
                    'cost' => $cost
                ];
            }
            
            $unitCost = $totalCost / $batchSize;
            $profitMargin = (($product['price'] - $unitCost) / $product['price']) * 100;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'totalCost' => $totalCost,
                    'unitCost' => $unitCost,
                    'profitMargin' => $profitMargin,
                    'profitValue' => $product['price'] - $unitCost,
                    'ingredientsDetails' => $ingredientsDetails,
                    'insufficientStock' => $insufficientStock,
                    'canProduce' => empty($insufficientStock)
                ]
            ]);
            break;
            
        case 'create':
        case 'save':
            $productId = intval($data['product_id'] ?? $_POST['product_id']);
            $batchSize = intval($data['batch_size'] ?? $_POST['batch_size']);
            $totalCost = floatval($data['total_cost'] ?? $_POST['total_cost']);
            $unitCost = floatval($data['unit_cost'] ?? $_POST['unit_cost']);
            $profitMargin = floatval($data['profit_margin'] ?? $_POST['profit_margin']);
            $ingredients = $data['ingredients'] ?? json_decode($_POST['ingredients'] ?? '[]', true);
            
            $db->beginTransaction();
            
            // Inserir produção com user_id
            $stmt = $db->prepare("
                INSERT INTO productions (user_id, product_id, batch_size, total_cost, unit_cost, profit_margin)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $productId, $batchSize, $totalCost, $unitCost, $profitMargin]);
            $productionId = $db->lastInsertId();
            
            // Inserir ingredientes
            $stmt = $db->prepare("
                INSERT INTO production_ingredients (production_id, product_id, quantity, cost)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($ingredients as $ingredient) {
                $ingId = $ingredient['product_id'] ?? $ingredient['id'];
                $ingQty = $ingredient['quantityBase'] ?? $ingredient['quantity'];
                $ingCost = $ingredient['cost'];
                
                $stmt->execute([
                    $productionId,
                    $ingId,
                    $ingQty,
                    $ingCost
                ]);
                
                // Atualizar estoque (dar baixa nos ingredientes)
                $stmtStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmtStock->execute([$ingQty, $ingId]);
                
                // Registrar movimentação de SAÍDA
                $stmtMov = $db->prepare("
                    INSERT INTO stock_movements (product_id, type, quantity, reason, reference_id)
                    VALUES (?, 'SAÍDA', ?, 'Produção', ?)
                ");
                $stmtMov->execute([$ingId, $ingQty, $productionId]);
            }
            
            // Adicionar produto acabado ao estoque
            $stmtStock = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmtStock->execute([$batchSize, $productId]);
            
            // Registrar movimentação de ENTRADA
            $stmtMov = $db->prepare("
                INSERT INTO stock_movements (product_id, type, quantity, reason, reference_id)
                VALUES (?, 'ENTRADA', ?, 'Produção', ?)
            ");
            $stmtMov->execute([$productId, $batchSize, $productionId]);
            
            // Registrar activity log
            logActivity($db, 'Production', "Produção #{$productionId} criada", 'production', $productionId);
            
            $db->commit();
            
            echo json_encode(['success' => true, 'message' => 'Produção salva com sucesso!', 'productionId' => $productionId]);
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
