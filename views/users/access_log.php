<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';

// Filtrar logs do usuário logado, admin vê todos
if ($userRole === 'admin') {
    $logs = $db->query("SELECT l.*, u.name as user_name FROM activity_logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM activity_logs l JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php include __DIR__ . '/../../components/header.php'; ?>
<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Histórico de Acesso</h1>
                <p>Veja os últimos acessos e ações do usuário.</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Últimos registros</h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Descrição</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo formatDate($log['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include __DIR__ . '/../../components/footer.php'; ?>
