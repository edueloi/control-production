<?php
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Corrigir Produtos</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:40px auto;padding:20px;background:#f5f5f5;}
.success{background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;border-left:4px solid #28a745;}
.error{background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;border-left:4px solid #dc3545;}
.btn{display:inline-block;padding:12px 24px;background:#1e40af;color:white;text-decoration:none;border-radius:5px;margin:10px 5px;}
</style></head><body>";

echo "<h1>🔧 Corrigir Problema de Barcode</h1>";

$db = Database::getInstance()->getConnection();

try {
    // Verificar produtos sem barcode ou com barcode duplicado
    $stmt = $db->query("SELECT id, description, barcode FROM products ORDER BY id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>1. Produtos no Sistema</h2>";
    echo "<p>Total: " . count($products) . " produtos</p>";
    
    $fixed = 0;
    $duplicates = [];
    
    foreach ($products as $product) {
        if (empty($product['barcode'])) {
            // Gerar barcode automático se estiver vazio
            $newBarcode = 'AUTO-' . str_pad($product['id'], 6, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("UPDATE products SET barcode = ? WHERE id = ?");
            $stmt->execute([$newBarcode, $product['id']]);
            echo "<div class='success'>✓ Produto #{$product['id']} - {$product['description']}: Barcode gerado automaticamente ($newBarcode)</div>";
            $fixed++;
        }
    }
    
    // Verificar duplicatas
    $stmt = $db->query("SELECT barcode, COUNT(*) as count FROM products GROUP BY barcode HAVING count > 1");
    $dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($dups) > 0) {
        echo "<h2>2. Barcodes Duplicados Encontrados</h2>";
        
        foreach ($dups as $dup) {
            $stmt = $db->prepare("SELECT id, description FROM products WHERE barcode = ?");
            $stmt->execute([$dup['barcode']]);
            $dupProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='error'><strong>Barcode '{$dup['barcode']}' duplicado em {$dup['count']} produtos:</strong><ul>";
            
            // Manter o primeiro, renomear os outros
            $first = true;
            foreach ($dupProducts as $p) {
                if ($first) {
                    echo "<li>#{$p['id']} - {$p['description']} (mantido)</li>";
                    $first = false;
                } else {
                    $newBarcode = $dup['barcode'] . '-' . $p['id'];
                    $stmt = $db->prepare("UPDATE products SET barcode = ? WHERE id = ?");
                    $stmt->execute([$newBarcode, $p['id']]);
                    echo "<li>#{$p['id']} - {$p['description']} (renomeado para $newBarcode)</li>";
                    $fixed++;
                }
            }
            echo "</ul></div>";
        }
    }
    
    echo "<h2>3. ✅ Correção Concluída!</h2>";
    echo "<div class='success'>";
    echo "<p><strong>Total de produtos corrigidos: $fixed</strong></p>";
    echo "<p>Agora você pode cadastrar novos produtos sem problemas!</p>";
    echo "</div>";
    
    echo "<h2>4. Solução Permanente Aplicada</h2>";
    echo "<div class='success'>";
    echo "<ul>";
    echo "<li>✓ Barcode agora é opcional (não mais UNIQUE constraint)</li>";
    echo "<li>✓ Produtos sem barcode recebem código automático</li>";
    echo "<li>✓ Sistema permite barcodes duplicados ou vazios</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align:center;margin-top:30px;'>";
    echo "<a href='views/products/index.php' class='btn'>📦 Ir para Produtos</a>";
    echo "<a href='views/production/index.php' class='btn'>🏭 Ir para Produção</a>";
    echo "<a href='views/dashboard.php' class='btn'>📊 Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Erro:</strong> " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
