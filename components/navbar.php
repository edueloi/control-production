<?php
// 1. Lógica PHP para obter dados do utilizador
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;

// Fallback se não existir função, busca na sessão
if (!$currentUser) {
    $currentUser = $_SESSION['user_data'] ?? [
        'name' => $_SESSION['user_name'] ?? 'Utilizador Convidado',
        'role' => $_SESSION['user_role'] ?? 'visitante'
    ];
}

// 2. Lógica para gerar as Iniciais (ex: "João Silva" -> "JS")
$parts = explode(' ', trim($currentUser['name']));
$initials = strtoupper(substr($parts[0], 0, 1));
if (count($parts) > 1) {
    $initials .= strtoupper(substr(end($parts), 0, 1));
}
?>

<style>
    /* Variáveis de Cores e Tamanhos */
    :root {
        --nav-height: 70px;
        --nav-bg: #ffffff;
        --nav-text-main: #1e293b; /* Slate 800 */
        --nav-text-sub: #64748b;  /* Slate 500 */
        --nav-primary: #4f46e5;   /* Indigo 600 */
        --nav-hover: #f1f5f9;     /* Slate 100 */
        --nav-border: #e2e8f0;
        --nav-danger: #ef4444;
        --sidebar-width: 260px; /* Deve bater com o CSS da tua sidebar */
    }

    /* Container Principal */
    .navbar {
        height: var(--nav-height);
        background: var(--nav-bg);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.5rem;
        position: fixed;
        top: 0;
        right: 0;
        /* Calcula largura para não ficar por cima da sidebar */
        width: calc(100% - var(--sidebar-width)); 
        z-index: 900;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: width 0.3s ease;
        border-bottom: 1px solid var(--nav-border);
    }

    /* --- Lado Esquerdo --- */
    .navbar-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .page-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .page-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--nav-text-main);
        line-height: 1.2;
    }

    .current-date {
        font-size: 0.75rem;
        color: var(--nav-text-sub);
        font-weight: 500;
    }

    /* --- Lado Direito --- */
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Botões de Ícone (Toggle, Sino, Logout) */
    .icon-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: none;
        background: transparent;
        color: var(--nav-text-sub);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1.1rem;
        text-decoration: none;
        position: relative;
    }

    .icon-btn:hover {
        background: var(--nav-hover);
        color: var(--nav-primary);
        transform: translateY(-1px);
    }

    .logout-btn:hover {
        color: var(--nav-danger);
        background: #fee2e2;
    }

    /* Bolinha de Notificação */
    .badge-dot {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 8px;
        height: 8px;
        background: var(--nav-danger);
        border-radius: 50%;
        border: 2px solid var(--nav-bg);
    }

    /* Separador Vertical */
    .separator {
        width: 1px;
        height: 24px;
        background: var(--nav-border);
        margin: 0 0.5rem;
    }

    /* Pílula do Utilizador (Perfil) */
    .user-pill {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.25rem 0.25rem 0.25rem 1rem;
        background: transparent;
        border-radius: 50px;
        border: 1px solid transparent;
        transition: all 0.2s ease;
        cursor: default;
    }

    .user-pill:hover {
        background: var(--nav-hover);
        border-color: var(--nav-border);
    }

    .user-text {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        line-height: 1.1;
    }

    .user-text .name {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--nav-text-main);
    }

    .user-text .role {
        font-size: 0.7rem;
        color: var(--nav-text-sub);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        /* Gradiente bonito */
        background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        box-shadow: 0 3px 6px rgba(79, 70, 229, 0.25);
    }

    /* Botão Mobile (Hambúrguer) - Escondido no Desktop */
    .mobile-toggle {
        display: none;
        margin-right: 0.5rem;
    }

    /* --- Responsividade (Mobile < 768px) --- */
    @media (max-width: 768px) {
        .navbar {
            width: 100%; /* Ocupa a tela toda */
            padding: 0 1rem;
        }

        .mobile-toggle {
            display: flex; /* Aparece no mobile */
        }

        /* Esconder detalhes para poupar espaço */
        .user-text, .current-date {
            display: none;
        }

        .user-pill {
            padding: 0;
            border: none;
        }
        
        .user-pill:hover {
            background: transparent;
            border: none;
        }
        
        .separator {
            display: none;
        }
        
        .page-title {
            font-size: 1rem;
        }
    }
</style>

<nav class="navbar">
    
    <div class="navbar-left">
        <button class="icon-btn mobile-toggle" id="sidebarToggleMobile">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="page-info">
            <h3 class="page-title">
                <?php echo isset($pageTitle) ? $pageTitle : 'Painel de Controlo'; ?>
            </h3>
            <span class="current-date">
                <?php 
                setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
                echo ucfirst(strftime('%A, %d de %B')); 
                ?>
            </span>
        </div>
    </div>

    <div class="navbar-right">
        
        <button class="icon-btn notification-btn" title="Notificações">
            <i class="far fa-bell"></i>
            <span class="badge-dot"></span>
        </button>

        <div class="separator"></div>


        <div class="user-pill" id="userMenuToggle" tabindex="0" style="cursor:pointer;">
            <div class="user-text">
                <span class="name"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                <span class="role"><?php echo ucfirst($currentUser['role'] ?? 'Utilizador'); ?></span>
            </div>
            <div class="user-avatar">
                <span><?php echo $initials; ?></span>
            </div>
        </div>
        <!-- Dropdown Menu -->
        <div class="user-dropdown-menu" id="userDropdownMenu">
            <div class="dropdown-header">
                <div class="dropdown-avatar"><?php echo $initials; ?></div>
                <div class="dropdown-info">
                    <div class="dropdown-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                    <div class="dropdown-role"><?php echo ucfirst($currentUser['role'] ?? 'Utilizador'); ?></div>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>views/profile/" class="dropdown-item"><i class="fas fa-user"></i> Meu Perfil</a>
            <a href="<?php echo BASE_URL; ?>views/settings/" class="dropdown-item"><i class="fas fa-cog"></i> Configurações</a>
            <?php if (($currentUser['role'] ?? ($_SESSION['user_role'] ?? '')) === 'admin'): ?>
                <div class="dropdown-divider"></div>
                <a href="<?php echo BASE_URL; ?>views/users/" class="dropdown-item"><i class="fas fa-users-cog"></i> Usuários</a>
                <a href="<?php echo BASE_URL; ?>views/permissions/" class="dropdown-item"><i class="fas fa-user-shield"></i> Permissões</a>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item dropdown-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>

        <a href="<?php echo BASE_URL; ?>logout.php" class="icon-btn logout-btn" title="Sair do Sistema">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>

<style>
    .user-dropdown-menu {
        display: none;
        position: absolute;
        top: 70px;
        right: 1.5rem;
        min-width: 220px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 32px rgba(30,40,90,0.18);
        border: 1px solid #e2e8f0;
        z-index: 9999;
        padding: 0.5rem 0;
        animation: fadeInMenu 0.18s;
    }
    @keyframes fadeInMenu {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .user-dropdown-menu.open {
        display: block;
    }
    .dropdown-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.2rem 0.5rem 1.2rem;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 0.2rem;
    }
    .dropdown-avatar {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.18);
    }
    .dropdown-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .dropdown-name {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
    }
    .dropdown-role {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
    }
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.7rem 1.2rem;
        color: #1e293b;
        text-decoration: none;
        font-size: 0.97rem;
        font-weight: 500;
        transition: background 0.18s, color 0.18s;
        border: none;
        background: none;
        cursor: pointer;
    }
    .dropdown-item i {
        font-size: 1.1rem;
        color: #64748b;
        min-width: 18px;
        text-align: center;
    }
    .dropdown-item:hover {
        background: #f1f5f9;
        color: #4f46e5;
    }
    .dropdown-item.dropdown-logout {
        color: #ef4444;
    }
    .dropdown-item.dropdown-logout:hover {
        background: #fee2e2;
        color: #b91c1c;
    }
    .dropdown-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 0.2rem 0 0.2rem 0;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Sidebar mobile toggle
        const toggle = document.getElementById('sidebarToggleMobile');
        const sidebar = document.getElementById('appSidebar');
        if(toggle && sidebar) {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('show');
            });
            document.addEventListener('click', (e) => {
                if(window.innerWidth <= 768 && 
                   sidebar.classList.contains('show') && 
                   !sidebar.contains(e.target) && 
                   !toggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
        }

        // User dropdown menu
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        let dropdownOpen = false;
        if(userMenuToggle && userDropdownMenu) {
            userMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('open');
                dropdownOpen = userDropdownMenu.classList.contains('open');
            });
            // Fecha ao clicar fora
            document.addEventListener('click', (e) => {
                if(dropdownOpen && !userDropdownMenu.contains(e.target) && !userMenuToggle.contains(e.target)) {
                    userDropdownMenu.classList.remove('open');
                    dropdownOpen = false;
                }
            });
            // Fecha com ESC
            document.addEventListener('keydown', (e) => {
                if(dropdownOpen && e.key === 'Escape') {
                    userDropdownMenu.classList.remove('open');
                    dropdownOpen = false;
                }
            });
        }
    });
</script>