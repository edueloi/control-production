<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'sales':
            $startDate = $_GET['start'] ?? date('Y-m-01');
            $endDate = $_GET['end'] ?? date('Y-m-d');
            $payment = $_GET['payment'] ?? '';
            
            $query = "
                SELECT s.*, c.name as client_name 
                FROM sales s 
                LEFT JOIN clients c ON s.client_id = c.id 
                WHERE DATE(s.created_at) BETWEEN ? AND ?
            ";
            
            $params = [$startDate, $endDate];
            
            if (!empty($payment)) {
                $query .= " AND s.payment_method = ?";
                $params[] = $payment;
            }
            
            $query .= " ORDER BY s.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalSales = count($sales);
            $totalRevenue = array_sum(array_column($sales, 'total'));
            $avgTicket = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_sales' => $totalSales,
                    'total_revenue' => $totalRevenue,
                    'avg_ticket' => $avgTicket,
                    'sales' => $sales
                ]
            ]);
            break;
            
        case 'production':
            $startDate = $_GET['start'] ?? date('Y-m-01');
            $endDate = $_GET['end'] ?? date('Y-m-d');
            
            $stmt = $db->prepare("
                SELECT p.*, pr.description as product_name
                FROM productions p
                JOIN products pr ON p.product_id = pr.id
                WHERE DATE(p.created_at) BETWEEN ? AND ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$startDate, $endDate]);
            $productions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $productions
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
