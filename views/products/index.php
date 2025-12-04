<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Produtos';
$db = Database::getInstance()->getConnection();

// Buscar todos os produtos
$products = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
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
                        <input type="file" id="image" name="image" accept="image/*">
                        <div id="imagePreview" style="margin-top: 10px; display: none;">
                            <img id="previewImg" style="max-width: 150px; border-radius: 8px; border: 2px solid #e2e8f0;">
                        </div>
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

<script>
// Preview de imagem
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(file);
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
        document.getElementById('previewImg').src = '<?php echo BASE_URL; ?>' + product.image;
        document.getElementById('imagePreview').style.display = 'block';
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

// Limpar formulário
function limparFormulario() {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('imagePreview').style.display = 'none';
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
