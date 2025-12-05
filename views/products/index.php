<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Produtos';
$db = Database::getInstance()->getConnection();

// Buscar todos os produtos do usuário
$userFilter = getUserFilter();
$products = $db->query("SELECT * FROM products WHERE $userFilter ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-box"></i> Gestão de Produtos</h1>
                <p>Cadastre e gerencie seus produtos</p>
            </div>
            
            <!-- Formulário de Cadastro -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Cadastrar Novo Produto</h3>
                    <button class="btn btn-secondary btn-sm" onclick="limparFormulario()">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>
                <form id="productForm" enctype="multipart/form-data">
                    <input type="hidden" id="product_id" name="id">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="barcode">
                                <i class="fas fa-barcode"></i>
                                Código de Barras *
                            </label>
                            <input type="text" id="barcode" name="barcode" required>
                        </div>
                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-tag"></i>
                                Descrição *
                            </label>
                            <input type="text" id="description" name="description" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cost">
                                <i class="fas fa-dollar-sign"></i>
                                Custo (R$) *
                            </label>
                            <input type="number" id="cost" name="cost" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="price">
                                <i class="fas fa-money-bill"></i>
                                Preço de Venda (R$) *
                            </label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stock">
                                <i class="fas fa-boxes"></i>
                                Estoque Inicial *
                            </label>
                            <input type="number" id="stock" name="stock" step="0.01" min="0" value="0" required>
                        </div>
                        <div class="form-group">
                            <label for="min_stock">
                                <i class="fas fa-exclamation-triangle"></i>
                                Estoque Mínimo *
                            </label>
                            <input type="number" id="min_stock" name="min_stock" step="0.01" min="0" value="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="unit">
                                <i class="fas fa-weight"></i>
                                Embalagem *
                            </label>
                            <select id="unit" name="unit" required>
                                <option value="un">UN - Unidade</option>
                                <option value="kg">KG - Quilograma</option>
                                <option value="l">L - Litro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="type">
                                <i class="fas fa-layer-group"></i>
                                Tipo de Produto *
                            </label>
                            <select id="type" name="type" required>
                                <option value="finished">Produto Acabado</option>
                                <option value="intermediate">Produto Intermediário</option>
                                <option value="supply">Insumo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">
                            <i class="fas fa-image"></i>
                            Imagem do Produto (opcional)
                        </label>
                        <div id="image-uploader" class="image-uploader">
                            <div class="uploader-instructions">
                                <i class="fas fa-upload"></i>
                                <p>Arraste e solte uma imagem aqui, cole, ou clique para selecionar.</p>
                            </div>
                            <div class="image-preview" style="display: none;">
                                <img id="preview-img" src="#">
                                <div class="image-actions">
                                    <button type="button" id="copy-image-btn" class="action-btn" title="Copiar link da imagem"><i class="fas fa-copy"></i></button>
                                    <button type="button" id="remove-image-btn" class="action-btn" title="Remover imagem"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Produto
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="limparFormulario()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Busca de Produtos -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Buscar Produtos</h3>
                </div>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Digite para buscar...">
                    <select id="searchType">
                        <option value="all">Todos</option>
                        <option value="barcode">Código de Barras</option>
                        <option value="description">Descrição</option>
                    </select>
                    <button class="btn btn-info" onclick="buscarProdutos()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            
            <!-- Lista de Produtos -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Produtos Cadastrados</h3>
                    <span class="badge badge-info" id="totalProducts"><?php echo count($products); ?> produtos</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Imagem</th>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th>Unidade</th>
                                <th>Custo</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo BASE_URL . $product['image']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($product['description']); ?></strong></td>
                                    <td>
                                        <?php
                                        $types = ['finished' => 'Acabado', 'intermediate' => 'Intermediário', 'supply' => 'Insumo'];
                                        $badges = ['finished' => 'success', 'intermediate' => 'info', 'supply' => 'warning'];
                                        ?>
                                        <span class="badge badge-<?php echo $badges[$product['type']]; ?>">
                                            <?php echo $types[$product['type']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo strtoupper($product['unit']); ?></td>
                                    <td><?php echo formatMoney($product['cost']); ?></td>
                                    <td><?php echo formatMoney($product['price']); ?></td>
                                    <td>
                                        <?php
                                        $stockClass = $product['stock'] <= $product['min_stock'] ? 'badge-danger' : 'badge-success';
                                        ?>
                                        <span class="badge <?php echo $stockClass; ?>">
                                            <?php echo number_format($product['stock'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm" onclick='editarProduto(<?php echo json_encode($product); ?>)' title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="excluirProduto(<?php echo $product['id']; ?>)" title="Excluir">
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

<style>
.image-uploader {
    position: relative;
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    background: #f8f9fa;
    transition: background 0.2s, border-color 0.2s;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.image-uploader:hover, .image-uploader.dragover {
    background: #e9ecef;
    border-color: #3b82f6;
}
.uploader-instructions {
    color: #6c757d;
}
.uploader-instructions i {
    font-size: 40px;
    margin-bottom: 10px;
    display: block;
}
.image-preview {
    position: relative;
    width: 100%;
    height: 100%;
}
.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 5px;
}
.image-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 10px;
}
.action-btn {
    background: rgba(0,0,0,0.6);
    color: white;
    border: none;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: background 0.2s;
}
.action-btn:hover {
    background: rgba(0,0,0,0.8);
}
</style>

<script>
// Novo componente de upload de imagem
const uploader = document.getElementById('image-uploader');
const fileInput = document.getElementById('image');
const instructions = uploader.querySelector('.uploader-instructions');
const preview = uploader.querySelector('.image-preview');
const previewImg = document.getElementById('preview-img');
const removeBtn = document.getElementById('remove-image-btn');
const copyBtn = document.getElementById('copy-image-btn');

// Abrir seletor de arquivo ao clicar
uploader.addEventListener('click', (e) => {
    if (e.target !== removeBtn && e.target.parentElement !== removeBtn && e.target !== copyBtn && e.target.parentElement !== copyBtn) {
        fileInput.click();
    }
});

// Highlight ao arrastar
uploader.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploader.classList.add('dragover');
});
uploader.addEventListener('dragleave', () => {
    uploader.classList.remove('dragover');
});

// Lidar com o drop
uploader.addEventListener('drop', (e) => {
    e.preventDefault();
    uploader.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFile(files[0]);
    }
});

// Lidar com o paste
document.addEventListener('paste', (e) => {
    const items = e.clipboardData.items;
    for (const item of items) {
        if (item.type.indexOf('image') !== -1) {
            const file = item.getAsFile();
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            handleFile(file);
            break;
        }
    }
});


// Lidar com a seleção do arquivo
fileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        handleFile(file);
    }
});

function handleFile(file) {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            instructions.style.display = 'none';
            preview.style.display = 'block';
            copyBtn.style.display = 'none'; // Oculta o botão de cópia para novas imagens
        };
        reader.readAsDataURL(file);
    }
}

// Remover imagem
removeBtn.addEventListener('click', () => {
    fileInput.value = ''; // Limpa o input
    previewImg.src = '#';
    preview.style.display = 'none';
    instructions.style.display = 'block';
});

// Copiar link da imagem
copyBtn.addEventListener('click', () => {
    const imageUrl = previewImg.src;
    if (imageUrl && !imageUrl.startsWith('data:')) {
        navigator.clipboard.writeText(imageUrl).then(() => {
            Utils.showAlert('Link da imagem copiado!', 'success');
        }).catch(err => {
            Utils.showAlert('Falha ao copiar o link.', 'error');
        });
    }
});

// Salvar produto
document.getElementById('productForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const productId = document.getElementById('product_id').value;
    
    if (productId) {
        formData.set('action', 'update');
    }

    // Se a imagem foi removida no frontend, precisamos informar o backend
    if (preview.style.display === 'none') {
        formData.append('remove_image', 'true');
    }
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/product_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert('Produto salvo com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert('Erro ao salvar produto: ' + result.message, 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao salvar produto', 'error');
    }
});

// Editar produto
function editarProduto(product) {
    document.getElementById('product_id').value = product.id;
    document.getElementById('barcode').value = product.barcode;
    document.getElementById('description').value = product.description;
    document.getElementById('cost').value = product.cost;
    document.getElementById('price').value = product.price;
    document.getElementById('stock').value = product.stock;
    document.getElementById('min_stock').value = product.min_stock;
    document.getElementById('unit').value = product.unit;
    document.getElementById('type').value = product.type;
    
    if (product.image) {
        previewImg.src = '<?php echo BASE_URL; ?>' + product.image;
        instructions.style.display = 'none';
        preview.style.display = 'block';
        copyBtn.style.display = 'block'; // Mostra o botão de cópia para imagens existentes
    } else {
        limparFormularioImagem();
    }
    
    document.querySelector('input[name="action"]').value = 'update';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Excluir produto
async function excluirProduto(id) {
    if (!confirm('Deseja realmente excluir este produto?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/product_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert('Produto excluído com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert('Erro ao excluir produto', 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao excluir produto', 'error');
    }
}

function limparFormularioImagem() {
    fileInput.value = '';
    previewImg.src = '#';
    preview.style.display = 'none';
    instructions.style.display = 'block';
}

// Limpar formulário
function limparFormulario() {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    limparFormularioImagem();
    document.querySelector('input[name="action"]').value = 'create';
}

// Buscar produtos
async function buscarProdutos() {
    const searchValue = document.getElementById('searchInput').value;
    const searchType = document.getElementById('searchType').value;
    
    const url = `<?php echo BASE_URL; ?>controllers/product_controller.php?action=search&search_type=${searchType}&search_value=${encodeURIComponent(searchValue)}`;
    
    try {
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            // Atualizar tabela com resultados
            location.reload(); // Simplificado - você pode atualizar dinamicamente
        }
    } catch (error) {
        console.error('Erro ao buscar produtos:', error);
    }
}
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
