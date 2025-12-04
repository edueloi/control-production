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

$currentUser = getCurrentUser();
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../components/alerts.php'; ?>
            
            <div class="page-header">
                <div>
                    <h1>Olá, <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?>! 👋</h1>
                    <p>Aqui está o resumo das atividades de hoje.</p>
                </div>
                <div class="date-display">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('d/m/Y'); ?>
                </div>
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
            
            <div class="dashboard-grid">
                <!-- Produtos com Estoque Baixo -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Estoque Baixo</h3>
                        <a href="<?php echo BASE_URL; ?>views/stock/" class="btn-link">Ver todos</a>
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
                        <div class="empty-state">
                            <i class="fas fa-check-circle success-icon"></i>
                            <p>Todos os produtos estão com estoque adequado!</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Últimas Vendas -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-receipt"></i> Últimas Vendas</h3>
                        <a href="<?php echo BASE_URL; ?>views/reports/" class="btn-link">Relatórios</a>
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
                                            <td><?php echo htmlspecialchars($sale['client_name'] ?? 'Cliente Não Informado'); ?></td>
                                            <td><strong><?php echo formatMoney($sale['total']); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Nenhuma venda registrada ainda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3 class="section-title">Ações Rápidas</h3>
            <div class="actions-grid">
                <a href="<?php echo BASE_URL; ?>views/products/" class="action-card primary">
                    <div class="icon"><i class="fas fa-plus"></i></div>
                    <span>Novo Produto</span>
                </a>

                <a href="<?php echo BASE_URL; ?>views/production/" class="action-card success">
                    <div class="icon"><i class="fas fa-industry"></i></div>
                    <span>Nova Produção</span>
                </a>

                <a href="<?php echo BASE_URL; ?>views/pdv/" class="action-card info">
                    <div class="icon"><i class="fas fa-cash-register"></i></div>
                    <span>Abrir PDV</span>
                </a>

                <a href="<?php echo BASE_URL; ?>views/clients/" class="action-card warning">
                    <div class="icon"><i class="fas fa-user-plus"></i></div>
                    <span>Novo Cliente</span>
                </a>
            </div>
        </main>
    </div>
</div>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .date-display {
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: var(--spacing-xl);
        margin-bottom: var(--spacing-2xl);
    }

    .btn-link {
        font-size: 13px;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
    }
    
    .btn-link:hover {
        text-decoration: underline;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 48px;
        display: block;
        margin-bottom: 15px;
        color: #cbd5e0;
    }

    .empty-state .success-icon {
        color: var(--success-color);
        opacity: 0.5;
    }

    .section-title {
        font-size: 18px;
        margin-bottom: var(--spacing-lg);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: var(--spacing-lg);
    }

    .action-card {
        background: white;
        padding: 20px;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 15px;
        text-decoration: none;
        color: var(--text-primary);
        font-weight: 600;
        transition: all 0.2s;
        border: 1px solid var(--border-color);
    }

    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }

    .action-card .icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
    }

    .action-card.primary .icon { background: linear-gradient(135deg, var(--primary-color), var(--primary-light)); }
    .action-card.success .icon { background: linear-gradient(135deg, var(--success-color), #34d399); }
    .action-card.info .icon { background: linear-gradient(135deg, var(--info-color), #60a5fa); }
    .action-card.warning .icon { background: linear-gradient(135deg, var(--warning-color), #fbbf24); }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
</style>

<?php include __DIR__ . '/../components/footer.php'; ?>
