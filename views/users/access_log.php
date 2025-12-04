<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';
$filterUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Lógica de busca de logs (mantida do teu código original)
if ($userRole === 'admin' && $filterUserId) {
    $stmt = $db->prepare("SELECT l.*, u.name as user_name, u.email as user_email FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT 50");
    $stmt->execute([$filterUserId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar nome do usuário filtrado para o título
    $stmtUser = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmtUser->execute([$filterUserId]);
    $filterUserName = $stmtUser->fetchColumn();
} elseif ($userRole === 'admin') {
    $logs = $db->query("SELECT l.*, u.name as user_name, u.email as user_email FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    $filterUserName = null;
} else {
    $stmt = $db->prepare("SELECT l.*, u.name as user_name, u.email as user_email FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filterUserName = null;
}

// Função auxiliar para cor da ação
function getActionColor($action) {
    $action = strtolower($action);
    if (strpos($action, 'login') !== false) return 'blue';
    if (strpos($action, 'delete') !== false || strpos($action, 'excluir') !== false) return 'red';
    if (strpos($action, 'create') !== false || strpos($action, 'criar') !== false) return 'green';
    if (strpos($action, 'update') !== false || strpos($action, 'editar') !== false) return 'yellow';
    return 'gray';
}
?>

<?php include __DIR__ . '/../../components/header.php'; ?>

<style>
    :root {
        --primary-color: #4f46e5;
        --bg-card: #ffffff;
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --border-color: #e5e7eb;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .page-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .table-container {
        background: var(--bg-card);
        border-radius: 1rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .custom-table th {
        background: #f9fafb;
        text-align: left;
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 600;
        letter-spacing: 0.05em;
    }

    .custom-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
        font-size: 0.95rem;
        vertical-align: middle;
    }

    .custom-table tr:hover { background-color: #f9fafb; }

    /* Badges de Ação */
    .action-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        text-transform: uppercase;
    }
    .badge-blue { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
    .badge-green { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
    .badge-red { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
    .badge-yellow { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }
    .badge-gray { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }

    .user-mini { display: flex; align-items: center; gap: 0.5rem; }
    .user-avatar { width: 24px; height: 24px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold; }

    .btn-back {
        background: white;
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .btn-back:hover { background: #f9fafb; color: var(--text-main); }
</style>

<div class="main-layout">
    <?php include __DIR__ . '/../../components/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include __DIR__ . '/../../components/navbar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../components/alerts.php'; ?>
            
            <div class="page-header-flex">
                <div>
                    <h1 style="margin:0; font-size: 1.8rem; color: var(--text-main);">
                        <i class="fas fa-history" style="color: var(--primary-color);"></i> 
                        <?php echo $filterUserName ? 'Atividades de ' . htmlspecialchars($filterUserName) : 'Histórico Global'; ?>
                    </h1>
                    <p style="margin: 0.5rem 0 0; color: var(--text-muted);">
                        Monitoramento de segurança e auditoria de ações.
                    </p>
                </div>

                <?php if ($filterUserId): ?>
                        <a href="index.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Voltar para Usuários
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th width="20%">Data / Hora</th>
                                <th width="25%">Usuário</th>
                                <th width="15%">Ação</th>
                                <th width="25%">Detalhes</th>
                                <th width="15%">IP Origem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 3rem;">
                                        <div style="color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-search" style="font-size: 2rem; opacity: 0.3;"></i>
                                            <span>Nenhum registro de atividade encontrado.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php 
                                        $badgeColor = getActionColor($log['action']); 
                                        $initial = strtoupper(substr($log['user_name'] ?? '?', 0, 1));
                                    ?>
                                    <tr>
                                        <td style="font-family: monospace; color: var(--text-muted);">
                                            <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="user-mini">
                                                <div class="user-avatar"><?php echo $initial; ?></div>
                                                <div>
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($log['user_name'] ?? 'Usuário Removido'); ?></div>
                                                    <?php if(isset($log['user_email'])): ?>
                                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($log['user_email']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="action-badge badge-<?php echo $badgeColor; ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted);">
                                            <?php echo htmlspecialchars($log['description']); ?>
                                        </td>
                                        <td>
                                            <span style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.85rem;">
                                                <?php echo htmlspecialchars($log['ip_address']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../../components/footer.php'; ?>