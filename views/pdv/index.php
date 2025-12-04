<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'PDV - Ponto de Venda';
$db = Database::getInstance()->getConnection();

// Buscar produtos disponíveis
$products = $db->query("SELECT * FROM products WHERE stock > 0 ORDER BY description")->fetchAll(PDO::FETCH_ASSOC);
$clients = $db->query("SELECT * FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-cash-register"></i> Ponto de Venda (PDV)</h1>
                <p>Realize vendas de forma rápida e fácil</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 400px; gap: 20px;">
                <!-- Produtos -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-basket"></i> Produtos Disponíveis</h3>
                    </div>
                    
                    <div class="search-box">
                        <input type="text" id="productSearch" placeholder="Buscar produto..." onkeyup="filtrarProdutos()">
                    </div>
                    
                    <div id="productsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; max-height: 500px; overflow-y: auto; padding: 15px;">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" onclick='adicionarAoCarrinho(<?php echo json_encode($product); ?>)' 
                                 style="background: white; border: 2px solid #e2e8f0; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;"
                                 onmouseover="this.style.borderColor='#667eea'" onmouseout="this.style.borderColor='#e2e8f0'">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo BASE_URL . $product['image']; ?>" 
                                         style="width: 100%; height: 100px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                        <i class="fas fa-box" style="font-size: 32px; color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                                <h4 style="font-size: 14px; margin-bottom: 5px;"><?php echo htmlspecialchars($product['description']); ?></h4>
                                <div style="font-size: 18px; font-weight: 700; color: #48bb78; margin-bottom: 5px;">
                                    <?php echo formatMoney($product['price']); ?>
                                </div>
                                <div style="font-size: 12px; color: #718096;">
                                    Estoque: <?php echo number_format($product['stock'], 2); ?> <?php echo strtoupper($product['unit']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Carrinho -->
                <div class="card" style="position: sticky; top: 90px;">
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-cart"></i> Carrinho</h3>
                        <button class="btn btn-danger btn-sm" onclick="limparCarrinho()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <div id="cartItems" style="max-height: 300px; overflow-y: auto; margin-bottom: 15px;">
                        <!-- Itens do carrinho -->
                    </div>
                    
                    <div style="border-top: 2px solid #e2e8f0; padding-top: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <strong id="subtotal">R$ 0,00</strong>
                        </div>
                        
                        <div class="form-group">
                            <label>Desconto</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="number" id="discount" step="0.01" min="0" value="0" style="flex: 1;" onchange="calcularTotal()">
                                <select id="discountType" onchange="calcularTotal()" style="width: 80px;">
                                    <option value="value">R$</option>
                                    <option value="percent">%</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding: 10px; background: #f7fafc; border-radius: 5px;">
                            <span style="font-size: 18px; font-weight: 700;">TOTAL:</span>
                            <span id="total" style="font-size: 24px; font-weight: 700; color: #667eea;">R$ 0,00</span>
                        </div>
                        
                        <div class="form-group">
                            <label>Cliente (opcional)</label>
                            <select id="clientId">
                                <option value="">Sem cliente</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Forma de Pagamento</label>
                            <select id="paymentMethod">
                                <option value="dinheiro">Dinheiro</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="pix">PIX</option>
                            </select>
                        </div>
                        
                        <button class="btn btn-success btn-block" onclick="finalizarVenda()">
                            <i class="fas fa-check"></i> Finalizar Venda
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let cart = [];

function adicionarAoCarrinho(product) {
    const existing = cart.find(item => item.id === product.id);
    
    if (existing) {
        if (existing.quantity < product.stock) {
            existing.quantity++;
        } else {
            Utils.showAlert('Estoque insuficiente', 'error');
            return;
        }
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }
    
    renderCarrinho();
    calcularTotal();
}

function removerDoCarrinho(productId) {
    cart = cart.filter(item => item.id !== productId);
    renderCarrinho();
    calcularTotal();
}

function alterarQuantidade(productId, quantidade) {
    const item = cart.find(i => i.id === productId);
    if (item) {
        const novaQtd = item.quantity + quantidade;
        if (novaQtd > 0 && novaQtd <= item.stock) {
            item.quantity = novaQtd;
            renderCarrinho();
            calcularTotal();
        } else if (novaQtd > item.stock) {
            Utils.showAlert('Estoque insuficiente', 'error');
        }
    }
}

function renderCarrinho() {
    const container = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Carrinho vazio</p>';
        return;
    }
    
    container.innerHTML = cart.map(item => `
        <div style="padding: 10px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 1;">
                <strong>${item.description}</strong>
                <div style="font-size: 14px; color: #666;">
                    ${Utils.formatMoney(item.price)} x ${item.quantity}
                </div>
            </div>
            <div style="display: flex; gap: 5px; align-items: center;">
                <button onclick="alterarQuantidade(${item.id}, -1)" class="btn btn-sm btn-secondary" style="width: 30px; height: 30px; padding: 0;">-</button>
                <span style="width: 30px; text-align: center; font-weight: 700;">${item.quantity}</span>
                <button onclick="alterarQuantidade(${item.id}, 1)" class="btn btn-sm btn-secondary" style="width: 30px; height: 30px; padding: 0;">+</button>
                <button onclick="removerDoCarrinho(${item.id})" class="btn btn-sm btn-danger" style="width: 30px; height: 30px; padding: 0;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function calcularTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const discountType = document.getElementById('discountType').value;
    
    let discountValue = 0;
    if (discountType === 'percent') {
        discountValue = subtotal * (discount / 100);
    } else {
        discountValue = discount;
    }
    
    const total = Math.max(0, subtotal - discountValue);
    
    document.getElementById('subtotal').textContent = Utils.formatMoney(subtotal);
    document.getElementById('total').textContent = Utils.formatMoney(total);
}

async function finalizarVenda() {
    if (cart.length === 0) {
        Utils.showAlert('Adicione produtos ao carrinho', 'error');
        return;
    }
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const discountType = document.getElementById('discountType').value;
    const total = parseFloat(document.getElementById('total').textContent.replace('R$ ', '').replace('.', '').replace(',', '.'));
    
    const data = {
        action: 'create',
        client_id: document.getElementById('clientId').value || null,
        subtotal: subtotal,
        discount: discount,
        discount_type: discountType,
        total: total,
        payment_method: document.getElementById('paymentMethod').value,
        items: cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            price: item.price
        }))
    };
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>controllers/sale_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showAlert('Venda realizada com sucesso!', 'success');
            limparCarrinho();
        } else {
            Utils.showAlert('Erro ao realizar venda', 'error');
        }
    } catch (error) {
        Utils.showAlert('Erro ao realizar venda', 'error');
    }
}

function limparCarrinho() {
    cart = [];
    renderCarrinho();
    calcularTotal();
    document.getElementById('discount').value = 0;
    document.getElementById('clientId').value = '';
}

function filtrarProdutos() {
    const search = document.getElementById('productSearch').value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(search) ? 'block' : 'none';
    });
}
</script>

<?php include __DIR__ . '/../../components/footer.php'; ?>
