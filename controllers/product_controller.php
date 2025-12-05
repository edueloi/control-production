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
                INSERT INTO products (user_id, barcode, description, cost, price, stock, min_stock, unit, type, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([getCurrentUserId(), $barcode, $description, $cost, $price, $stock, $minStock, $unit, $type, $imagePath]);
            
            setSuccessMessage('Produto cadastrado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            
            if (!checkOwnership($db, 'products', $id)) {
                echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
                exit;
            }

            $barcode = sanitizeInput($_POST['barcode']);
            $description = sanitizeInput($_POST['description']);
            $cost = floatval($_POST['cost']);
            $price = floatval($_POST['price']);
            $stock = floatval($_POST['stock']);
            $minStock = floatval($_POST['min_stock']);
            $unit = sanitizeInput($_POST['unit']);
            $type = sanitizeInput($_POST['type']);
            
            // Buscar imagem atual
            $stmt = $db->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, getCurrentUserId()]);
            $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
            $imagePath = $currentProduct['image'];
            
            // Lógica de remoção de imagem
            if (isset($_POST['remove_image']) && $_POST['remove_image'] === 'true' && !isset($_FILES['image'])) {
                if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                    unlink(__DIR__ . '/../' . $imagePath);
                }
                $imagePath = null;
            }

            // Upload de nova imagem
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD_DIR . 'products/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Remover imagem antiga se houver uma nova
                if ($imagePath && file_exists(__DIR__ . '/../' . $imagePath)) {
                    unlink(__DIR__ . '/../' . $imagePath);
                }
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/products/' . $filename;
                } else {
                    $imagePath = $currentProduct['image']; // Mantém a imagem antiga se o upload falhar
                }
            }
            
            $stmt = $db->prepare("
                UPDATE products 
                SET barcode = ?, description = ?, cost = ?, price = ?, stock = ?, 
                    min_stock = ?, unit = ?, type = ?, image = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$barcode, $description, $cost, $price, $stock, $minStock, $unit, $type, $imagePath, $id, getCurrentUserId()]);
            
            setSuccessMessage('Produto atualizado com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            if (!checkOwnership($db, 'products', $id)) {
                echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
                exit;
            }

            // Buscar e deletar imagem
            $stmt = $db->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, getCurrentUserId()]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product && $product['image'] && file_exists(__DIR__ . '/../' . $product['image'])) {
                unlink(__DIR__ . '/../' . $product['image']);
            }
            
            $stmt = $db->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, getCurrentUserId()]);
            
            setSuccessMessage('Produto excluído com sucesso!');
            echo json_encode(['success' => true]);
            break;
            
        case 'search':
            $searchType = $_GET['search_type'] ?? 'all';
            $searchValue = sanitizeInput($_GET['search_value'] ?? '');
            
            $filter = getUserFilter();
            $query = "SELECT * FROM products WHERE {$filter}";
            $params = [];

            if ($searchType !== 'all' && !empty($searchValue)) {
                if ($searchType === 'barcode') {
                    $query .= " AND barcode LIKE ?";
                    $params[] = "%$searchValue%";
                } elseif ($searchType === 'description') {
                    $query .= " AND description LIKE ?";
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
            
            if (!checkOwnership($db, 'products', $id)) {
                echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
                exit;
            }

            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $product]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (PDOException $e) {
    // Check for unique constraint violation
    if ($e->getCode() == '23000' && strpos($e->getMessage(), 'UNIQUE constraint failed: products.barcode') !== false) {
        echo json_encode(['success' => false, 'message' => 'Este código de barras já está em uso. Por favor, utilize outro.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
