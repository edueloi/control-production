<?php
$currentUser = getCurrentUser();
?>
<nav class="navbar">
    <div class="navbar-left" style="display: flex; align-items: center; gap: 15px;">
        <button id="mobileMenuBtn" class="btn-icon mobile-only" style="background: none; border: none; font-size: 24px; color: var(--primary-color); cursor: pointer; display: none;">
            <i class="fas fa-bars"></i>
        </button>
        <div class="navbar-brand">
            <i class="fas fa-industry"></i>
            <span><?php echo APP_NAME; ?></span>
        </div>
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

<style>
    @media (max-width: 1024px) {
        #mobileMenuBtn {
            display: block !important;
        }
    }
</style>
