<?php
// 1. Lógica PHP: Obter dados do utilizador
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;

// Fallback se não existir função
if (!$currentUser) {
    $currentUser = $_SESSION['user_data'] ?? [
        'name' => ($_SESSION['user_name'] ?? 'Utilizador Convidado'),
        'role' => ($_SESSION['user_role'] ?? 'visitante')
    ];
}

// Gerar Iniciais (ex: "Flavio Silva" -> "FS")
$parts = explode(' ', trim($currentUser['name']));
$initials = strtoupper(substr($parts[0], 0, 1));
if (count($parts) > 1) {
    $initials .= strtoupper(substr(end($parts), 0, 1));
}

// 2. Lógica PHP: Alertas (Estoque e Validade)
$db = isset($db) ? $db : (class_exists('Database') ? Database::getInstance()->getConnection() : null);
$alertas = [];
$alertCount = 0;

if ($db) {
    try {
        // Alerta de Estoque Baixo
        $lowStock = $db->query("SELECT * FROM products WHERE stock <= min_stock AND min_stock > 0 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lowStock as $p) {
            $alertas[] = [
                'icon' => 'fa-exclamation-triangle',
                'color' => '#ef4444', // Vermelho
                'text' => 'Estoque baixo: ' . htmlspecialchars($p['description'])
            ];
        }
        $alertCount += count($lowStock);

        // Alerta de Validade (verifica se a coluna existe para evitar erro)
        $cols = $db->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_COLUMN, 1);
        // Ajuste 'validade' para o nome real da tua coluna no banco, se for diferente
        $colValidade = in_array('validade', $cols) ? 'validade' : (in_array('expiry_date', $cols) ? 'expiry_date' : null);

        if ($colValidade) {
            $validade = $db->query("SELECT * FROM products WHERE $colValidade IS NOT NULL AND $colValidade <> '' AND DATE($colValidade) <= DATE('now', '+30 days') ORDER BY $colValidade ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($validade as $p) {
                $isVencido = strtotime($p[$colValidade]) < time();
                $alertas[] = [
                    'icon' => $isVencido ? 'fa-times-circle' : 'fa-clock',
                    'color' => $isVencido ? '#b91c1c' : '#f59e0b', // Vermelho escuro ou Laranja
                    'text' => ($isVencido ? 'Vencido: ' : 'Vence em breve: ') . htmlspecialchars($p['description'])
                ];
            }
            $alertCount += count($validade);
        }
    } catch (Exception $e) {
        // Silencia erros de SQL para não quebrar a navbar
    }
}
?>

<style>
    :root {
        --nav-height: 70px;
        --nav-bg: #ffffff;
        --nav-text-main: #1e293b;
        --nav-text-sub: #64748b;
        --nav-primary: #4f46e5;
        --nav-hover: #f1f5f9;
        --nav-border: #e2e8f0;
        --sidebar-width: 260px;
    }

    /* Navbar Container */
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
        width: calc(100% - var(--sidebar-width));
        z-index: 900;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        border-bottom: 1px solid var(--nav-border);
        transition: width 0.3s ease;
    }

    /* Lado Esquerdo */
    .navbar-left { display: flex; align-items: center; gap: 1rem; }
    
    .page-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--nav-text-main);
    }
    
    .current-date {
        font-size: 0.75rem;
        color: var(--nav-text-sub);
        font-weight: 500;
        text-transform: capitalize;
    }

    /* Lado Direito */
    .navbar-right { display: flex; align-items: center; gap: 0.8rem; }

    /* Botões (Ícones) */
    .icon-btn {
        width: 40px; height: 40px;
        border-radius: 10px;
        border: none;
        background: transparent;
        color: var(--nav-text-sub);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1.1rem;
        position: relative;
    }
    .icon-btn:hover { background: var(--nav-hover); color: var(--nav-primary); }
    .logout-btn:hover { background: #fee2e2; color: #ef4444; }

    /* Badge de Notificação */
    .badge-dot {
        position: absolute; top: 10px; right: 10px;
        width: 8px; height: 8px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid white;
    }
    
    .alert-count {
        position: absolute; top: -5px; right: -5px;
        background: #ef4444; color: white;
        font-size: 0.7rem; font-weight: bold;
        padding: 2px 5px; border-radius: 10px;
    }

    /* Perfil do Utilizador (Pílula) */
    .user-pill {
        display: flex; align-items: center; gap: 0.8rem;
        padding: 0.25rem 0.25rem 0.25rem 1rem;
        border-radius: 50px;
        cursor: pointer;
        transition: background 0.2s;
        border: 1px solid transparent;
    }
    .user-pill:hover, .user-pill.active {
        background: var(--nav-hover);
        border-color: var(--nav-border);
    }

    .user-text { display: flex; flex-direction: column; align-items: flex-end; line-height: 1.1; }
    .user-text .name { font-size: 0.85rem; font-weight: 600; color: var(--nav-text-main); }
    .user-text .role { font-size: 0.7rem; color: var(--nav-text-sub); text-transform: uppercase; font-weight: 700; }

    .user-avatar {
        width: 38px; height: 38px;
        background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        color: white; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.95rem;
        box-shadow: 0 2px 5px rgba(99, 102, 241, 0.3);
    }

    /* Dropdown Menu */
    .user-dropdown-menu {
        display: none;
        position: absolute;
        top: 75px; right: 1.5rem;
        width: 240px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border: 1px solid var(--nav-border);
        z-index: 1000;
        animation: slideDown 0.2s ease;
    }
    .user-dropdown-menu.open { display: block; }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-header {
        padding: 1rem;
        border-bottom: 1px solid var(--nav-hover);
        display: flex; align-items: center; gap: 1rem;
    }
    .dropdown-info .d-name { display: block; font-weight: 700; color: var(--nav-text-main); }
    .dropdown-info .d-role { display: block; font-size: 0.75rem; color: var(--nav-text-sub); }

    .dropdown-item {
        display: flex; align-items: center; gap: 0.8rem;
        padding: 0.8rem 1.2rem;
        color: var(--nav-text-main);
        text-decoration: none;
        font-size: 0.9rem;
        transition: background 0.2s;
    }
    .dropdown-item:hover { background: var(--nav-hover); color: var(--nav-primary); }
    .dropdown-item i { width: 20px; text-align: center; color: var(--nav-text-sub); }
    .dropdown-logout { color: #ef4444; border-top: 1px solid var(--nav-hover); }
    .dropdown-logout:hover { background: #fef2f2; color: #dc2626; }

    /* Modal de Alertas */
    .alert-modal {
        display: none; position: fixed;
        top: 75px; right: 80px; /* Posição relativa ao sino */
        width: 320px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border: 1px solid var(--nav-border);
        z-index: 1000;
        animation: slideDown 0.2s ease;
    }
    .alert-modal.open { display: block; }
    
    .alert-header {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--nav-hover);
        font-weight: 700; color: var(--nav-text-main);
        display: flex; justify-content: space-between; align-items: center;
    }
    .alert-body { max-height: 300px; overflow-y: auto; }
    .alert-item {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid var(--nav-hover);
        display: flex; align-items: flex-start; gap: 0.8rem;
        font-size: 0.85rem; color: #334155;
    }
    .alert-item:last-child { border-bottom: none; }
    .alert-empty { padding: 2rem; text-align: center; color: var(--nav-text-sub); }

    /* Separador */
    .separator { width: 1px; height: 24px; background: var(--nav-border); }
    
    /* Botão Mobile */
    .mobile-toggle { display: none; }

    /* Responsividade */
    @media (max-width: 768px) {
        .navbar { width: 100%; padding: 0 1rem; }
        .mobile-toggle { display: flex; }
        .user-text, .current-date, .separator { display: none; }
        .user-pill { padding: 0; border: none; }
        .user-pill:hover { background: transparent; }
        .alert-modal { right: 10px; left: 10px; width: auto; }
        .user-dropdown-menu { right: 10px; width: 200px; }
    }
</style>

<nav class="navbar">
    
    <div class="navbar-left">
        <button class="icon-btn mobile-toggle" id="sidebarToggleMobile">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="page-info">
            <h3 class="page-title">
                <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Painel de Controlo'; ?>
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
        
        <button class="icon-btn notification-btn" id="alertBell" title="Notificações">
            <i class="far fa-bell"></i>
            <?php if ($alertCount > 0): ?>
                <span class="alert-count"><?php echo $alertCount; ?></span>
            <?php endif; ?>
        </button>

        <div class="alert-modal" id="alertModal">
            <div class="alert-header">
                <span>Notificações</span>
                <button onclick="document.getElementById('alertModal').classList.remove('open')" style="background:none;border:none;cursor:pointer;color:#94a3b8;"><i class="fas fa-times"></i></button>
            </div>
            <div class="alert-body">
                <?php if ($alertCount === 0): ?>
                    <div class="alert-empty">
                        <i class="fas fa-check-circle" style="color:#10b981; font-size:1.5rem; margin-bottom:0.5rem;"></i><br>
                        Tudo certo! Sem alertas.
                    </div>
                <?php else: ?>
                    <?php foreach ($alertas as $a): ?>
                        <div class="alert-item">
                            <i class="fas <?php echo $a['icon']; ?>" style="color: <?php echo $a['color']; ?>; margin-top:3px;"></i>
                            <span><?php echo $a['text']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="separator"></div>

        <div class="user-pill" id="userMenuToggle">
            <div class="user-text">
                <span class="name"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                <span class="role"><?php echo ucfirst($currentUser['role'] ?? 'Utilizador'); ?></span>
            </div>
            <div class="user-avatar">
                <span><?php echo $initials; ?></span>
            </div>
        </div>

        <div class="user-dropdown-menu" id="userDropdownMenu">
            <div class="dropdown-header">
                <div class="user-avatar" style="width:32px;height:32px;font-size:0.8rem;"><?php echo $initials; ?></div>
                <div class="dropdown-info">
                    <span class="d-name"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                    <span class="d-role"><?php echo ucfirst($currentUser['role'] ?? 'Utilizador'); ?></span>
                </div>
            </div>
            
            <a href="<?php echo BASE_URL; ?>views/profile/" class="dropdown-item">
                <i class="fas fa-user-circle"></i> Meu Perfil
            </a>
            <a href="<?php echo BASE_URL; ?>views/settings/" class="dropdown-item">
                <i class="fas fa-cog"></i> Configurações
            </a>

            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>views/users/" class="dropdown-item">
                    <i class="fas fa-users-cog"></i> Usuários
                </a>
                <a href="<?php echo BASE_URL; ?>permissions_manager.php" class="dropdown-item">
                    <i class="fas fa-shield-alt"></i> Permissões
                </a>
            <?php endif; ?>

            <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item dropdown-logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Sidebar Toggle (Mobile e Desktop)
    const sideToggle = document.getElementById('sidebarToggleMobile');
    const sidebar = document.getElementById('appSidebar'); // ID da Sidebar
    if(sideToggle && sidebar) {
        sideToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            // Alterna entre colapsado e expandido
            sidebar.classList.toggle('is-collapsed');
        });
    }

    // 2. User Menu Dropdown
    const userToggle = document.getElementById('userMenuToggle');
    const userMenu = document.getElementById('userDropdownMenu');
    if(userToggle && userMenu) {
        userToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('open');
            // Fecha o modal de alertas se estiver aberto
            document.getElementById('alertModal')?.classList.remove('open');
        });
    }

    // 3. Alerts Modal
    const alertBtn = document.getElementById('alertBell');
    const alertModal = document.getElementById('alertModal');
    if(alertBtn && alertModal) {
        alertBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            alertModal.classList.toggle('open');
            // Fecha o menu de usuário se estiver aberto
            userMenu?.classList.remove('open');
        });
    }

    // 4. Fechar ao clicar fora
    document.addEventListener('click', (e) => {
        // Fechar Dropdown Usuario
        if(userMenu && userMenu.classList.contains('open') && !userMenu.contains(e.target)) {
            userMenu.classList.remove('open');
        }
        // Fechar Modal Alerta
        if(alertModal && alertModal.classList.contains('open') && !alertModal.contains(e.target)) {
            alertModal.classList.remove('open');
        }
        // Fechar Sidebar Mobile (remover show)
        if(window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show') && !sidebar.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    });
});
</script>