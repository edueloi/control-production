<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

// Verificar se é admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

$permissions = require __DIR__ . '/../../config/permissions.php';

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPermissions = [];
    foreach ($permissions as $role => $perms) {
        foreach ($perms as $perm => $value) {
            $newPermissions[$role][$perm] = isset($_POST[$role . '_' . $perm]) ? true : false;
        }
    }
    // Salva como PHP
    $content = "<?php\n// Permissões disponíveis no sistema\nreturn " . var_export($newPermissions, true) . ";\n";
    file_put_contents(__DIR__ . '/../../config/permissions.php', $content);
    
    setSuccessMessage('Permissões atualizadas com sucesso!');
    header('Location: ' . BASE_URL . 'views/permissions/');
    exit;
}

$roleLabels = [
    'admin' => 'Administrador',
    'employee' => 'Funcionário',
    'client' => 'Cliente',
    'user' => 'Usuário Padrão'
];

$permissionLabels = [
    'view_dashboard' => 'Ver Dashboard',
    'view_all_products' => 'Ver Todos os Produtos',
    'edit_all_products' => 'Editar Todos os Produtos',
    'view_own_products' => 'Ver Próprios Produtos',
    'edit_own_products' => 'Editar Próprios Produtos',
    'view_all_clients' => 'Ver Todos os Clientes',
    'edit_all_clients' => 'Editar Todos os Clientes',
    'view_own_clients' => 'Ver Próprios Clientes',
    'edit_own_clients' => 'Editar Próprios Clientes',
    'view_all_sales' => 'Ver Todas as Vendas',
    'edit_all_sales' => 'Editar Todas as Vendas',
    'view_own_sales' => 'Ver Próprias Vendas',
    'edit_own_sales' => 'Editar Próprias Vendas',
    'view_all_stock' => 'Ver Todo o Estoque',
    'edit_all_stock' => 'Editar Todo o Estoque',
    'view_own_stock' => 'Ver Próprio Estoque',
    'edit_own_stock' => 'Editar Próprio Estoque',
    'view_all_production' => 'Ver Todas as Produções',
    'edit_all_production' => 'Editar Todas as Produções',
    'view_own_production' => 'Ver Próprias Produções',
    'edit_own_production' => 'Editar Próprias Produções',
    'view_all_pdv' => 'Ver Todas as Vendas PDV',
    'edit_all_pdv' => 'Editar Todas as Vendas PDV',
    'view_own_pdv' => 'Ver Próprias Vendas PDV',
    'edit_own_pdv' => 'Editar Próprias Vendas PDV',
    'view_reports' => 'Ver Relatórios',
    'manage_users' => 'Gerenciar Usuários',
    'manage_permissions' => 'Gerenciar Permissões',
    'view_settings' => 'Ver Configurações',
    'view_profile' => 'Ver Perfil',
    'view_own_data' => 'Ver Próprios Dados',
    'edit_own_data' => 'Editar Próprios Dados'
];
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<style>
    :root {
        --primary-color: #4f46e5;
        --bg-card: #ffffff;
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --border-color: #e5e7eb;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .permissions-container {
        padding: 2rem;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .page-header h1 {
        color: var(--text-main);
        font-size: 1.8rem;
        margin: 0 0 0.5rem 0;
    }

    .page-header p {
        color: var(--text-muted);
        margin: 0;
    }

    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .role-card {
        background: var(--bg-card);
        border-radius: 1rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .role-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1.5rem;
        color: white;
    }

    .role-card-header.admin {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .role-card-header.employee {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .role-card-header.client {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .role-card-header.user {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .role-card-header h3 {
        margin: 0;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .role-card-body {
        padding: 1.5rem;
    }

    .permission-group {
        margin-bottom: 1.5rem;
    }

    .permission-group-title {
        font-weight: 600;
        color: var(--text-main);
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--border-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .permission-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
    }

    .permission-item:hover {
        background: #f9fafb;
    }

    .permission-label {
        color: var(--text-main);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .permission-label i {
        color: var(--text-muted);
        width: 20px;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 24px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }

    input:checked + .toggle-slider {
        background-color: #4f46e5;
    }

    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }

    .btn-save {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 1rem 2.5rem;
        border-radius: 0.75rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
    }

    .btn-save:hover {
        background: #3730a3;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(79, 70, 229, 0.4);
    }

    .actions-bar {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: center;
        box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="permissions-container">
                <div class="page-header">
                    <h1><i class="fas fa-user-shield" style="color: var(--primary-color);"></i> Gerenciar Permissões</h1>
                    <p>Configure as permissões de acesso para cada tipo de usuário do sistema.</p>
                </div>

                <form method="post">
                    <div class="roles-grid">
                        <?php foreach ($permissions as $role => $perms): ?>
                            <div class="role-card">
                                <div class="role-card-header <?= $role ?>">
                                    <h3>
                                        <?php
                                        $icons = [
                                            'admin' => 'fa-crown',
                                            'employee' => 'fa-user-tie',
                                            'client' => 'fa-user',
                                            'user' => 'fa-user-circle'
                                        ];
                                        ?>
                                        <i class="fas <?= $icons[$role] ?? 'fa-user' ?>"></i>
                                        <?= $roleLabels[$role] ?? ucfirst($role) ?>
                                    </h3>
                                </div>
                                <div class="role-card-body">
                                    <?php 
                                    // Agrupar permissões por categoria
                                    $groups = [
                                        'Dashboard' => ['view_dashboard'],
                                        'Produtos' => ['view_all_products', 'edit_all_products', 'view_own_products', 'edit_own_products'],
                                        'Clientes' => ['view_all_clients', 'edit_all_clients', 'view_own_clients', 'edit_own_clients'],
                                        'Vendas' => ['view_all_sales', 'edit_all_sales', 'view_own_sales', 'edit_own_sales'],
                                        'Estoque' => ['view_all_stock', 'edit_all_stock', 'view_own_stock', 'edit_own_stock'],
                                        'Produção' => ['view_all_production', 'edit_all_production', 'view_own_production', 'edit_own_production'],
                                        'PDV' => ['view_all_pdv', 'edit_all_pdv', 'view_own_pdv', 'edit_own_pdv'],
                                        'Sistema' => ['view_reports', 'manage_users', 'manage_permissions', 'view_settings', 'view_profile', 'view_own_data', 'edit_own_data']
                                    ];

                                    foreach ($groups as $groupName => $groupPerms):
                                        $hasPerms = false;
                                        foreach ($groupPerms as $gp) {
                                            if (isset($perms[$gp])) {
                                                $hasPerms = true;
                                                break;
                                            }
                                        }
                                        if (!$hasPerms) continue;
                                    ?>
                                        <div class="permission-group">
                                            <div class="permission-group-title"><?= $groupName ?></div>
                                            <?php foreach ($groupPerms as $perm): ?>
                                                <?php if (isset($perms[$perm])): ?>
                                                    <div class="permission-item">
                                                        <label class="permission-label">
                                                            <i class="fas fa-check-circle"></i>
                                                            <?= $permissionLabels[$perm] ?? $perm ?>
                                                        </label>
                                                        <label class="toggle-switch">
                                                            <input type="checkbox" 
                                                                   name="<?= $role . '_' . $perm ?>" 
                                                                   <?= $perms[$perm] ? 'checked' : '' ?>>
                                                            <span class="toggle-slider"></span>
                                                        </label>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="actions-bar">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Salvar Todas as Permissões
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../../components/footer.php'; ?>
