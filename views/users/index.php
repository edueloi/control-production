<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

// Verificar se é admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'Acesso negado! Apenas administradores podem acessar esta página.';
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

$pageTitle = 'Gerenciar Usuários';
$db = Database::getInstance()->getConnection();

// Buscar todos os usuários
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas
$stats = [
    'total' => count($users),
    'active' => $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'inactive' => $db->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn(),
    'admins' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn()
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
                <h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>
                <p>Controle total sobre os usuários do sistema</p>
            </div>

            <!-- Estatísticas -->
            <div class="cards-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Usuários</h4>
                        <div class="value"><?php echo $stats['total']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Usuários Ativos</h4>
                        <div class="value"><?php echo $stats['active']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Usuários Inativos</h4>
                        <div class="value"><?php echo $stats['inactive']; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Administradores</h4>
                        <div class="value"><?php echo $stats['admins']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Formulário de Novo Usuário -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> Cadastrar Novo Usuário</h3>
                    <button class="btn btn-secondary btn-sm" onclick="limparFormulario()">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>

                <form id="userForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nome Completo *</label>
                            <input type="text" id="name" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> E-mail *</label>
                            <input type="email" id="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telefone</label>
                            <input type="text" id="phone" placeholder="(00) 00000-0000">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Senha *</label>
                            <input type="password" id="password" required minlength="6">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> Função *</label>
                            <select id="role" required>
                                <option value="user">Usuário</option>
                                <option value="admin">Administrador</option>
                                <option value="manager">Gerente</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Status *</label>
                            <select id="status" required>
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Usuário
                    </button>
                </form>
            </div>

            <!-- Lista de Usuários -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Lista de Usuários</h3>
                    <span class="badge badge-info"><?php echo count($users); ?> usuários</span>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Função</th>
                                <th>Status</th>
                                <th>Último Login</th>
                                <th>Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong>#<?php echo $user['id']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #3b82f6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <?php 
                                        $roleColors = [
                                            'admin' => 'danger',
                                            'manager' => 'warning',
                                            'user' => 'info'
                                        ];
                                        $roleLabels = [
                                            'admin' => 'Admin',
                                            'manager' => 'Gerente',
                                            'user' => 'Usuário'
                                        ];
                                        $roleColor = $roleColors[$user['role']] ?? 'info';
                                        $roleLabel = $roleLabels[$user['role']] ?? 'Usuário';
                                        ?>
                                        <span class="badge badge-<?php echo $roleColor; ?>">
                                            <i class="fas fa-user-shield"></i> <?php echo $roleLabel; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times-circle"></i> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($user['last_login']) {
                                            echo date('d/m/Y H:i', strtotime($user['last_login']));
                                        } else {
                                            echo '<span style="color: var(--text-muted);">Nunca</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-info btn-sm" onclick="editUser(<?php echo $user['id']; ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($user['status'] === 'active'): ?>
                                                <button class="btn btn-warning btn-sm" onclick="toggleStatus(<?php echo $user['id']; ?>, 'inactive')" title="Desativar">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-sm" onclick="toggleStatus(<?php echo $user['id']; ?>, 'active')" title="Ativar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-secondary btn-sm" onclick="viewLogs(<?php echo $user['id']; ?>)" title="Ver Logs">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let editingUserId = null;

document.getElementById('userForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const data = {
        action: editingUserId ? 'update' : 'create',
        id: editingUserId,
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        password: document.getElementById('password').value,
        role: document.getElementById('role').value,
        status: document.getElementById('status').value
    };
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/user_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert(result.message, 'success');
            limparFormulario();
            setTimeout(() => location.reload(), 1500);
        } else {
            Utils.showAlert(result.message, 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao processar requisição', 'error');
    }
});

function limparFormulario() {
    document.getElementById('userForm').reset();
    editingUserId = null;
}

function editUser(id) {
    // Implementar edição
    Utils.showAlert('Funcionalidade em desenvolvimento', 'info');
}

async function toggleStatus(id, newStatus) {
    if (!confirm(`Deseja realmente ${newStatus === 'active' ? 'ativar' : 'desativar'} este usuário?`)) {
        return;
    }
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/user_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_status', id, status: newStatus })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert(result.message, 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao processar requisição', 'error');
    }
}

async function deleteUser(id) {
    if (!confirm('Deseja realmente excluir este usuário? Esta ação não pode ser desfeita!')) {
        return;
    }
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/user_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert(result.message, 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao processar requisição', 'error');
    }
}

function viewLogs(id) {
    // Implementar visualização de logs
    Utils.showAlert('Funcionalidade em desenvolvimento', 'info');
}
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
