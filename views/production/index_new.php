<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Controle de Produção';
$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// Buscar produtos com filtro por usuário
$filter = getUserFilter();
$products = $db->query("SELECT * FROM products WHERE $filter ORDER BY description")->fetchAll(PDO::FETCH_ASSOC);
$finishedProducts = array_filter($products, fn($p) => $p['type'] === 'finished' || $p['type'] === 'intermediate');
$ingredients = array_filter($products, fn($p) => $p['type'] === 'supply' || $p['type'] === 'intermediate');
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-industry"></i> Controle de Produção</h1>
                    <p>Gerencie receitas e produções com cálculo automático de custos</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="verHistorico()">
                        <i class="fas fa-history"></i> Histórico
                    </button>
                    <button class="btn btn-info" onclick="verEstatisticas()">
                        <i class="fas fa-chart-line"></i> Estatísticas
                    </button>
                </div>
            </div>
            
            <!-- Formulário de Nova Produção -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3><i class="fas fa-plus-circle"></i> Nova Produção</h3>
                    <button class="btn btn-sm btn-secondary" onclick="limparFormulario()">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>
                
                <form id="productionForm" onsubmit="return false;">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="finished_product">
                                <i class="fas fa-box"></i>
                                Produto Acabado *
                            </label>
                            <select id="finished_product" required onchange="atualizarInfoProduto()">
                                <option value="">Selecione o produto final</option>
                                <?php foreach ($finishedProducts as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" 
                                            data-price="<?php echo $product['price']; ?>"
                                            data-cost="<?php echo $product['cost']; ?>"
                                            data-unit="<?php echo $product['unit']; ?>"
                                            data-stock="<?php echo $product['stock']; ?>">
                                        <?php echo htmlspecialchars($product['description']); ?> 
                                        (Estoque: <?php echo $product['stock']; ?> <?php echo $product['unit']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="batch_size">
                                <i class="fas fa-layer-group"></i>
                                Tamanho do Lote *
                            </label>
                            <input type="number" id="batch_size" min="1" value="1" required onchange="recalcular()">
                        </div>
                    </div>
                    
                    <!-- Info do Produto -->
                    <div id="productInfo" class="hidden" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                            <div>
                                <small style="color: #666;">Preço de Venda</small>
                                <div style="font-size: 18px; font-weight: bold; color: #1976d2;" id="productPrice">R$ 0,00</div>
                            </div>
                            <div>
                                <small style="color: #666;">Estoque Atual</small>
                                <div style="font-size: 18px; font-weight: bold; color: #388e3c;" id="productStock">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-header" style="margin: 20px 0; display: flex; justify-content: space-between; align-items: center;">
                        <h4><i class="fas fa-list-ul"></i> Ingredientes da Receita</h4>
                        <button type="button" class="btn btn-success btn-sm" onclick="adicionarIngrediente()">
                            <i class="fas fa-plus"></i> Adicionar Ingrediente
                        </button>
                    </div>
                    
                    <div id="ingredientsContainer" style="margin-bottom: 20px;">
                        <p style="text-align: center; color: #999; padding: 40px;">
                            Clique em "Adicionar Ingrediente" para começar
                        </p>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" onclick="calcularProducao()">
                            <i class="fas fa-calculator"></i> Calcular Custos
                        </button>
                        <button type="button" class="btn btn-success" id="saveBtn" onclick="salvarProducao()" disabled>
                            <i class="fas fa-save"></i> Salvar Produção
                        </button>
                    </div>
                </form>
                
                <!-- Resultados -->
                <div id="productionResults" class="hidden" style="margin-top: 30px; padding: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                    <h4 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-pie"></i> Análise de Custos e Lucratividade
                    </h4>
                    <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                        <div class="result-card">
                            <div class="result-label">Custo Total</div>
                            <div class="result-value" id="totalCost">R$ 0,00</div>
                        </div>
                        <div class="result-card">
                            <div class="result-label">Custo Unitário</div>
                            <div class="result-value" id="unitCost">R$ 0,00</div>
                        </div>
                        <div class="result-card">
                            <div class="result-label">Quantidade</div>
                            <div class="result-value" id="yield">0 un</div>
                        </div>
                        <div class="result-card">
                            <div class="result-label">Margem de Lucro</div>
                            <div class="result-value" id="profitMargin">0%</div>
                        </div>
                        <div class="result-card">
                            <div class="result-label">Lucro por Unidade</div>
                            <div class="result-value" id="profitValue">R$ 0,00</div>
                        </div>
                    </div>
                    
                    <div id="stockWarnings" class="hidden" style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.2); border-radius: 8px;">
                        <h5 style="margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Avisos de Estoque</h5>
                        <div id="stockWarningsList"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.ingredient-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 15px;
    align-items: end;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid #3b82f6;
}

.ingredient-row:hover {
    background: #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.ingredient-row.insufficient-stock {
    border-left-color: #ef4444;
    background: #fee2e2;
}

.result-card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.result-label {
    font-size: 12px;
    opacity: 0.9;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.result-value {
    font-size: 24px;
    font-weight: bold;
}

.form-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .ingredient-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const produtos = <?php echo json_encode($products); ?>;
const ingredientesDisponiveis = <?php echo json_encode($ingredients); ?>;
let currentCalculation = null;

function adicionarIngrediente() {
    const container = document.getElementById('ingredientsContainer');
    
    // Remover mensagem vazia
    if (container.querySelector('p')) {
        container.innerHTML = '';
    }
    
    const row = document.createElement('div');
    row.className = 'ingredient-row';
    
    row.innerHTML = `
        <div class="form-group">
            <label>Ingrediente *</label>
            <select class="ingredient-select" required onchange="atualizarUnidadesIngrediente(this)">
                <option value="">Selecione um ingrediente</option>
                <?php foreach ($ingredients as $ing): ?>
                    <option value="<?php echo $ing['id']; ?>" 
                            data-unit="<?php echo $ing['unit']; ?>"
                            data-stock="<?php echo $ing['stock']; ?>"
                            data-cost="<?php echo $ing['cost']; ?>">
                        <?php echo htmlspecialchars($ing['description']); ?> 
                        (<?php echo $ing['stock']; ?> <?php echo $ing['unit']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Quantidade *</label>
            <input type="number" class="ingredient-quantity" min="0" step="0.01" required onchange="recalcular()">
        </div>
        <div class="form-group">
            <label>Unidade *</label>
            <select class="ingredient-unit" required onchange="recalcular()">
                <option value="">-</option>
            </select>
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removerIngrediente(this)" style="margin-top: 0;">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(row);
}

function atualizarUnidadesIngrediente(select) {
    const row = select.closest('.ingredient-row');
    const unitSelect = row.querySelector('.ingredient-unit');
    const option = select.selectedOptions[0];
    const unit = option.dataset.unit;
    
    unitSelect.innerHTML = '';
    
    if (!unit) return;
    
    // Adicionar opção padrão
    unitSelect.innerHTML += `<option value="${unit}">${getUnitText(unit)}</option>`;
    
    // Adicionar conversões
    if (unit === 'kg') {
        unitSelect.innerHTML += `<option value="g">Gramas (g)</option>`;
    } else if (unit === 'g') {
        unitSelect.innerHTML += `<option value="kg">Quilogramas (kg)</option>`;
    } else if (unit === 'l') {
        unitSelect.innerHTML += `<option value="ml">Mililitros (ml)</option>`;
    } else if (unit === 'ml') {
        unitSelect.innerHTML += `<option value="l">Litros (l)</option>`;
    }
    
    recalcular();
}

function getUnitText(unit) {
    const units = {
        'un': 'Unidades',
        'kg': 'Quilogramas (kg)',
        'g': 'Gramas (g)',
        'l': 'Litros (l)',
        'ml': 'Mililitros (ml)'
    };
    return units[unit] || unit;
}

function removerIngrediente(btn) {
    btn.closest('.ingredient-row').remove();
    
    const container = document.getElementById('ingredientsContainer');
    if (container.children.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Clique em "Adicionar Ingrediente" para começar</p>';
    }
    
    recalcular();
}

function atualizarInfoProduto() {
    const select = document.getElementById('finished_product');
    const option = select.selectedOptions[0];
    const info = document.getElementById('productInfo');
    
    if (!option.value) {
        info.classList.add('hidden');
        return;
    }
    
    document.getElementById('productPrice').textContent = 'R$ ' + parseFloat(option.dataset.price).toFixed(2);
    document.getElementById('productStock').textContent = option.dataset.stock + ' ' + option.dataset.unit;
    info.classList.remove('hidden');
}

function coletarIngredientes() {
    const rows = document.querySelectorAll('.ingredient-row');
    const ingredients = [];
    
    rows.forEach(row => {
        const select = row.querySelector('.ingredient-select');
        const quantity = row.querySelector('.ingredient-quantity');
        const unit = row.querySelector('.ingredient-unit');
        
        if (select.value && quantity.value && unit.value) {
            ingredients.push({
                product_id: parseInt(select.value),
                quantity: parseFloat(quantity.value),
                unit: unit.value
            });
        }
    });
    
    return ingredients;
}

function calcularProducao() {
    const productId = document.getElementById('finished_product').value;
    const batchSize = document.getElementById('batch_size').value;
    const ingredients = coletarIngredientes();
    
    if (!productId) {
        alert('Selecione um produto acabado!');
        return;
    }
    
    if (ingredients.length === 0) {
        alert('Adicione pelo menos um ingrediente!');
        return;
    }
    
    // Mostrar loading
    document.getElementById('saveBtn').disabled = true;
    
    fetch('../../controllers/production_controller.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'calculate',
            product_id: productId,
            batch_size: batchSize,
            ingredients: ingredients
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            currentCalculation = data.data;
            mostrarResultados(data.data);
            document.getElementById('saveBtn').disabled = !data.data.canProduce;
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(err => {
        alert('Erro ao calcular: ' + err);
    });
}

function mostrarResultados(data) {
    document.getElementById('totalCost').textContent = 'R$ ' + data.totalCost.toFixed(2);
    document.getElementById('unitCost').textContent = 'R$ ' + data.unitCost.toFixed(2);
    document.getElementById('yield').textContent = data.batchSize + ' un';
    document.getElementById('profitMargin').textContent = data.profitMargin.toFixed(2) + '%';
    document.getElementById('profitValue').textContent = 'R$ ' + data.profitValue.toFixed(2);
    
    // Mostrar avisos de estoque
    const warnings = document.getElementById('stockWarnings');
    const warningsList = document.getElementById('stockWarningsList');
    
    if (data.insufficientStock.length > 0) {
        warningsList.innerHTML = data.insufficientStock.map(w => 
            `<div style="padding: 8px; background: rgba(255,255,255,0.3); border-radius: 4px; margin-bottom: 5px;">
                <strong>${w.name}:</strong> Necessário ${w.needed.toFixed(2)} ${w.unit}, disponível ${w.available.toFixed(2)} ${w.unit}
            </div>`
        ).join('');
        warnings.classList.remove('hidden');
    } else {
        warnings.classList.add('hidden');
    }
    
    document.getElementById('productionResults').classList.remove('hidden');
}

function salvarProducao() {
    if (!currentCalculation || !currentCalculation.canProduce) {
        alert('Não é possível produzir! Verifique o estoque.');
        return;
    }
    
    if (!confirm('Confirma a produção? O estoque será atualizado.')) {
        return;
    }
    
    const productId = document.getElementById('finished_product').value;
    const batchSize = document.getElementById('batch_size').value;
    
    fetch('../../controllers/production_controller.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'save',
            product_id: productId,
            batch_size: parseInt(batchSize),
            total_cost: currentCalculation.totalCost,
            unit_cost: currentCalculation.unitCost,
            profit_margin: currentCalculation.profitMargin,
            ingredients: currentCalculation.ingredientsDetails
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            limparFormulario();
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(err => {
        alert('Erro ao salvar: ' + err);
    });
}

function limparFormulario() {
    document.getElementById('productionForm').reset();
    document.getElementById('ingredientsContainer').innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Clique em "Adicionar Ingrediente" para começar</p>';
    document.getElementById('productionResults').classList.add('hidden');
    document.getElementById('productInfo').classList.add('hidden');
    document.getElementById('saveBtn').disabled = true;
    currentCalculation = null;
}

function recalcular() {
    document.getElementById('productionResults').classList.add('hidden');
    document.getElementById('saveBtn').disabled = true;
    currentCalculation = null;
}

function verHistorico() {
    window.location.href = 'historico.php';
}

function verEstatisticas() {
    alert('Relatórios em desenvolvimento!');
}

// Adicionar primeiro ingrediente ao carregar
window.addEventListener('load', () => {
    adicionarIngrediente();
});
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
