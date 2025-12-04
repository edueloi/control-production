<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle = 'Produção';
$db = Database::getInstance()->getConnection();

// Buscar produtos para os selects
$products = $db->query("SELECT * FROM products ORDER BY description")->fetchAll(PDO::FETCH_ASSOC);
$finishedProducts = array_filter($products, fn($p) => $p['type'] === 'finished' || $p['type'] === 'intermediate');

// Buscar histórico de produção
$productions = $db->query("
    SELECT p.*, pr.description as product_name 
    FROM productions p 
    JOIN products pr ON p.product_id = pr.id 
    ORDER BY p.created_at DESC 
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-industry"></i> Controle de Produção</h1>
                <p>Gerencie suas receitas e produções</p>
            </div>
            
            <!-- Formulário de Nova Produção -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Nova Receita de Produção</h3>
                </div>
                <form id="productionForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="finished_product">
                                <i class="fas fa-box"></i>
                                Produto Acabado *
                            </label>
                            <select id="finished_product" name="product_id" required>
                                <option value="">Selecione um produto</option>
                                <?php foreach ($finishedProducts as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" 
                                            data-price="<?php echo $product['price']; ?>"
                                            data-cost="<?php echo $product['cost']; ?>">
                                        <?php echo htmlspecialchars($product['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="batch_size">
                                <i class="fas fa-layer-group"></i>
                                Tamanho do Lote *
                            </label>
                            <input type="number" id="batch_size" name="batch_size" min="1" value="1" required>
                        </div>
                    </div>
                    
                    <div class="card-header" style="margin-top: 20px;">
                        <h4><i class="fas fa-list"></i> Ingredientes da Receita</h4>
                        <button type="button" class="btn btn-success btn-sm" onclick="adicionarIngrediente()">
                            <i class="fas fa-plus"></i> Adicionar Ingrediente
                        </button>
                    </div>
                    
                    <div id="ingredientsContainer">
                        <!-- Ingredientes serão adicionados aqui -->
                    </div>
                    
                    <div class="btn-group" style="margin-top: 20px;">
                        <button type="button" class="btn btn-primary" onclick="calcularProducao()">
                            <i class="fas fa-calculator"></i> Calcular Custo
                        </button>
                        <button type="button" class="btn btn-success" id="saveBtn" onclick="salvarProducao()" disabled>
                            <i class="fas fa-save"></i> Salvar Produção
                        </button>
                    </div>
                </form>
                
                <!-- Resultados -->
                <div id="productionResults" class="hidden" style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 8px;">
                    <h4 style="color: #2e7d32; margin-bottom: 15px;">
                        <i class="fas fa-check-circle"></i> Resultados da Produção
                    </h4>
                    <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="result-item" style="background: white; padding: 15px; border-radius: 8px;">
                            <div style="color: #666; font-size: 14px;">Custo Total</div>
                            <div id="totalCost" style="font-size: 24px; font-weight: 700; color: #2e7d32;">R$ 0,00</div>
                        </div>
                        <div class="result-item" style="background: white; padding: 15px; border-radius: 8px;">
                            <div style="color: #666; font-size: 14px;">Custo por Unidade</div>
                            <div id="unitCost" style="font-size: 24px; font-weight: 700; color: #2e7d32;">R$ 0,00</div>
                        </div>
                        <div class="result-item" style="background: white; padding: 15px; border-radius: 8px;">
                            <div style="color: #666; font-size: 14px;">Rendimento</div>
                            <div id="yield" style="font-size: 24px; font-weight: 700; color: #2e7d32;">0 unidades</div>
                        </div>
                        <div class="result-item" style="background: white; padding: 15px; border-radius: 8px;">
                            <div style="color: #666; font-size: 14px;">Margem de Lucro</div>
                            <div id="profitMargin" style="font-size: 24px; font-weight: 700; color: #2e7d32;">0%</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Histórico de Produção -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Histórico de Produção</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Lote</th>
                                <th>Custo Total</th>
                                <th>Custo Unit.</th>
                                <th>Margem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productions as $prod): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($prod['created_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($prod['product_name']); ?></strong></td>
                                    <td><?php echo $prod['batch_size']; ?> unidades</td>
                                    <td><?php echo formatMoney($prod['total_cost']); ?></td>
                                    <td><?php echo formatMoney($prod['unit_cost']); ?></td>
                                    <td><span class="badge badge-success"><?php echo number_format($prod['profit_margin'], 2); ?>%</span></td>
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
const products = <?php echo json_encode($products); ?>;
let ingredientCount = 0;
let calculatedData = null;

function adicionarIngrediente() {
    ingredientCount++;
    const container = document.getElementById('ingredientsContainer');
    const div = document.createElement('div');
    div.className = 'ingredient-row';
    div.id = `ingredient-${ingredientCount}`;
    div.style.cssText = 'display: flex; gap: 15px; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; align-items: flex-end;';
    
    div.innerHTML = `
        <div class="form-group" style="flex: 2; margin-bottom: 0;">
            <label>Ingrediente</label>
            <select name="ingredients[]" class="ingredient-select" required>
                <option value="">Selecione</option>
                ${products.map(p => `<option value="${p.id}" data-cost="${p.cost}" data-unit="${p.unit}">${p.description} (${p.cost.toFixed(2)} - ${p.unit.toUpperCase()})</option>`).join('')}
            </select>
        </div>
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label>Quantidade</label>
            <input type="number" name="quantities[]" step="0.01" min="0.01" required>
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removerIngrediente(${ingredientCount})" style="margin-bottom: 0;">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(div);
}

function removerIngrediente(id) {
    document.getElementById(`ingredient-${id}`).remove();
}

function calcularProducao() {
    const productId = document.getElementById('finished_product').value;
    const batchSize = parseInt(document.getElementById('batch_size').value);
    
    if (!productId || !batchSize) {
        Utils.showAlert('Preencha o produto e tamanho do lote', 'error');
        return;
    }
    
    const ingredients = document.querySelectorAll('.ingredient-row');
    if (ingredients.length === 0) {
        Utils.showAlert('Adicione pelo menos um ingrediente', 'error');
        return;
    }
    
    let totalCost = 0;
    const ingredientData = [];
    
    ingredients.forEach(row => {
        const select = row.querySelector('.ingredient-select');
        const quantity = parseFloat(row.querySelector('input[name="quantities[]"]').value);
        const option = select.options[select.selectedIndex];
        const cost = parseFloat(option.dataset.cost);
        
        const ingredientCost = cost * quantity;
        totalCost += ingredientCost;
        
        ingredientData.push({
            product_id: select.value,
            quantity: quantity,
            cost: ingredientCost
        });
    });
    
    const unitCost = totalCost / batchSize;
    
    const productSelect = document.getElementById('finished_product');
    const productOption = productSelect.options[productSelect.selectedIndex];
    const salePrice = parseFloat(productOption.dataset.price);
    
    const profit = salePrice - unitCost;
    const profitMargin = (profit / salePrice) * 100;
    
    document.getElementById('totalCost').textContent = Utils.formatMoney(totalCost);
    document.getElementById('unitCost').textContent = Utils.formatMoney(unitCost);
    document.getElementById('yield').textContent = `${batchSize} unidades`;
    document.getElementById('profitMargin').textContent = `${profitMargin.toFixed(2)}%`;
    
    document.getElementById('productionResults').classList.remove('hidden');
    document.getElementById('saveBtn').disabled = false;
    
    calculatedData = {
        product_id: productId,
        batch_size: batchSize,
        total_cost: totalCost,
        unit_cost: unitCost,
        profit_margin: profitMargin,
        ingredients: ingredientData
    };
}

async function salvarProducao() {
    if (!calculatedData) {
        Utils.showAlert('Calcule a produção primeiro', 'error');
        return;
    }
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/production_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', ...calculatedData })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert('Produção salva com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            Utils.showAlert('Erro ao salvar produção', 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao salvar produção', 'error');
    }
}

// Adicionar primeiro ingrediente automaticamente
adicionarIngrediente();
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
