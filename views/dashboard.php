<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle = 'Dashboard';
$db = Database::getInstance()->getConnection();

// Estatísticas
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalClients = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$totalSales = $db->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = DATE('now')")->fetchColumn();
$todaySalesAmount = $db->query("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) = DATE('now')")->fetchColumn();

// Produtos com estoque baixo
$lowStockProducts = $db->query("SELECT * FROM products WHERE stock <= min_stock AND min_stock > 0 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Últimas vendas
$recentSales = $db->query("
    SELECT s.*, c.name as client_name 
    FROM sales s 
    LEFT JOIN clients c ON s.client_id = c.id 
    ORDER BY s.created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-home"></i> Dashboard</h1>
                <p>Visão geral do sistema</p>
            </div>
            
            <!-- Estatísticas -->
            <div class="cards-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Produtos</h4>
                        <div class="value"><?php echo $totalProducts; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Clientes</h4>
                        <div class="value"><?php echo $totalClients; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Vendas Hoje</h4>
                        <div class="value"><?php echo $totalSales; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Faturamento Hoje</h4>
                        <div class="value"><?php echo formatMoney($todaySalesAmount); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Produtos com Estoque Baixo -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Produtos com Estoque Baixo</h3>
                    </div>
                    <?php if (count($lowStockProducts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Estoque</th>
                                        <th>Mínimo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    <?php echo number_format($product['stock'], 2); ?> <?php echo strtoupper($product['unit']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($product['min_stock'], 2); ?> <?php echo strtoupper($product['unit']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #718096; padding: 20px;">
                            <i class="fas fa-check-circle" style="font-size: 48px; color: #48bb78; display: block; margin-bottom: 10px;"></i>
                            Todos os produtos estão com estoque adequado!
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Últimas Vendas -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-receipt"></i> Últimas Vendas</h3>
                    </div>
                    <?php if (count($recentSales) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentSales as $sale): ?>
                                        <tr>
                                            <td><?php echo date('d/m H:i', strtotime($sale['created_at'])); ?></td>
                                            <td><?php echo $sale['client_name'] ?? 'Cliente Não Informado'; ?></td>
                                            <td><strong><?php echo formatMoney($sale['total']); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #718096; padding: 20px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e0; display: block; margin-bottom: 10px;"></i>
                            Nenhuma venda registrada ainda.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-rocket"></i> Ações Rápidas</h3>
                </div>
                <div class="btn-group">
                    <a href="<?php echo BASE_URL; ?>views/products.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Produto
                    </a>
                    <a href="<?php echo BASE_URL; ?>views/production.php" class="btn btn-success">
                        <i class="fas fa-industry"></i> Nova Produção
                    </a>
                    <a href="<?php echo BASE_URL; ?>views/pdv.php" class="btn btn-info">
                        <i class="fas fa-cash-register"></i> Abrir PDV
                    </a>
                    <a href="<?php echo BASE_URL; ?>views/clients.php" class="btn btn-warning">
                        <i class="fas fa-user-plus"></i> Novo Cliente
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
    .page-header {
        margin-bottom: 30px;
    }
    
    .page-header h1 {
        font-size: 28px;
        color: var(--dark-color);
        margin-bottom: 5px;
    }
    
    .page-header p {
        color: var(--text-muted);
    }
</style>

<?php include __DIR__ . '/../components/footer.php'; ?>
