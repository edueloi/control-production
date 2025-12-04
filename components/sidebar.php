<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$requestUri  = $_SERVER['REQUEST_URI'];
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="sidebar-title">
            <span class="sidebar-title-main">Menu Principal</span>
            <span class="sidebar-title-sub">Gestão completa</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <a href="<?php echo BASE_URL; ?>views/dashboard.php"
           class="menu-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"
           title="Dashboard">
            <i class="fas fa-home"></i>
            <span class="menu-text">Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/products/"
           class="menu-item <?php echo strpos($requestUri, '/products/') !== false ? 'active' : ''; ?>"
           title="Produtos">
            <i class="fas fa-box"></i>
            <span class="menu-text">Produtos</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/production/"
           class="menu-item <?php echo strpos($requestUri, '/production/') !== false ? 'active' : ''; ?>"
           title="Produção">
            <i class="fas fa-industry"></i>
            <span class="menu-text">Produção</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/stock/"
           class="menu-item <?php echo strpos($requestUri, '/stock/') !== false ? 'active' : ''; ?>"
           title="Estoque">
            <i class="fas fa-warehouse"></i>
            <span class="menu-text">Estoque</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/clients/"
           class="menu-item <?php echo strpos($requestUri, '/clients/') !== false ? 'active' : ''; ?>"
           title="Clientes">
            <i class="fas fa-users"></i>
            <span class="menu-text">Clientes</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/pdv/"
           class="menu-item <?php echo strpos($requestUri, '/pdv/') !== false ? 'active' : ''; ?>"
           title="PDV">
            <i class="fas fa-cash-register"></i>
            <span class="menu-text">PDV</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/reports/"
           class="menu-item <?php echo strpos($requestUri, '/reports/') !== false ? 'active' : ''; ?>"
           title="Relatórios">
            <i class="fas fa-chart-bar"></i>
            <span class="menu-text">Relatórios</span>
        </a>

        <div class="menu-divider"></div>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="<?php echo BASE_URL; ?>views/users/"
           class="menu-item <?php echo strpos($requestUri, '/users/') !== false ? 'active' : ''; ?>"
           title="Gerenciar Usuários">
            <i class="fas fa-users-cog"></i>
            <span class="menu-text">Usuários</span>
        </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>views/profile/"
           class="menu-item <?php echo strpos($requestUri, '/profile/') !== false ? 'active' : ''; ?>"
           title="Meu Perfil">
            <i class="fas fa-user-circle"></i>
            <span class="menu-text">Meu Perfil</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/settings/"
           class="menu-item <?php echo strpos($requestUri, '/settings/') !== false ? 'active' : ''; ?>"
           title="Configurações">
            <i class="fas fa-cog"></i>
            <span class="menu-text">Configurações</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <button class="sidebar-toggle" id="sidebarToggle" type="button">
            <i class="fas fa-chevron-left"></i>
            <span class="toggle-text">Recolher</span>
        </button>
    </div>
</aside>
