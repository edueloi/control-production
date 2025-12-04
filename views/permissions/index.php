<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

// Verificar se é admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

// Carregar permissões (ajuste o caminho se necessário)
$permissionsPath = __DIR__ . '/../../config/permissions.php';
$permissions = file_exists($permissionsPath) ? require $permissionsPath : [];

// Definição dos Grupos (para organização visual)
$groups = [
    'Acesso Geral' => ['view_dashboard'],
    'Produtos' => ['view_all_products', 'edit_all_products', 'view_own_products', 'edit_own_products'],
    'Clientes' => ['view_all_clients', 'edit_all_clients', 'view_own_clients', 'edit_own_clients'],
    'Vendas' => ['view_all_sales', 'edit_all_sales', 'view_own_sales', 'edit_own_sales'],
    'Estoque' => ['view_all_stock', 'edit_all_stock', 'view_own_stock', 'edit_own_stock'],
    'Produção' => ['view_all_production', 'edit_all_production', 'view_own_production', 'edit_own_production'],
    'PDV' => ['view_all_pdv', 'edit_all_pdv', 'view_own_pdv', 'edit_own_pdv'],
    'Administrativo' => ['view_reports', 'manage_users', 'manage_permissions', 'view_settings', 'view_profile', 'view_own_data', 'edit_own_data']
];

// Se o arquivo de permissões estiver vazio ou incompleto, inicializa com padrão
if (empty($permissions)) {
    $roles = ['admin', 'employee', 'client', 'user'];
    foreach ($roles as $role) {
        foreach ($groups as $groupPerms) {
            foreach ($groupPerms as $perm) {
                // Admin tem tudo true, outros false por padrão
                $permissions[$role][$perm] = ($role === 'admin');
            }
        }
    }
}

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPermissions = [];
    foreach ($permissions as $role => $perms) {
        foreach ($groups as $groupPerms) {
            foreach ($groupPerms as $perm) {
                // Verifica se o checkbox foi enviado
                $newPermissions[$role][$perm] = isset($_POST[$role . '_' . $perm]);
            }
        }
    }
    
    // Salva como arquivo PHP retornando array
    $content = "<?php\nreturn " . var_export($newPermissions, true) . ";\n";
    file_put_contents($permissionsPath, $content);
    
    // Feedback visual (pode usar sua função setSuccessMessage se existir)
    $successMessage = "Permissões atualizadas com sucesso!";
}

$roleLabels = [
    'admin' => 'Administrador',
    'employee' => 'Funcionário',
    'client' => 'Cliente',
    'user' => 'Usuário Padrão'
];

$roleIcons = [
    'admin' => 'fa-crown',
    'employee' => 'fa-briefcase',
    'client' => 'fa-user-tag',
    'user' => 'fa-user'
];

$permissionLabels = [
    'view_dashboard' => 'Acessar Dashboard',
    'view_all_products' => 'Ver Todos', 'edit_all_products' => 'Editar Todos',
    'view_own_products' => 'Ver Próprios', 'edit_own_products' => 'Editar Próprios',
    'view_all_clients' => 'Ver Todos', 'edit_all_clients' => 'Editar Todos',
    'view_own_clients' => 'Ver Próprios', 'edit_own_clients' => 'Editar Próprios',
    'view_all_sales' => 'Ver Todas', 'edit_all_sales' => 'Editar Todas',
    'view_own_sales' => 'Ver Próprias', 'edit_own_sales' => 'Editar Próprias',
    'view_all_stock' => 'Ver Geral', 'edit_all_stock' => 'Editar Geral',
    'view_own_stock' => 'Ver Próprio', 'edit_own_stock' => 'Editar Próprio',
    'view_all_production' => 'Ver Todas', 'edit_all_production' => 'Editar Todas',
    'view_own_production' => 'Ver Próprias', 'edit_own_production' => 'Editar Próprias',
    'view_all_pdv' => 'PDV Geral', 'edit_all_pdv' => 'Gerenciar PDV',
    'view_own_pdv' => 'PDV Pessoal', 'edit_own_pdv' => 'Gerenciar PDV Pessoal',
    'view_reports' => 'Ver Relatórios',
    'manage_users' => 'Gerenciar Usuários',
    'manage_permissions' => 'Gerenciar Permissões',
    'view_settings' => 'Configurações',
    'view_profile' => 'Ver Perfil',
    'view_own_data' => 'Seus Dados', 'edit_own_data' => 'Editar Seus Dados'
];
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<style>
    :root {
        --primary-color: #4f46e5;
        --success-color: #10b981;
        --bg-body: #f3f4f6;
        --bg-card: #ffffff;
        --text-main: #111827;
        --text-muted: #6b7280;
        --border-color: #e5e7eb;
    }

    /* Layout Geral */
    .permissions-container {
        padding-bottom: 80px; /* Espaço para a barra de ação fixa */
    }

    .page-header-flex {
        display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;
    }
    .page-title h1 { font-size: 1.75rem; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.75rem; }
    .page-title p { color: var(--text-muted); margin-top: 0.5rem; font-size: 0.95rem; }

    /* Grid de Cartões */
    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    /* Cartão de Função */
    .role-card {
        background: var(--bg-card);
        border-radius: 1rem;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex; flex-direction: column;
    }
    .role-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }

    /* Cabeçalho do Cartão */
    .role-header {
        padding: 1.5rem;
        color: white;
        display: flex; align-items: center; gap: 1rem;
    }
    .role-header h2 { margin: 0; font-size: 1.25rem; font-weight: 600; }
    .role-icon {
        width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }

    /* Cores dos Cabeçalhos */
    .header-admin { background: linear-gradient(135deg, #4f46e5, #4338ca); }
    .header-employee { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .header-client { background: linear-gradient(135deg, #10b981, #059669); }
    .header-user { background: linear-gradient(135deg, #f59e0b, #d97706); }

    /* Corpo do Cartão */
    .role-body { padding: 1.5rem; flex: 1; }

    /* Grupos de Permissão */
    .perm-group {
        background: #f8fafc;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
    }
    .perm-group-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 0.75rem; padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .perm-group-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
    
    .btn-toggle-all {
        background: none; border: none; font-size: 0.75rem; color: var(--primary-color);
        cursor: pointer; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 4px;
        transition: background 0.2s;
    }
    .btn-toggle-all:hover { background: #e0e7ff; }

    /* Itens de Permissão */
    .perm-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.5rem 0;
    }
    .perm-item:not(:last-child) { border-bottom: 1px solid #f1f5f9; }
    .perm-label { font-size: 0.9rem; color: var(--text-main); font-weight: 500; }

    /* Toggle Switch (iOS Style) */
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1; transition: .3s; border-radius: 24px;
    }
    .slider:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background-color: white;
        transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    input:checked + .slider { background-color: var(--success-color); }
    input:checked + .slider:before { transform: translateX(20px); }
    input:disabled + .slider { opacity: 0.5; cursor: not-allowed; }

    /* Barra de Ação Fixa */
    .action-bar {
        position: fixed; bottom: 0; left: 260px; /* Largura da sidebar */
        right: 0; background: white; padding: 1rem 2rem;
        border-top: 1px solid var(--border-color);
        box-shadow: 0 -4px 20px rgba(0,0,0,0.05);
        display: flex; justify-content: flex-end; align-items: center; gap: 1rem;
        z-index: 100;
    }
    .btn-save {
        background: var(--primary-color); color: white; border: none;
        padding: 0.75rem 2rem; border-radius: 0.5rem; font-weight: 600;
        font-size: 1rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;
        transition: background 0.2s; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
    }
    .btn-save:hover { background: #4338ca; transform: translateY(-1px); }

    /* Responsividade */
    @media (max-width: 768px) {
        .action-bar { left: 0; padding: 1rem; justify-content: center; }
        .roles-grid { grid-template-columns: 1fr; }
        .page-header-flex { flex-direction: column; align-items: flex-start; gap: 1rem; }
    }
</style>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>

            <?php if (isset($successMessage)): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle"></i> <?= $successMessage ?>
                </div>
            <?php endif; ?>

            <div class="permissions-container">
                <div class="page-header-flex">
                    <div class="page-title">
                        <h1><i class="fas fa-user-shield" style="color: var(--primary-color);"></i> Gerenciar Permissões</h1>
                        <p>Defina o que cada tipo de usuário pode acessar ou modificar no sistema.</p>
                    </div>
                </div>

                <form method="post" id="permissionsForm">
                    <div class="roles-grid">
                        <?php foreach ($permissions as $role => $perms): ?>
                            <div class="role-card">
                                <div class="role-header header-<?= $role ?>">
                                    <div class="role-icon">
                                        <i class="fas <?= $roleIcons[$role] ?? 'fa-user' ?>"></i>
                                    </div>
                                    <h2><?= $roleLabels[$role] ?? ucfirst($role) ?></h2>
                                </div>
                                
                                <div class="role-body">
                                    <?php foreach ($groups as $groupName => $groupPerms): 
                                        // Verifica se o grupo tem permissões válidas para exibir
                                        $hasPerms = false;
                                        foreach ($groupPerms as $gp) if(isset($perms[$gp])) $hasPerms = true;
                                        if (!$hasPerms) continue;
                                    ?>
                                        <div class="perm-group" id="group-<?= $role ?>-<?= str_replace(' ', '', $groupName) ?>">
                                            <div class="perm-group-header">
                                                <span class="perm-group-title"><?= $groupName ?></span>
                                                <button type="button" class="btn-toggle-all" onclick="toggleGroup('<?= $role ?>', '<?= str_replace(' ', '', $groupName) ?>')">
                                                    Alternar Tudo
                                                </button>
                                            </div>
                                            
                                            <?php foreach ($groupPerms as $perm): 
                                                if (!isset($perms[$perm])) continue;
                                            ?>
                                                <div class="perm-item">
                                                    <span class="perm-label">
                                                        <?= $permissionLabels[$perm] ?? $perm ?>
                                                    </span>
                                                    <label class="switch">
                                                        <input type="checkbox" 
                                                               name="<?= $role ?>_<?= $perm ?>" 
                                                               <?= $perms[$perm] ? 'checked' : '' ?>
                                                               class="perm-checkbox group-<?= $role ?>-<?= str_replace(' ', '', $groupName) ?>">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="action-bar">
                        <span style="font-size: 0.9rem; color: #64748b; margin-right: auto;">
                            <i class="fas fa-info-circle"></i> As alterações são aplicadas imediatamente após salvar.
                        </span>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Salvar Permissões
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
    // Função para marcar/desmarcar todo um grupo
    function toggleGroup(role, groupName) {
        const checkboxes = document.querySelectorAll(`.group-${role}-${groupName}`);
        
        // Verifica se todos já estão marcados para decidir se marca ou desmarca
        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });

        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
    }
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>