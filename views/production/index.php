<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

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
require_once __DIR__ . '/../../includes/multi_tenancy.php';
$queryProductions = "SELECT p.*, pr.description as product_name FROM productions p JOIN products pr ON p.product_id = pr.id ORDER BY p.created_at DESC LIMIT 20";
$queryProductions = addUserFilterToQuery($queryProductions, 'p');
$productions = $db->query($queryProductions)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
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
const allProducts = <?php echo json_encode($products); ?>;
// Filtrar apenas insumos e produtos intermediários para ingredientes
const ingredientProducts = allProducts.filter(p => p.type === 'supply' || p.type === 'intermediate');
let ingredientCount = 0;
let calculatedData = null;

function adicionarIngrediente() {
    console.log('Adicionando ingrediente...'); // Debug
    ingredientCount++;
    const container = document.getElementById('ingredientsContainer');
    
    if (!container) {
        console.error('Container não encontrado!');
        return;
    }
    
    const div = document.createElement('div');
    div.className = 'ingredient-row';
    div.id = `ingredient-${ingredientCount}`;
    div.style.cssText = 'display: flex; gap: 15px; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; align-items: flex-end; border-left: 4px solid #3b82f6;';
    
    let optionsHtml = '<option value="">Selecione um ingrediente</option>';
    ingredientProducts.forEach(p => {
        optionsHtml += `<option value="${p.id}" data-cost="${p.cost}" data-unit="${p.unit}" data-stock="${p.stock}">
            ${p.description} (Estoque: ${p.stock} ${p.unit.toUpperCase()})
        </option>`;
    });
    
    div.innerHTML = `
        <div class="form-group" style="flex: 2; margin-bottom: 0;">
            <label><i class="fas fa-box"></i> Ingrediente *</label>
            <select name="ingredients[]" class="ingredient-select" required>
                ${optionsHtml}
            </select>
        </div>
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label><i class="fas fa-weight"></i> Quantidade *</label>
            <input type="number" name="quantities[]" step="0.01" min="0.01" placeholder="0.00" required>
        </div>
        <div class="form-group" style="flex: 0.8; margin-bottom: 0;">
            <label><i class="fas fa-balance-scale"></i> Unidade</label>
            <input type="text" class="unit-display" readonly value="-" style="text-align: center; font-weight: bold;">
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removerIngrediente(${ingredientCount})" style="margin-bottom: 0;" title="Remover ingrediente">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(div);
    
    // Atualizar unidade quando selecionar ingrediente
    const select = div.querySelector('.ingredient-select');
    const unitDisplay = div.querySelector('.unit-display');
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            unitDisplay.value = option.dataset.unit.toUpperCase();
        } else {
            unitDisplay.value = '-';
        }
    });
    
    console.log('Ingrediente adicionado com sucesso!');
}

function removerIngrediente(id) {
    const element = document.getElementById(`ingredient-${id}`);
    if (element) {
        element.remove();
        console.log('Ingrediente removido:', id);
    }
}

function calcularProducao() {
    console.log('Calculando produção...');
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
        alert('Calcule a produção primeiro!');
        return;
    }
    
    if (!confirm('Confirma o salvamento da produção? O estoque será atualizado.')) {
        return;
    }
    
    console.log('Salvando produção:', calculatedData);
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/production_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'save', 
                ...calculatedData 
            })
        });
        
        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Response data:', result);
        
        if (result.success) {
            alert('✓ Produção salva com sucesso!');
            window.location.reload();
        } else {
            alert('Erro ao salvar produção:\n' + (result.message || 'Erro desconhecido'));
            console.error('Erro completo:', result);
        }
    } catch (error) {
        alert('Erro ao salvar produção:\n' + error.message);
        console.error('Erro catch:', error);
    }
}

// Adicionar primeiro ingrediente quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, adicionando primeiro ingrediente...');
    adicionarIngrediente();
});

// Também chamar imediatamente caso o script rode depois do DOM
if (document.readyState === 'loading') {
    console.log('Aguardando DOM...');
} else {
    console.log('DOM já pronto, adicionando ingrediente agora...');
    adicionarIngrediente();
}
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
