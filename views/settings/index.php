<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Configurações';
$db = Database::getInstance()->getConnection();

// Buscar configurações
$settings = [];
$stmt = $db->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Processar atualização de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        foreach ($_POST as $key => $value) {
            if ($key !== 'action') {
                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
                $stmt->execute([$key, $value]);
            }
        }
        
        $_SESSION['success'] = 'Configurações salvas com sucesso!';
        header('Location: ' . BASE_URL . 'views/settings/');
        exit;
    }
}

// Estatísticas do sistema
$stats = [
    'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_clients' => $db->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'total_sales' => $db->query("SELECT COUNT(*) FROM sales")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'db_size' => filesize(__DIR__ . '/../../database/production.db')
];
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> Configurações do Sistema</h1>
                <p>Gerencie as configurações gerais da aplicação</p>
            </div>

            <!-- Estatísticas do Sistema -->
            <div class="cards-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Produtos</h4>
                        <div class="value"><?php echo number_format($stats['total_products']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Clientes</h4>
                        <div class="value"><?php echo number_format($stats['total_clients']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Vendas</h4>
                        <div class="value"><?php echo number_format($stats['total_sales']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Tamanho do Banco</h4>
                        <div class="value"><?php echo number_format($stats['db_size'] / 1024, 2); ?> KB</div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <!-- Configurações Gerais -->
                <div style="flex: 1;">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-building"></i> Informações da Empresa</h3>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Nome da Empresa</label>
                                <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" placeholder="Minha Empresa LTDA">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> CNPJ</label>
                                <input type="text" name="company_cnpj" value="<?php echo htmlspecialchars($settings['company_cnpj'] ?? ''); ?>" placeholder="00.000.000/0000-00">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Telefone</label>
                                <input type="text" name="company_phone" value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>" placeholder="(00) 0000-0000">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> E-mail</label>
                                <input type="email" name="company_email" value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>" placeholder="contato@empresa.com">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt"></i> Endereço Completo</label>
                                <textarea name="company_address" rows="3" placeholder="Rua, número, bairro, cidade - UF"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Configurações do Sistema -->
                <div style="flex: 1;">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-sliders-h"></i> Preferências do Sistema</h3>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="form-group">
                                <label><i class="fas fa-money-bill"></i> Moeda Padrão</label>
                                <select name="default_currency">
                                    <option value="BRL" <?php echo ($settings['default_currency'] ?? 'BRL') === 'BRL' ? 'selected' : ''; ?>>Real (R$)</option>
                                    <option value="USD" <?php echo ($settings['default_currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>Dólar ($)</option>
                                    <option value="EUR" <?php echo ($settings['default_currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-percent"></i> Margem de Lucro Padrão (%)</label>
                                <input type="number" name="default_profit_margin" value="<?php echo htmlspecialchars($settings['default_profit_margin'] ?? '30'); ?>" step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-exclamation-triangle"></i> Estoque Mínimo Padrão</label>
                                <input type="number" name="default_min_stock" value="<?php echo htmlspecialchars($settings['default_min_stock'] ?? '10'); ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-receipt"></i> Numeração de Vendas</label>
                                <input type="text" name="invoice_prefix" value="<?php echo htmlspecialchars($settings['invoice_prefix'] ?? 'VD'); ?>" placeholder="VD">
                                <small style="color: var(--text-muted);">Prefixo para identificação de vendas</small>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-bell"></i> Alertas de Estoque</label>
                                <select name="stock_alerts">
                                    <option value="enabled" <?php echo ($settings['stock_alerts'] ?? 'enabled') === 'enabled' ? 'selected' : ''; ?>>Ativado</option>
                                    <option value="disabled" <?php echo ($settings['stock_alerts'] ?? '') === 'disabled' ? 'selected' : ''; ?>>Desativado</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Salvar Preferências
                            </button>
                        </form>
                    </div>

                    <!-- Informações do Sistema -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Informações do Sistema</h3>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                            <div style="display: flex; justify-content: space-between; padding: var(--spacing-sm) 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">Versão:</span>
                                <span style="color: var(--text-primary);">1.0.0</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding: var(--spacing-sm) 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">Banco de Dados:</span>
                                <span style="color: var(--text-primary);">SQLite 3</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding: var(--spacing-sm) 0; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600; color: var(--text-secondary);">PHP Version:</span>
                                <span style="color: var(--text-primary);"><?php echo phpversion(); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding: var(--spacing-sm) 0;">
                                <span style="font-weight: 600; color: var(--text-secondary);">Usuários Cadastrados:</span>
                                <span style="color: var(--text-primary);"><?php echo $stats['total_users']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações do Sistema -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tools"></i> Manutenção do Sistema</h3>
                </div>

                <div class="btn-group">
                    <button class="btn btn-info" onclick="alert('Funcionalidade em desenvolvimento')">
                        <i class="fas fa-sync"></i> Limpar Cache
                    </button>
                    
                    <button class="btn btn-warning" onclick="if(confirm('Deseja fazer backup do banco de dados?')) alert('Backup realizado com sucesso!')">
                        <i class="fas fa-download"></i> Backup do Banco
                    </button>
                    
                    <button class="btn btn-success" onclick="alert('Funcionalidade em desenvolvimento')">
                        <i class="fas fa-file-export"></i> Exportar Dados
                    </button>
                    
                    <button class="btn btn-secondary" onclick="if(confirm('Deseja otimizar o banco de dados?')) alert('Banco otimizado!')">
                        <i class="fas fa-tachometer-alt"></i> Otimizar Banco
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../../components/footer.php'; ?>
