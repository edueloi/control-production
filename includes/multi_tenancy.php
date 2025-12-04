<?php
/**
 * Funções auxiliares para Multi-Tenancy
 * Garante que cada usuário veja apenas seus próprios dados
 */

/**
 * Retorna o ID do usuário logado
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Retorna o papel do usuário logado
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? 'user';
}

/**
 * Verifica se o usuário é admin
 */
function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

/**
 * Adiciona condição WHERE para filtrar por usuário
 * Se for admin, não filtra (vê tudo)
 */
function getUserFilter($tableAlias = '') {
    if (isAdmin()) {
        return '1=1'; // Admin vê tudo
    }
    
    $userId = getCurrentUserId();
    $prefix = $tableAlias ? $tableAlias . '.' : '';
    
    return "{$prefix}user_id = {$userId}";
}

/**
 * Adiciona condição WHERE na query
 */
function addUserFilterToQuery($query, $tableAlias = '') {
    $filter = getUserFilter($tableAlias);
    
    if (stripos($query, 'WHERE') !== false) {
        return str_replace('WHERE', "WHERE {$filter} AND", $query);
    } else {
        // Encontrar posição para adicionar WHERE
        $keywords = ['GROUP BY', 'ORDER BY', 'LIMIT', 'OFFSET'];
        $position = false;
        
        foreach ($keywords as $keyword) {
            $pos = stripos($query, $keyword);
            if ($pos !== false && ($position === false || $pos < $position)) {
                $position = $pos;
            }
        }
        
        if ($position !== false) {
            return substr_replace($query, " WHERE {$filter} ", $position, 0);
        } else {
            return $query . " WHERE {$filter}";
        }
    }
}

/**
 * Verifica se o registro pertence ao usuário atual
 */
function checkOwnership($db, $table, $id) {
    if (isAdmin()) {
        return true; // Admin tem acesso a tudo
    }
    
    $userId = getCurrentUserId();
    $stmt = $db->prepare("SELECT user_id FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $record && $record['user_id'] == $userId;
}

/**
 * Retorna array com informações do usuário para logging
 */
function getUserLogInfo() {
    return [
        'user_id' => getCurrentUserId(),
        'user_name' => $_SESSION['user_name'] ?? 'Unknown',
        'user_email' => $_SESSION['user_email'] ?? 'Unknown',
        'user_role' => getCurrentUserRole(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
}

/**
 * Registra atividade no log
 */
function logActivity($db, $action, $description, $entityType = null, $entityId = null) {
    try {
        $userId = getCurrentUserId();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entityType, $entityId, $description, $ip]);
    } catch (Exception $e) {
        // Não interromper o fluxo se log falhar
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

/**
 * Valida se usuário pode acessar um recurso
 */
function canAccess($requiredRole = 'user') {
    $userRole = getCurrentUserRole();
    
    $roleHierarchy = [
        'admin' => 3,
        'manager' => 2,
        'user' => 1
    ];
    
    $userLevel = $roleHierarchy[$userRole] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 1;
    
    return $userLevel >= $requiredLevel;
}

/**
 * Exibe mensagem de acesso negado e redireciona
 */
function accessDenied($message = 'Você não tem permissão para acessar este recurso.') {
    setErrorMessage($message);
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit;
}

/**
 * Middleware para proteger páginas
 */
function requireRole($role = 'user') {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    
    if (!canAccess($role)) {
        accessDenied();
    }
}

/**
 * Retorna SQL para contar registros do usuário
 */
function getUserCountQuery($table) {
    if (isAdmin()) {
        return "SELECT COUNT(*) as count FROM {$table}";
    }
    
    $userId = getCurrentUserId();
    return "SELECT COUNT(*) as count FROM {$table} WHERE user_id = {$userId}";
}

/**
 * Formata dados para exibição considerando multi-tenancy
 */
function formatRecordForDisplay($record) {
    // Se for admin, mostrar nome do proprietário
    if (isAdmin() && isset($record['user_id'])) {
        $record['_owner_info'] = 'ID: ' . $record['user_id'];
    }
    
    return $record;
}
?>
