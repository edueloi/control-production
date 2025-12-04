<?php
// Função auxiliar para verificar active
function isActive($uri, $search, $isExact = false) {
    if ($isExact) {
        return $uri === $search ? 'active' : '';
    }
    return strpos($uri, $search) !== false ? 'active' : '';
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$requestUri  = $_SERVER['REQUEST_URI'];
?>

<style>
    :root {
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 70px; /* Largura quando fechada */
        --sidebar-bg: #1e293b;
        --sidebar-text: #94a3b8;
        --sidebar-hover: #334155;
        --sidebar-active: #4f46e5;
    }

    /* Sidebar Base */
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background-color: var(--sidebar-bg);
        position: fixed;
        top: 0; left: 0; z-index: 1000;
        display: flex; flex-direction: column;
        transition: width 0.3s ease, transform 0.3s ease;
        box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        overflow-x: hidden; /* Esconde scroll horizontal na animação */
    }

    /* Cabeçalho */
    .sidebar-header {
        height: 70px;
        display: flex; align-items: center;
        padding: 0 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        color: white;
        white-space: nowrap; /* Impede quebra de texto */
        overflow: hidden;
    }
    .sidebar-logo { font-size: 1.5rem; color: var(--sidebar-active); min-width: 30px; }
    .sidebar-title { margin-left: 10px; transition: opacity 0.3s; opacity: 1; }
    .sidebar-title-main { font-weight: 700; font-size: 1rem; display: block; }
    .sidebar-title-sub { font-size: 0.7rem; color: var(--sidebar-text); }

    /* Menu */
    .sidebar-menu { flex: 1; padding: 1rem 0.75rem; overflow-y: auto; overflow-x: hidden; }

    .menu-item {
        display: flex; align-items: center;
        padding: 0.75rem 1rem;
        color: var(--sidebar-text);
        text-decoration: none;
        border-radius: 0.5rem;
        margin-bottom: 0.25rem;
        transition: all 0.2s;
        font-size: 0.95rem;
        white-space: nowrap;
    }
    .menu-item:hover { background-color: var(--sidebar-hover); color: white; }
    .menu-item.active { background-color: var(--sidebar-active); color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
    .menu-item i { font-size: 1.2rem; min-width: 24px; text-align: center; margin-right: 1rem; }
    .menu-text { transition: opacity 0.3s; opacity: 1; }

    .menu-category {
        color: #64748b; padding-left: 1rem; margin: 1.5rem 0 0.5rem;
        display: block; font-size: 0.7rem; text-transform: uppercase; 
        letter-spacing: 1px; font-weight: 700; white-space: nowrap;
    }

    .menu-divider { height: 1px; background-color: rgba(255,255,255,0.1); margin: 0.5rem 0; }
    .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.05); }

    /* Botão Fechar (Mobile) */
    .sidebar-close-btn { display: none; background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; margin-left: auto; }

    /* --- ESTADO COLAPSADO (DESKTOP) --- */
    .sidebar.is-collapsed {
        width: var(--sidebar-collapsed-width);
    }
    .sidebar.is-collapsed .sidebar-title,
    .sidebar.is-collapsed .menu-text,
    .sidebar.is-collapsed .menu-category,
    .sidebar.is-collapsed .sidebar-footer .menu-text {
        opacity: 0;
        pointer-events: none;
        display: none; /* Garante que some */
    }
    .sidebar.is-collapsed .sidebar-header { padding: 0; justify-content: center; }
    .sidebar.is-collapsed .sidebar-logo { margin: 0; }
    .sidebar.is-collapsed .menu-item { padding: 0.75rem 0; justify-content: center; }
    .sidebar.is-collapsed .menu-item i { margin: 0; }
    .sidebar.is-collapsed .menu-divider { margin: 0.5rem auto; width: 80%; }

    /* --- RESPONSIVIDADE (MOBILE) --- */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; } /* Força largura total no mobile */
        .sidebar.show { transform: translateX(0); }
        .sidebar-close-btn { display: block; }
        
        /* No mobile, não existe estado colapsado visualmente, apenas show/hide */
        .sidebar.is-collapsed { transform: translateX(-100%); } 
    }
</style>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fas fa-cubes"></i></div>
        <div class="sidebar-title">
            <span class="sidebar-title-main">ERP System</span>
            <span class="sidebar-title-sub">Gestão Completa</span>
        </div>
        <button class="sidebar-close-btn" id="sidebarCloseBtn"><i class="fas fa-times"></i></button>
    </div>

    <nav class="sidebar-menu">
        <a href="<?php echo BASE_URL; ?>views/dashboard.php" class="menu-item <?php echo isActive($currentPage, 'dashboard', true); ?>" title="Dashboard">
            <i class="fas fa-home"></i><span class="menu-text">Dashboard</span>
        </a>

        <span class="menu-category">Operacional</span>
        <a href="<?php echo BASE_URL; ?>views/products/" class="menu-item <?php echo isActive($requestUri, '/products/'); ?>" title="Produtos">
            <i class="fas fa-box"></i><span class="menu-text">Produtos</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/production/" class="menu-item <?php echo isActive($requestUri, '/production/'); ?>" title="Produção">
            <i class="fas fa-industry"></i><span class="menu-text">Produção</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/stock/" class="menu-item <?php echo isActive($requestUri, '/stock/'); ?>" title="Estoque">
            <i class="fas fa-warehouse"></i><span class="menu-text">Estoque</span>
        </a>

        <span class="menu-category">Comercial</span>
        <a href="<?php echo BASE_URL; ?>views/clients/" class="menu-item <?php echo isActive($requestUri, '/clients/'); ?>" title="Clientes">
            <i class="fas fa-users"></i><span class="menu-text">Clientes</span>
        </a>
        <a href="<?php echo BASE_URL; ?>views/pdv/" class="menu-item <?php echo isActive($requestUri, '/pdv/'); ?>" title="PDV">
            <i class="fas fa-cash-register"></i><span class="menu-text">PDV</span>
        </a>

        <div class="menu-divider"></div>
        <a href="<?php echo BASE_URL; ?>views/reports/" class="menu-item <?php echo isActive($requestUri, '/reports/'); ?>" title="Relatórios">
            <i class="fas fa-chart-bar"></i><span class="menu-text">Relatórios</span>
        </a>

        <span class="menu-category">Sistema</span>
        <?php
        $permFile = __DIR__ . '/../config/permissions.php';
        $allPermissions = file_exists($permFile) ? require $permFile : [];
        $userRole = $_SESSION['user_role'] ?? 'user';
        $userPerms = $allPermissions[$userRole] ?? [];
        ?>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>views/users/" class="menu-item <?php echo isActive($requestUri, '/users/'); ?>" title="Usuários">
                <i class="fas fa-users-cog"></i><span class="menu-text">Usuários</span>
            </a>
        <?php endif; ?>

        <?php if (!empty($userPerms['manage_permissions']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
            <a href="<?php echo BASE_URL; ?>views/permissions/index.php" class="menu-item <?php echo isActive($requestUri, '/permissions/'); ?>" title="Permissões">
                <i class="fas fa-user-shield"></i><span class="menu-text">Permissões</span>
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>views/profile/" class="menu-item <?php echo isActive($requestUri, '/profile/'); ?>" title="Meu Perfil">
            <i class="fas fa-user-circle"></i><span class="menu-text">Meu Perfil</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>views/settings/" class="menu-item <?php echo isActive($requestUri, '/settings/'); ?>" style="margin-bottom: 0;" title="Configurações">
            <i class="fas fa-cog"></i><span class="menu-text">Configurações</span>
        </a>
    </div>
</aside>

<!-- O JS para colapsar/expandir sidebar está no navbar.php -->