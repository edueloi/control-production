<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

// Determina a ação e os dados
$action = $inputData['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
$data = $inputData ?? $_POST;
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
            $ingredients = $data['ingredients'] ?? [];
            
            if (is_string($ingredients)) {
                $ingredients = json_decode($ingredients, true);
            }
            
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
                
                // Nota: Assumindo conversão direta ou que já vem calculado
                $quantityNeeded = $ing['quantity'];
                
                // Se quiseres usar a função de conversão, seria algo assim:
                // $quantityBase = convertToBaseUnit($ing['quantity'], $ing['unit'], $ingProduct['unit']);
                // Mas para simplificar, vamos usar a quantidade direta por enquanto
                
                if ($ingProduct['stock'] < $quantityNeeded) {
                    $insufficientStock[] = [
                        'name' => $ingProduct['description'],
                        'needed' => $quantityNeeded,
                        'available' => $ingProduct['stock'],
                        'unit' => $ingProduct['unit']
                    ];
                }
                
                // O custo deve ser calculado proporcionalmente
                // Se o custo do produto é por UN, é direto. Se é por KG, tem de ver a quantidade.
                // Simplificação: Custo unitário * Quantidade
                $cost = $ingProduct['cost'] * $quantityNeeded;
                $totalCost += $cost;
                
                $ingredientsDetails[] = [
                    'id' => $ingProduct['id'],
                    'name' => $ingProduct['description'],
                    'quantity' => $quantityNeeded,
                    'unit' => $ingProduct['unit'],
                    'cost' => $cost
                ];
            }
            
            $unitCost = ($batchSize > 0) ? $totalCost / $batchSize : 0;
            $profitMargin = 0;
            if (isset($product['price']) && $product['price'] > 0) {
                $profitMargin = (($product['price'] - $unitCost) / $product['price']) * 100;
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'totalCost' => $totalCost,
                    'unitCost' => $unitCost,
                    'profitMargin' => $profitMargin,
                    'profitValue' => ($product['price'] ?? 0) - $unitCost,
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
            $ingredients = $data['ingredients'] ?? [];

            if (is_string($ingredients)) {
                $ingredients = json_decode($ingredients, true);
            }
            
            // Validações
            if (!$productId || !$batchSize || empty($ingredients)) {
                throw new Exception('Dados incompletos: produto, lote ou ingredientes faltando!');
            }
            
            $db->beginTransaction();
            
            // 1. Inserir produção
            $stmt = $db->prepare("
                INSERT INTO productions (user_id, product_id, batch_size, total_cost, unit_cost, profit_margin)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            // Se userId for null (sessão perdida), usa 1 como fallback ou lança erro
            $userIdToUse = $userId ?: 1; 
            $stmt->execute([$userIdToUse, $productId, $batchSize, $totalCost, $unitCost, $profitMargin]);
            $productionId = $db->lastInsertId();
            
            // 2. Inserir ingredientes e Movimentar Estoque (Saída)
            $stmtInsertIng = $db->prepare("
                INSERT INTO production_ingredients (production_id, product_id, quantity, cost)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmtStockOut = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            
            // CORREÇÃO: Usar 'notes' e 'reference_type'
            $stmtMovOut = $db->prepare("
                INSERT INTO stock_movements (product_id, type, quantity, reference_id, reference_type, notes)
                VALUES (?, 'saida', ?, ?, 'production', 'Ingrediente p/ Produção')
            ");
            
            foreach ($ingredients as $ingredient) {
                // Tenta pegar o ID de várias formas possíveis dependendo do JSON recebido
                $ingId = $ingredient['product_id'] ?? $ingredient['id'] ?? null;
                $ingQty = $ingredient['quantityBase'] ?? $ingredient['quantity'] ?? 0;
                $ingCost = $ingredient['cost'] ?? 0;
                
                if (!$ingId || !$ingQty) {
                    continue; // Pula se dados inválidos
                }
                
                // Salva ingrediente
                $stmtInsertIng->execute([$productionId, $ingId, $ingQty, $ingCost]);
                
                // Baixa no estoque
                $stmtStockOut->execute([$ingQty, $ingId]);
                
                // Registo de movimento (CORRIGIDO)
                $stmtMovOut->execute([$ingId, $ingQty, $productionId]);
            }
            
            // 3. Adicionar produto acabado ao estoque (Entrada)
            $stmtStockIn = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmtStockIn->execute([$batchSize, $productId]);
            
            // Registo de movimento (CORRIGIDO)
            $stmtMovIn = $db->prepare("
                INSERT INTO stock_movements (product_id, type, quantity, reference_id, reference_type, notes)
                VALUES (?, 'entrada', ?, ?, 'production', 'Produção Concluída')
            ");
            $stmtMovIn->execute([$productId, $batchSize, $productionId]);
            
            // Log de atividade (se a função existir)
            if (function_exists('logActivity')) {
                logActivity($db, 'Production', "Produção #{$productionId} criada", 'production', $productionId);
            }
            
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
    
    // Log do erro para debug no servidor
    error_log("Erro Produção: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao processar: ' . $e->getMessage()
    ]);
}
?>