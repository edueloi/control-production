<?php
require_once __DIR__ . '/config/config.php';

// Redirecionar para login se não estiver logado, ou dashboard se estiver
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
?>
