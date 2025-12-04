<?php
// Função auxiliar para verificar active (torna o código mais limpo)
function isActive($uri, $search, $isExact = false) {
    if ($isExact) {
        return $uri === $search ? 'active' : '';
    }
    return strpos($uri, $search) !== false ? 'active' : '';
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$requestUri  = $_SERVER['REQUEST_URI'];
?>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-cubes"></i>
        </div>
        <div class="sidebar-title">
            <span class="sidebar-title-main">Menu Principal</span>
            <span class="sidebar-title-sub">Gestão Completa</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        
        <a href="<?php echo BASE_URL; ?>views/dashboard.php"
           class="menu-item <?php echo isActive($currentPage, 'dashboard', true); ?>">
            <i class="fas fa-home"></i>
            <span class="menu-text">Dashboard</span>
        </a>

        <span class="menu-category">Operacional</span>

        <a href="<?php echo BASE_URL; ?>views/products/"
           class="menu-item <?php echo isActive($requestUri, '/products/'); ?>">
            <i class="fas fa-box"></i>
            <span class="menu-text">Produtos</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/production/"
           class="menu-item <?php echo isActive($requestUri, '/production/'); ?>">
            <i class="fas fa-industry"></i>
            <span class="menu-text">Produção</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/stock/"
           class="menu-item <?php echo isActive($requestUri, '/stock/'); ?>">
            <i class="fas fa-warehouse"></i>
            <span class="menu-text">Estoque</span>
        </a>

        <span class="menu-category">Comercial</span>

        <a href="<?php echo BASE_URL; ?>views/clients/"
           class="menu-item <?php echo isActive($requestUri, '/clients/'); ?>">
            <i class="fas fa-users"></i>
            <span class="menu-text">Clientes</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/pdv/"
           class="menu-item <?php echo isActive($requestUri, '/pdv/'); ?>">
            <i class="fas fa-cash-register"></i>
            <span class="menu-text">PDV</span>
        </a>

        <div class="menu-divider"></div>

        <a href="<?php echo BASE_URL; ?>views/reports/"
           class="menu-item <?php echo isActive($requestUri, '/reports/'); ?>">
            <i class="fas fa-chart-bar"></i>
            <span class="menu-text">Relatórios</span>
        </a>

        <span class="menu-category">Sistema</span>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>views/users/"
               class="menu-item <?php echo isActive($requestUri, '/users/'); ?>">
                <i class="fas fa-users-cog"></i>
                <span class="menu-text">Usuários</span>
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>views/permissions/"
           class="menu-item <?php echo isActive($requestUri, '/permissions/'); ?>">
            <i class="fas fa-user-shield"></i>
            <span class="menu-text">Permissões</span>
        </a>

        <a href="<?php echo BASE_URL; ?>views/profile/"
           class="menu-item <?php echo isActive($requestUri, '/profile/'); ?>">
            <i class="fas fa-user-circle"></i>
            <span class="menu-text">Meu Perfil</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>views/settings/" 
           class="menu-item <?php echo isActive($requestUri, '/settings/'); ?>" 
           style="margin-bottom: 0;">
            <i class="fas fa-cog"></i>
            <span class="menu-text">Configurações</span>
        </a>
    </div>
</aside>