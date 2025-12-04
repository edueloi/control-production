<?php
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
if (!$currentUser) {
    $currentUser = $_SESSION['user_data'] ?? ['name' => $_SESSION['user_name'] ?? 'Utilizador'];
}
?>
<nav class="navbar">
    <div class="navbar-left">
        <button class="mobile-toggle" id="sidebarToggleMobile">
            <i class="fas fa-bars"></i>
        </button>
        
        <h3 style="margin: 0; font-size: 1.1rem; color: #334155; font-weight: 600;">
            <?php echo isset($pageTitle) ? $pageTitle : 'Painel de Controlo'; ?>
        </h3>
    </div>

    <div class="navbar-user">
        <div class="user-pill">
            <span class="user-name">
                <?php echo htmlspecialchars($currentUser['name'] ?? 'Utilizador'); ?>
            </span>
            <div style="width: 32px; height: 32px; background: #cbd5e1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user" style="color: #64748b; font-size: 0.9rem;"></i>
            </div>
        </div>
        
        <a href="<?php echo BASE_URL; ?>logout.php" class="btn-logout" title="Sair do Sistema">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>
