<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Relatórios';
$db = Database::getInstance()->getConnection();
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-chart-bar"></i> Relatórios e Análises</h1>
                <p>Visualize dados e estatísticas do sistema</p>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="sales-report">
                    <i class="fas fa-shopping-cart"></i> Vendas
                </button>
                <button class="tab-btn" data-tab="production-report">
                    <i class="fas fa-industry"></i> Produção
                </button>
                <button class="tab-btn" data-tab="stock-report">
                    <i class="fas fa-warehouse"></i> Estoque
                </button>
            </div>
            
            <!-- Relatório de Vendas -->
            <div id="sales-report" class="tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Filtros</h3>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Data Início</label>
                            <input type="date" id="sales_start_date" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Data Fim</label>
                            <input type="date" id="sales_end_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Forma de Pagamento</label>
                            <select id="sales_payment">
                                <option value="">Todas</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="pix">PIX</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="gerarRelatorioVendas()">
                        <i class="fas fa-chart-line"></i> Gerar Relatório
                    </button>
                </div>
                
                <div class="card" id="salesResults" style="display: none;">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> Resultados</h3>
                        <button class="btn btn-success btn-sm" onclick="exportarPDF('vendas')">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                    
                    <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Total de Vendas</h4>
                                <div class="value" id="totalSales">0</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Faturamento</h4>
                                <div class="value" id="totalRevenue">R$ 0,00</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Ticket Médio</h4>
                                <div class="value" id="avgTicket">R$ 0,00</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Forma de Pagamento</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="salesTableBody">
                                <!-- Dados serão inseridos aqui -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Relatório de Produção -->
            <div id="production-report" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Filtros</h3>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Data Início</label>
                            <input type="date" id="prod_start_date" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Data Fim</label>
                            <input type="date" id="prod_end_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="gerarRelatorioProducao()">
                        <i class="fas fa-chart-line"></i> Gerar Relatório
                    </button>
                </div>
                
                <div class="card" id="productionResults" style="display: none;">
                    <div class="card-header">
                        <h3><i class="fas fa-industry"></i> Produções Realizadas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Produto</th>
                                    <th>Lote</th>
                                    <th>Custo Total</th>
                                    <th>Custo Unitário</th>
                                </tr>
                            </thead>
                            <tbody id="productionTableBody">
                                <!-- Dados serão inseridos aqui -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Relatório de Estoque -->
            <div id="stock-report" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-boxes"></i> Status Geral do Estoque</h3>
                        <button class="btn btn-success btn-sm" onclick="exportarPDF('estoque')">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                    
                    <?php
                    $products = $db->query("SELECT * FROM products ORDER BY stock ASC")->fetchAll(PDO::FETCH_ASSOC);
                    $totalValue = 0;
                    foreach ($products as $p) {
                        $totalValue += $p['stock'] * $p['cost'];
                    }
                    ?>
                    
                    <div class="cards-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Total de Produtos</h4>
                                <div class="value"><?php echo count($products); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Valor Total em Estoque</h4>
                                <div class="value"><?php echo formatMoney($totalValue); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Estoque Atual</th>
                                    <th>Valor Unitário</th>
                                    <th>Valor Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                                        <td><?php echo number_format($product['stock'], 2); ?> <?php echo strtoupper($product['unit']); ?></td>
                                        <td><?php echo formatMoney($product['cost']); ?></td>
                                        <td><?php echo formatMoney($product['stock'] * $product['cost']); ?></td>
                                        <td>
                                            <?php if ($product['stock'] <= $product['min_stock'] && $product['min_stock'] > 0): ?>
                                                <span class="badge badge-danger">CRÍTICO</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">OK</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
async function gerarRelatorioVendas() {
    const startDate = document.getElementById('sales_start_date').value;
    const endDate = document.getElementById('sales_end_date').value;
    const payment = document.getElementById('sales_payment').value;
    
    try {
        const response = await fetch(`<?php echo BASE_URL; ?>controllers/report_controller.php?action=sales&start=${startDate}&end=${endDate}&payment=${payment}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('totalSales').textContent = result.data.total_sales;
            document.getElementById('totalRevenue').textContent = Utils.formatMoney(result.data.total_revenue);
            document.getElementById('avgTicket').textContent = Utils.formatMoney(result.data.avg_ticket);
            
            const tbody = document.getElementById('salesTableBody');
            tbody.innerHTML = result.data.sales.map(sale => `
                <tr>
                    <td>${new Date(sale.created_at).toLocaleDateString('pt-BR')}</td>
                    <td>${sale.client_name || 'Não informado'}</td>
                    <td>${sale.payment_method}</td>
                    <td><strong>${Utils.formatMoney(sale.total)}</strong></td>
                </tr>
            `).join('');
            
            document.getElementById('salesResults').style.display = 'block';
        }
    } catch (error) {
        Utils.showAlert('Erro ao gerar relatório', 'error');
    }
}

async function gerarRelatorioProducao() {
    const startDate = document.getElementById('prod_start_date').value;
    const endDate = document.getElementById('prod_end_date').value;
    
    try {
        const response = await fetch(`<?php echo BASE_URL; ?>controllers/report_controller.php?action=production&start=${startDate}&end=${endDate}`);
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('productionTableBody');
            tbody.innerHTML = result.data.map(prod => `
                <tr>
                    <td>${new Date(prod.created_at).toLocaleDateString('pt-BR')}</td>
                    <td>${prod.product_name}</td>
                    <td>${prod.batch_size} unidades</td>
                    <td>${Utils.formatMoney(prod.total_cost)}</td>
                    <td>${Utils.formatMoney(prod.unit_cost)}</td>
                </tr>
            `).join('');
            
            document.getElementById('productionResults').style.display = 'block';
        }
    } catch (error) {
        Utils.showAlert('Erro ao gerar relatório', 'error');
    }
}

function exportarPDF(tipo) {
    Utils.showAlert('Funcionalidade de exportação em desenvolvimento', 'info');
}
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
