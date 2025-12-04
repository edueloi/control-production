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

<style>
    :root {
        --primary-color: #4f46e5;
        --primary-hover: #4338ca;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --bg-card: #ffffff;
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --border-color: #e5e7eb;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* Layout Geral */
    .page-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .btn-create {
        background-color: var(--primary-color);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        border: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .btn-create:hover {
        background-color: var(--primary-hover);
        transform: translateY(-1px);
    }

    /* Cards de Estatísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card-modern {
        background: var(--bg-card);
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s;
    }

    .stat-card-modern:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .bg-blue { background: #e0e7ff; color: #4f46e5; }
    .bg-green { background: #d1fae5; color: #10b981; }
    .bg-red { background: #fee2e2; color: #ef4444; }
    .bg-yellow { background: #fef3c7; color: #d97706; }

    .stat-info h5 { margin: 0; color: var(--text-muted); font-size: 0.875rem; font-weight: 500; }
    .stat-info .value { font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-top: 0.25rem; }

    /* Tabela Moderna */
    .table-container {
        background: var(--bg-card);
        border-radius: 1rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .table-header-styled {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .custom-table th {
        background: #f9fafb;
        text-align: left;
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 600;
        letter-spacing: 0.05em;
    }

    .custom-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
        vertical-align: middle;
    }

    .custom-table tr:last-child td { border-bottom: none; }
    .custom-table tr:hover { background-color: #f9fafb; }

    /* Componentes da Tabela */
    .user-info-cell { display: flex; align-items: center; gap: 1rem; }
    .avatar-circle {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: 1rem;
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .status-active { background: #d1fae5; color: #065f46; }
    .status-inactive { background: #fee2e2; color: #991b1b; }

    .role-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    .action-btn {
        padding: 0.5rem;
        border-radius: 0.375rem;
        border: none;
        background: transparent;
        cursor: pointer;
        color: var(--text-muted);
        transition: color 0.2s;
    }
    .action-btn:hover { color: var(--primary-color); background: #f3f4f6; }
    .action-btn.delete:hover { color: var(--danger-color); }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(2px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-content-modern {
        background: white;
        width: 100%;
        max-width: 600px;
        border-radius: 1rem;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 1.5rem; }
    .modal-footer { padding: 1.5rem; background: #f9fafb; border-top: 1px solid var(--border-color); border-radius: 0 0 1rem 1rem; display: flex; justify-content: flex-end; gap: 0.75rem; }

    /* Inputs Modernos */
    .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: var(--text-main); }
    .form-control-modern {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        transition: border-color 0.15s;
        font-size: 0.95rem;
    }
    .form-control-modern:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    
    .form-row-modern { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }

    @media (max-width: 768px) {
        .form-row-modern { grid-template-columns: 1fr; }
        .table-responsive { overflow-x: auto; }
        .user-info-cell { min-width: 200px; }
    }
</style>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header-flex">
                <div>
                    <h1 style="margin:0; font-size: 1.8rem; color: var(--text-main);">Gerenciar Usuários</h1>
                    <p style="margin: 0.5rem 0 0; color: var(--text-muted);">Administre o acesso e as permissões do sistema</p>
                </div>
                <button class="btn-create" onclick="openModal()">
                    <i class="fas fa-plus"></i> Novo Usuário
                </button>
            </div>

            <div class="stats-grid">
                <div class="stat-card-modern">
                    <div class="stat-icon-wrapper bg-blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h5>Total de Usuários</h5>
                        <div class="value"><?php echo $stats['total']; ?></div>
                    </div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-icon-wrapper bg-green">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h5>Ativos</h5>
                        <div class="value"><?php echo $stats['active']; ?></div>
                    </div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-icon-wrapper bg-red">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <h5>Inativos</h5>
                        <div class="value"><?php echo $stats['inactive']; ?></div>
                    </div>
                </div>

                <div class="stat-card-modern">
                    <div class="stat-icon-wrapper bg-yellow">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <h5>Administradores</h5>
                        <div class="value"><?php echo $stats['admins']; ?></div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header-styled">
                    <h3 style="margin:0; font-size:1.1rem;">Lista de Registros</h3>
                    </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Contato</th>
                                <th>Função</th>
                                <th>Status</th>
                                <th>Último Acesso</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info-cell">
                                            <div class="avatar-circle">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">ID: #<?php echo $user['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                        $roles = ['admin' => 'Admin', 'manager' => 'Gerente', 'user' => 'Usuário'];
                                        echo '<span class="role-badge">' . ($roles[$user['role']] ?? 'Usuário') . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-circle" style="font-size: 6px;"></i> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="fas fa-circle" style="font-size: 6px;"></i> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.9rem;">
                                        <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <button class="action-btn" onclick="window.location.href='activity_logs.php?user_id=<?php echo $user['id']; ?>'" title="Ver Logs">
                                            <i class="fas fa-history"></i>
                                        </button>

                                        <button class="action-btn" onclick='editUser(<?php echo json_encode($user); ?>)' title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <button class="action-btn" onclick="toggleStatus(<?php echo $user['id']; ?>, 'inactive')" title="Desativar">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn" onclick="toggleStatus(<?php echo $user['id']; ?>, 'active')" title="Ativar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="action-btn delete" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
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

<div id="userModal" class="modal-overlay">
    <div class="modal-content-modern">
        <div class="modal-header">
            <h3 style="margin:0;" id="modalTitle">Novo Usuário</h3>
            <button class="action-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="userForm">
            <div class="modal-body">
                <input type="hidden" id="userId">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nome Completo *</label>
                    <input type="text" id="name" class="form-control-modern" required placeholder="Ex: João Silva">
                </div>

                <div class="form-row-modern">
                    <div class="form-group">
                        <label>E-mail *</label>
                        <input type="email" id="email" class="form-control-modern" required placeholder="joao@email.com">
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" id="phone" class="form-control-modern" placeholder="(00) 00000-0000">
                    </div>
                </div>

                <div class="form-row-modern">
                    <div class="form-group">
                        <label>Senha <span id="passwordHint" style="font-weight: normal; color: #999; font-size: 0.8em;">*</span></label>
                        <input type="password" id="password" class="form-control-modern" minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Função *</label>
                        <select id="role" class="form-control-modern" required>
                            <option value="user">Usuário</option>
                            <option value="manager">Gerente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select id="status" class="form-control-modern" required>
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="border:1px solid #ddd; background:white; color:#333; padding: 0.6rem 1rem; border-radius: 0.5rem; cursor:pointer;" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn-create" style="box-shadow: none;">Salvar Usuário</button>
            </div>
        </form>
    </div>
</div>

<script>
let editingUserId = null;
const modal = document.getElementById('userModal');

// Funções do Modal
function openModal() {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Previne scroll no fundo
}

function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    limparFormulario();
}

// Fechar modal ao clicar fora
modal.addEventListener('click', function(e) {
    if (e.target === modal) closeModal();
});

// Envio do Formulário
document.getElementById('userForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validação básica de senha na criação
    const password = document.getElementById('password').value;
    if (!editingUserId && !password) {
        alert('A senha é obrigatória para novos usuários');
        return;
    }

    const data = {
        action: editingUserId ? 'update' : 'create',
        id: editingUserId,
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        password: password, // Se vazio na edição, o backend deve ignorar
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
            // Usa o Utils se existir, senão alert normal
            if(typeof Utils !== 'undefined') Utils.showAlert(result.message, 'success');
            else alert(result.message);
            
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            if(typeof Utils !== 'undefined') Utils.showAlert(result.message, 'error');
            else alert(result.message || 'Erro ao salvar');
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao processar requisição');
    }
});

function limparFormulario() {
    document.getElementById('userForm').reset();
    editingUserId = null;
    document.getElementById('modalTitle').innerText = 'Novo Usuário';
    document.getElementById('passwordHint').innerText = '*';
    document.getElementById('password').required = true;
}

// Preencher formulário para edição
function editUser(user) {
    editingUserId = user.id;
    
    document.getElementById('modalTitle').innerText = 'Editar Usuário';
    document.getElementById('userId').value = user.id;
    document.getElementById('name').value = user.name;
    document.getElementById('email').value = user.email;
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('role').value = user.role;
    document.getElementById('status').value = user.status;
    
    // Senha não é obrigatória na edição
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordHint').innerText = '(deixe em branco para manter)';
    
    openModal();
}

async function toggleStatus(id, newStatus) {
    if (!confirm(`Deseja realmente ${newStatus === 'active' ? 'ativar' : 'desativar'} este usuário?`)) return;
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/user_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_status', id, status: newStatus })
        });
        
        const result = await response.json();
        if (result.success) location.reload();
        else alert(result.message);
    } catch (error) {
        alert('Erro de conexão');
    }
}

async function deleteUser(id) {
    if (!confirm('Esta ação é irreversível. Deseja excluir?')) return;
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/user_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const result = await response.json();
        if (result.success) location.reload();
        else alert(result.message);
    } catch (error) {
        alert('Erro de conexão');
    }
}
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>