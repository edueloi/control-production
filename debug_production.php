<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    echo "Faça login primeiro!";
    exit;
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Produção</title><link rel='icon' type='image/png' href='images/icon-seictech.png'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
.success { background: #0e6c30; padding: 10px; margin: 10px 0; border-radius: 4px; }
.error { background: #8e1c20; padding: 10px; margin: 10px 0; border-radius: 4px; }
.info { background: #1e3a5f; padding: 10px; margin: 10px 0; border-radius: 4px; }
pre { background: #2d2d2d; padding: 15px; border-radius: 4px; overflow-x: auto; }
h2 { color: #4ec9b0; }
</style></head><body>";

echo "<h1>🔍 Debug do Sistema de Produção</h1>";

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// 1. Verificar usuário
echo "<h2>1. Usuário Logado</h2>";
echo "<div class='info'>";
echo "ID: " . $userId . "<br>";
echo "Nome: " . ($_SESSION['user_name'] ?? 'N/A') . "<br>";
echo "Email: " . ($_SESSION['user_email'] ?? 'N/A') . "<br>";
echo "Role: " . ($_SESSION['user_role'] ?? 'N/A');
echo "</div>";

// 2. Verificar produtos
echo "<h2>2. Produtos Disponíveis</h2>";
$filter = getUserFilter();
$products = $db->query("SELECT * FROM products WHERE $filter")->fetchAll(PDO::FETCH_ASSOC);
echo "<div class='info'>Total de produtos: " . count($products) . "</div>";

$finished = array_filter($products, fn($p) => $p['type'] === 'finished');
$supplies = array_filter($products, fn($p) => $p['type'] === 'supply');

echo "<div class='info'>";
echo "<strong>Produtos Acabados:</strong> " . count($finished) . "<br>";
echo "<strong>Insumos:</strong> " . count($supplies);
echo "</div>";

// 3. Testar inserção de produção
echo "<h2>3. Teste de Produção</h2>";

if (!empty($finished) && !empty($supplies)) {
    $productFinished = array_values($finished)[0];
    $productSupply = array_values($supplies)[0];
    
    echo "<div class='info'>";
    echo "<strong>Produto Final:</strong> {$productFinished['description']} (ID: {$productFinished['id']})<br>";
    echo "<strong>Ingrediente:</strong> {$productSupply['description']} (ID: {$productSupply['id']}, Estoque: {$productSupply['stock']})";
    echo "</div>";
    
    // Dados de teste
    $testData = [
        'action' => 'save',
        'product_id' => $productFinished['id'],
        'batch_size' => 1,
        'total_cost' => 10.00,
        'unit_cost' => 10.00,
        'profit_margin' => 50,
        'ingredients' => [
            [
                'product_id' => $productSupply['id'],
                'quantity' => 1,
                'cost' => 10.00
            ]
        ]
    ];
    
    echo "<h3>Dados de Teste:</h3>";
    echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    if (isset($_GET['test'])) {
        echo "<h3>Executando Teste...</h3>";
        
        try {
            $db->beginTransaction();
            
            // Inserir produção
            $stmt = $db->prepare("
                INSERT INTO productions (user_id, product_id, batch_size, total_cost, unit_cost, profit_margin)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $testData['product_id'],
                $testData['batch_size'],
                $testData['total_cost'],
                $testData['unit_cost'],
                $testData['profit_margin']
            ]);
            
            $productionId = $db->lastInsertId();
            echo "<div class='success'>✓ Produção criada com ID: $productionId</div>";
            
            // Inserir ingredientes
            $stmt = $db->prepare("
                INSERT INTO production_ingredients (production_id, product_id, quantity, cost)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($testData['ingredients'] as $ing) {
                $stmt->execute([
                    $productionId,
                    $ing['product_id'],
                    $ing['quantity'],
                    $ing['cost']
                ]);
            }
            echo "<div class='success'>✓ Ingredientes inseridos</div>";
            
            // Atualizar estoques
            $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$testData['batch_size'], $testData['product_id']]);
            echo "<div class='success'>✓ Estoque do produto final atualizado</div>";
            
            foreach ($testData['ingredients'] as $ing) {
                $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$ing['quantity'], $ing['product_id']]);
                echo "<div class='success'>✓ Estoque do ingrediente atualizado</div>";
            }
            
            $db->commit();
            echo "<div class='success'><strong>✓ TESTE CONCLUÍDO COM SUCESSO!</strong></div>";
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "<div class='error'><strong>✗ ERRO:</strong> " . $e->getMessage() . "</div>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    } else {
        echo "<p><a href='?test=1' style='display: inline-block; padding: 10px 20px; background: #0e639c; color: white; text-decoration: none; border-radius: 4px;'>▶ Executar Teste</a></p>";
    }
} else {
    echo "<div class='error'>Cadastre produtos acabados e insumos primeiro!</div>";
}

// 4. Verificar estrutura da tabela productions
echo "<h2>4. Estrutura da Tabela 'productions'</h2>";
$stmt = $db->query("PRAGMA table_info(productions)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($columns, true) . "</pre>";

// 5. Verificar estrutura da tabela production_ingredients
echo "<h2>5. Estrutura da Tabela 'production_ingredients'</h2>";
$stmt = $db->query("PRAGMA table_info(production_ingredients)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($columns, true) . "</pre>";

// 6. Últimas produções
echo "<h2>6. Últimas Produções</h2>";
try {
    $stmt = $db->query("SELECT * FROM productions ORDER BY created_at DESC LIMIT 5");
    $prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($prods)) {
        echo "<div class='info'>Nenhuma produção registrada ainda.</div>";
    } else {
        echo "<pre>" . print_r($prods, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Erro ao buscar produções: " . $e->getMessage() . "</div>";
}

echo "<p style='margin-top: 30px;'><a href='views/production/index.php' style='display: inline-block; padding: 10px 20px; background: #0e639c; color: white; text-decoration: none; border-radius: 4px;'>← Voltar para Produção</a></p>";

echo "</body></html>";
?>
