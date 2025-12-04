<?php
$currentUser = getCurrentUser();
?>
<nav class="navbar">
    <div class="navbar-brand">
        <i class="fas fa-industry"></i>
        <span><?php echo APP_NAME; ?></span>
    </div>
    <div class="navbar-user">
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($currentUser['name']); ?></span>
        </div>
        <a href="<?php echo BASE_URL; ?>logout.php" class="btn-logout" title="Sair">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>
