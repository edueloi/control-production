<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Estoque';
$db = Database::getInstance()->getConnection();

// Buscar produtos com informações de estoque
$products = $db->query("
    SELECT * FROM products 
    ORDER BY 
        CASE 
            WHEN stock <= min_stock THEN 0 
            ELSE 1 
        END,
        stock ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Buscar últimas movimentações
$movements = $db->query("
    SELECT sm.*, p.description as product_name, p.unit
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    ORDER BY sm.created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-warehouse"></i> Controle de Estoque</h1>
                <p>Gerencie o estoque dos seus produtos</p>
            </div>
            
            <!-- Estatísticas -->
            <div class="cards-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Produtos</h4>
                        <div class="value"><?php echo count($products); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Estoque Crítico</h4>
                        <div class="value">
                            <?php 
                            $lowStock = array_filter($products, fn($p) => $p['stock'] <= $p['min_stock'] && $p['min_stock'] > 0);
                            echo count($lowStock);
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Entradas Hoje</h4>
                        <div class="value">
                            <?php 
                            $todayIn = array_filter($movements, fn($m) => 
                                date('Y-m-d', strtotime($m['created_at'])) === date('Y-m-d') && 
                                $m['type'] === 'entrada'
                            );
                            echo count($todayIn);
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Saídas Hoje</h4>
                        <div class="value">
                            <?php 
                            $todayOut = array_filter($movements, fn($m) => 
                                date('Y-m-d', strtotime($m['created_at'])) === date('Y-m-d') && 
                                $m['type'] === 'saida'
                            );
                            echo count($todayOut);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ajuste Manual de Estoque -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Ajuste Manual de Estoque</h3>
                </div>
                <form id="stockAdjustForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_id">
                                <i class="fas fa-box"></i>
                                Produto *
                            </label>
                            <select id="product_id" name="product_id" required>
                                <option value="">Selecione um produto</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['description']); ?> 
                                        (Atual: <?php echo number_format($product['stock'], 2); ?> <?php echo strtoupper($product['unit']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="movement_type">
                                <i class="fas fa-exchange-alt"></i>
                                Tipo de Movimentação *
                            </label>
                            <select id="movement_type" name="type" required>
                                <option value="entrada">Entrada (+)</option>
                                <option value="saida">Saída (-)</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">
                                <i class="fas fa-sort-numeric-up"></i>
                                Quantidade *
                            </label>
                            <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="notes">
                                <i class="fas fa-sticky-note"></i>
                                Observações
                            </label>
                            <input type="text" id="notes" name="notes" placeholder="Motivo do ajuste">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Movimentação
                    </button>
                </form>
            </div>
            
            <!-- Lista de Estoque -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Status do Estoque</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Estoque Atual</th>
                                <th>Estoque Mínimo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['description']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                    <td>
                                        <?php
                                        $types = ['finished' => 'Acabado', 'intermediate' => 'Intermediário', 'supply' => 'Insumo'];
                                        $badges = ['finished' => 'success', 'intermediate' => 'info', 'supply' => 'warning'];
                                        ?>
                                        <span class="badge badge-<?php echo $badges[$product['type']]; ?>">
                                            <?php echo $types[$product['type']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($product['stock'], 2); ?></strong> 
                                        <?php echo strtoupper($product['unit']); ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($product['min_stock'], 2); ?> 
                                        <?php echo strtoupper($product['unit']); ?>
                                    </td>
                                    <td>
                                        <?php if ($product['stock'] <= $product['min_stock'] && $product['min_stock'] > 0): ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-exclamation-circle"></i> CRÍTICO
                                            </span>
                                        <?php elseif ($product['stock'] <= ($product['min_stock'] * 1.5) && $product['min_stock'] > 0): ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-exclamation-triangle"></i> BAIXO
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> OK
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Histórico de Movimentações -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Últimas Movimentações</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Produto</th>
                                <th>Tipo</th>
                                <th>Quantidade</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $movement): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($movement['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                    <td>
                                        <?php
                                        $typeColors = [
                                            'entrada' => 'success',
                                            'saida' => 'danger',
                                            'ajuste' => 'info'
                                        ];
                                        $typeIcons = [
                                            'entrada' => 'arrow-up',
                                            'saida' => 'arrow-down',
                                            'ajuste' => 'edit'
                                        ];
                                        ?>
                                        <span class="badge badge-<?php echo $typeColors[$movement['type']]; ?>">
                                            <i class="fas fa-<?php echo $typeIcons[$movement['type']]; ?>"></i>
                                            <?php echo strtoupper($movement['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($movement['quantity'], 2); ?></strong>
                                        <?php echo strtoupper($movement['unit']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($movement['notes'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.getElementById('stockAdjustForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'adjust');
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/stock_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert('Movimentação registrada com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert('Erro ao registrar movimentação', 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao registrar movimentação', 'error');
    }
});
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
