<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-boxes"></i>
        <h2>Menu Principal</h2>
    </div>
    <nav class="sidebar-menu">
        <a href="<?php echo BASE_URL; ?>views/dashboard.php" class="menu-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span class="menu-text">Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/products/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/products/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span class="menu-text">Produtos</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/production/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/production/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-industry"></i>
            <span class="menu-text">Produção</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/stock/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/stock/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-warehouse"></i>
            <span class="menu-text">Estoque</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/clients/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/clients/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span class="menu-text">Clientes</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/pdv/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/pdv/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-cash-register"></i>
            <span class="menu-text">PDV</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/reports/" class="menu-item <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span class="menu-text">Relatórios</span>
        </a>
    </nav>
    
    <!-- Botão para colapsar sidebar -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-chevron-left"></i>
    </button>
</aside>
