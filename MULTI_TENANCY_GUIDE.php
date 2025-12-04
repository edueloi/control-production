<?php
/**
 * GUIA DE USO - MULTI-TENANCY
 * Sistema de Isolamento de Dados por Usuário
 * 
 * Este arquivo explica como usar o sistema multi-tenant
 */

// ============================================
// 1. CRIANDO REGISTROS
// ============================================

// SEMPRE adicionar user_id ao criar registros
$userId = getCurrentUserId();

// Exemplo: Criar produto
$stmt = $db->prepare("INSERT INTO products (user_id, barcode, description, cost, price, unit, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$userId, $barcode, $description, $cost, $price, $unit, $type]);

// ============================================
// 2. LISTANDO REGISTROS
// ============================================

// Método 1: Usar getUserFilter() na condição WHERE
$filter = getUserFilter(); // Retorna "user_id = X" ou "1=1" se admin
$stmt = $db->query("SELECT * FROM products WHERE $filter ORDER BY description");

// Método 2: Usar addUserFilterToQuery() para adicionar filtro automaticamente
$query = "SELECT * FROM products ORDER BY description";
$query = addUserFilterToQuery($query); // Adiciona WHERE user_id = X automaticamente
$stmt = $db->query($query);

// Método 3: Manual (não recomendado)
if (isAdmin()) {
    $stmt = $db->query("SELECT * FROM products ORDER BY description");
} else {
    $userId = getCurrentUserId();
    $stmt = $db->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY description");
    $stmt->execute([$userId]);
}

// ============================================
// 3. EDITANDO REGISTROS
// ============================================

// SEMPRE verificar se o usuário é dono antes de editar
if (!checkOwnership($db, 'products', $productId)) {
    setErrorMessage('Você não tem permissão para editar este produto!');
    header('Location: produtos.php');
    exit;
}

// Depois da verificação, pode editar normalmente
$stmt = $db->prepare("UPDATE products SET description = ?, price = ? WHERE id = ?");
$stmt->execute([$description, $price, $productId]);

// ============================================
// 4. DELETANDO REGISTROS
// ============================================

// SEMPRE verificar ownership antes de deletar
if (!checkOwnership($db, 'products', $productId)) {
    setErrorMessage('Você não tem permissão para deletar este produto!');
    exit;
}

$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$productId]);

// ============================================
// 5. CONTANDO REGISTROS
// ============================================

// Usar getUserCountQuery()
$query = getUserCountQuery('products');
$count = $db->query($query)->fetchColumn();

// Ou manual
if (isAdmin()) {
    $count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
} else {
    $userId = getCurrentUserId();
    $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
}

// ============================================
// 6. JOINS COM MÚLTIPLAS TABELAS
// ============================================

// Aplicar filtro em todas as tabelas com dados do usuário
$userId = getCurrentUserId();

if (isAdmin()) {
    $query = "SELECT s.*, c.name as client_name, u.name as user_name 
              FROM sales s 
              LEFT JOIN clients c ON s.client_id = c.id 
              LEFT JOIN users u ON s.user_id = u.id";
} else {
    $query = "SELECT s.*, c.name as client_name, u.name as user_name 
              FROM sales s 
              LEFT JOIN clients c ON s.client_id = c.id AND c.user_id = ?
              LEFT JOIN users u ON s.user_id = u.id 
              WHERE s.user_id = ?";
}

// ============================================
// 7. PROTEÇÃO DE ROTAS
// ============================================

// No início de cada página, usar requireRole()
requireRole('user'); // Exige no mínimo usuário comum
requireRole('manager'); // Exige manager ou admin
requireRole('admin'); // Exige admin

// Verificação manual
if (!canAccess('manager')) {
    accessDenied('Apenas gerentes podem acessar esta página.');
}

// ============================================
// 8. LOGGING DE ATIVIDADES
// ============================================

// Registrar ações importantes
logActivity($db, 'Create', 'Produto criado', 'product', $productId);
logActivity($db, 'Update', 'Cliente atualizado', 'client', $clientId);
logActivity($db, 'Delete', 'Venda removida', 'sale', $saleId);

// ============================================
// 9. FUNÇÕES AUXILIARES
// ============================================

getCurrentUserId();      // Retorna ID do usuário logado
getCurrentUserRole();    // Retorna 'admin', 'manager' ou 'user'
isAdmin();              // true se usuário é admin
canAccess('manager');   // Verifica se tem permissão de manager ou superior
getUserLogInfo();       // Retorna array com info do usuário para logs

// ============================================
// 10. COMPORTAMENTO POR ROLE
// ============================================

/*
USER (Usuário Comum):
- Vê APENAS seus próprios dados
- Não pode ver dados de outros usuários
- Cria registros automaticamente vinculados a ele

MANAGER (Gerente):
- Vê APENAS seus próprios dados (igual user)
- Pode ter acesso a relatórios específicos
- Tem permissões administrativas limitadas

ADMIN (Administrador):
- Vê TODOS os dados de TODOS os usuários
- Pode gerenciar usuários no painel
- Tem acesso irrestrito ao sistema
- Pode ver de qual usuário é cada registro
*/

// ============================================
// 11. EXEMPLO COMPLETO - CONTROLLER
// ============================================

/*
<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Proteger rota
requireRole('user');

$db = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = getCurrentUserId();

try {
    switch ($action) {
        case 'create':
            // SEMPRE incluir user_id ao criar
            $stmt = $db->prepare("INSERT INTO products (user_id, barcode, description, cost, price, unit, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $_POST['barcode'], $_POST['description'], $_POST['cost'], $_POST['price'], $_POST['unit'], $_POST['type']]);
            
            logActivity($db, 'Create', 'Produto criado', 'product', $db->lastInsertId());
            setSuccessMessage('Produto criado com sucesso!');
            break;
            
        case 'update':
            // Verificar ownership
            $id = $_POST['id'];
            if (!checkOwnership($db, 'products', $id)) {
                setErrorMessage('Sem permissão!');
                break;
            }
            
            $stmt = $db->prepare("UPDATE products SET description = ?, price = ? WHERE id = ?");
            $stmt->execute([$_POST['description'], $_POST['price'], $id]);
            
            logActivity($db, 'Update', 'Produto atualizado', 'product', $id);
            setSuccessMessage('Produto atualizado!');
            break;
            
        case 'delete':
            $id = $_POST['id'];
            if (!checkOwnership($db, 'products', $id)) {
                setErrorMessage('Sem permissão!');
                break;
            }
            
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($db, 'Delete', 'Produto deletado', 'product', $id);
            setSuccessMessage('Produto deletado!');
            break;
            
        case 'list':
            // Filtrar por usuário automaticamente
            $filter = getUserFilter();
            $stmt = $db->query("SELECT * FROM products WHERE $filter ORDER BY description");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($products);
            exit;
    }
} catch (Exception $e) {
    setErrorMessage('Erro: ' . $e->getMessage());
}

header('Location: produtos.php');
?>
*/

?>
