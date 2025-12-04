<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $barcode = sanitizeInput($_POST['barcode']);
            $description = sanitizeInput($_POST['description']);
            $cost = floatval($_POST['cost']);
            $price = floatval($_POST['price']);
            $stock = floatval($_POST['stock']);
            $minStock = floatval($_POST['min_stock']);
            $unit = sanitizeInput($_POST['unit']);
            $type = sanitizeInput($_POST['type']);
            
            // Upload de imagem
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD_DIR . 'products/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/products/' . $filename;
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO products (barcode, description, cost, price, stock, min_stock, unit, type, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$barcode, $description, $cost, $price, $stock, $minStock, $unit, $type, $imagePath]);
            
            setSuccessMessage('Produto cadastrado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $barcode = sanitizeInput($_POST['barcode']);
            $description = sanitizeInput($_POST['description']);
            $cost = floatval($_POST['cost']);
            $price = floatval($_POST['price']);
            $stock = floatval($_POST['stock']);
            $minStock = floatval($_POST['min_stock']);
            $unit = sanitizeInput($_POST['unit']);
            $type = sanitizeInput($_POST['type']);
            
            // Buscar imagem atual
            $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            $imagePath = $currentProduct['image'];
            
            // Upload de nova imagem
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD_DIR . 'products/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Remover imagem antiga
                if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                    unlink(__DIR__ . '/../' . $imagePath);
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/products/' . $filename;
                }
            }
            
            $stmt = $db->prepare("
                UPDATE products 
                SET barcode = ?, description = ?, cost = ?, price = ?, stock = ?, 
                    min_stock = ?, unit = ?, type = ?, image = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$barcode, $description, $cost, $price, $stock, $minStock, $unit, $type, $imagePath, $id]);
            
            setSuccessMessage('Produto atualizado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Buscar e deletar imagem
            $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product['image'] && file_exists(__DIR__ . '/../' . $product['image'])) {
                unlink(__DIR__ . '/../' . $product['image']);
            }
            
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            setSuccessMessage('Produto excluído com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'search':
            $searchType = $_GET['search_type'] ?? 'all';
            $searchValue = sanitizeInput($_GET['search_value'] ?? '');
            
            $query = "SELECT * FROM products";
            $params = [];
            
            if ($searchType !== 'all' && !empty($searchValue)) {
                if ($searchType === 'barcode') {
                    $query .= " WHERE barcode LIKE ?";
                    $params[] = "%$searchValue%";
                } elseif ($searchType === 'description') {
                    $query .= " WHERE description LIKE ?";
                    $params[] = "%$searchValue%";
                }
            }
            
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $products]);
            break;
            
        case 'get':
            $id = intval($_GET['id']);
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $product]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
