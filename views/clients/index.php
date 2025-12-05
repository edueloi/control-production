<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Clientes';
$db = Database::getInstance()->getConnection();

// Buscar todos os clientes do usuário
$userFilter = getUserFilter();
$clients = $db->query("SELECT * FROM clients WHERE $userFilter ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Gestão de Clientes</h1>
                <p>Cadastre e gerencie seus clientes</p>
            </div>
            
            <!-- Estatísticas -->
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Total de Clientes</h4>
                        <div class="value"><?php echo count($clients); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Pessoa Física</h4>
                        <div class="value">
                            <?php echo count(array_filter($clients, fn($c) => $c['type'] === 'physical')); ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <h4>Pessoa Jurídica</h4>
                        <div class="value">
                            <?php echo count(array_filter($clients, fn($c) => $c['type'] === 'legal')); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulário de Cadastro -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> Cadastrar Novo Cliente</h3>
                    <button class="btn btn-secondary btn-sm" onclick="limparFormulario()">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>
                <form id="clientForm">
                    <input type="hidden" id="client_id" name="id">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-user-tag"></i>
                            Tipo de Cliente *
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="type_physical" name="type" value="physical" checked onchange="toggleClientType()">
                                <label for="type_physical">Pessoa Física</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="type_legal" name="type" value="legal" onchange="toggleClientType()">
                                <label for="type_legal">Pessoa Jurídica</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-signature"></i>
                                <span id="nameLabel">Nome Completo *</span>
                            </label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group" id="cpfGroup">
                            <label for="cpf">
                                <i class="fas fa-id-card"></i>
                                CPF *
                            </label>
                            <input type="text" id="cpf" name="cpf" data-mask="cpf" maxlength="14">
                        </div>
                        <div class="form-group hidden" id="cnpjGroup">
                            <label for="cnpj">
                                <i class="fas fa-building"></i>
                                CNPJ *
                            </label>
                            <input type="text" id="cnpj" name="cnpj" data-mask="cnpj" maxlength="18">
                        </div>
                    </div>
                    
                    <div class="form-group hidden" id="companyNameGroup">
                        <label for="company_name">
                            <i class="fas fa-store"></i>
                            Nome Fantasia
                        </label>
                        <input type="text" id="company_name" name="company_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            E-mail *
                        </label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                Telefone
                            </label>
                            <input type="text" id="phone" name="phone" data-mask="phone" maxlength="15">
                        </div>
                        <div class="form-group">
                            <label for="whatsapp">
                                <i class="fab fa-whatsapp"></i>
                                WhatsApp
                            </label>
                            <input type="text" id="whatsapp" name="whatsapp" data-mask="phone" maxlength="15">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">
                            <i class="fas fa-map-marker-alt"></i>
                            Endereço Completo *
                        </label>
                        <textarea id="address" name="address" rows="3" required></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Cliente
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="limparFormulario()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Busca -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Buscar Clientes</h3>
                </div>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Digite para buscar...">
                    <select id="searchType">
                        <option value="all">Todos</option>
                        <option value="name">Nome</option>
                        <option value="cpf">CPF</option>
                        <option value="cnpj">CNPJ</option>
                    </select>
                    <button class="btn btn-info" onclick="buscarClientes()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            
            <!-- Lista de Clientes -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Clientes Cadastrados</h3>
                    <span class="badge badge-info"><?php echo count($clients); ?> clientes</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>CPF/CNPJ</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($client['name']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $client['type'] === 'physical' ? 'success' : 'info'; ?>">
                                            <?php echo $client['type'] === 'physical' ? 'Pessoa Física' : 'Pessoa Jurídica'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($client['cpf'] ?? $client['cnpj'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                    <td><?php echo htmlspecialchars($client['whatsapp'] ?? $client['phone'] ?? '-'); ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm" onclick='editarCliente(<?php echo json_encode($client); ?>)' title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="excluirCliente(<?php echo $client['id']; ?>)" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
function toggleClientType() {
    const isLegal = document.getElementById('type_legal').checked;
    
    document.getElementById('nameLabel').textContent = isLegal ? 'Razão Social *' : 'Nome Completo *';
    
    document.getElementById('cpfGroup').classList.toggle('hidden', isLegal);
    document.getElementById('cnpjGroup').classList.toggle('hidden', !isLegal);
    document.getElementById('companyNameGroup').classList.toggle('hidden', !isLegal);
    
    document.getElementById('cpf').required = !isLegal;
    document.getElementById('cnpj').required = isLegal;
}

document.getElementById('clientForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const clientId = document.getElementById('client_id').value;
    
    if (clientId) {
        formData.set('action', 'update');
    }
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/client_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert('Cliente salvo com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert('Erro ao salvar cliente: ' + result.message, 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao salvar cliente', 'error');
    }
});

function editarCliente(client) {
    document.getElementById('client_id').value = client.id;
    document.getElementById('name').value = client.name;
    document.getElementById('email').value = client.email;
    document.getElementById('phone').value = client.phone || '';
    document.getElementById('whatsapp').value = client.whatsapp || '';
    document.getElementById('address').value = client.address;
    
    if (client.type === 'legal') {
        document.getElementById('type_legal').checked = true;
        document.getElementById('cnpj').value = client.cnpj || '';
        document.getElementById('company_name').value = client.company_name || '';
    } else {
        document.getElementById('type_physical').checked = true;
        document.getElementById('cpf').value = client.cpf || '';
    }
    
    toggleClientType();
    document.querySelector('input[name="action"]').value = 'update';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function excluirCliente(id) {
    if (!confirm('Deseja realmente excluir este cliente?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/client_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert('Cliente excluído com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert('Erro ao excluir cliente', 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao excluir cliente', 'error');
    }
}

function limparFormulario() {
    document.getElementById('clientForm').reset();
    document.getElementById('client_id').value = '';
    document.getElementById('type_physical').checked = true;
    toggleClientType();
    document.querySelector('input[name="action"]').value = 'create';
}

function buscarClientes() {
    location.reload();
}
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
