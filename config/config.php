<?php
// Configurações gerais do sistema
define('APP_NAME', 'Sistema de Controle de Produção');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/karen_site/flavio/control_production/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Configurações de sessão
// Verifica se a sessão já não foi iniciada antes de tentar configurar/iniciar
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Incluir funções de multi-tenancy se necessário
// require_once __DIR__ . '/../includes/multi_tenancy.php'; // Descomente se esse arquivo existir

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// --- FUNÇÕES GERAIS ---

// Função para verificar se o usuário está logado
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Função para redirecionar se não estiver logado
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }
}

// Função para obter usuário atual
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'] ?? 'Usuário',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'user'
            ];
        }
        return null;
    }
}

// Função para formatar moeda
if (!function_exists('formatMoney')) {
    function formatMoney($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}

// Função para formatar data
if (!function_exists('formatDate')) {
    function formatDate($date) {
        if (!$date) return '-';
        return date('d/m/Y H:i', strtotime($date));
    }
}

// Função para validar CPF
if (!function_exists('validateCPF')) {
    function validateCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11) return false;
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }
}

// Função para validar CNPJ
if (!function_exists('validateCNPJ')) {
    function validateCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) return false;
        
        for ($t = 12; $t < 14; $t++) {
            for ($d = 0, $m = ($t - 7), $i = 0; $i < $t; $i++) {
                $d += $cnpj[$i] * $m;
                $m = ($m == 2 ? 9 : --$m);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cnpj[$t] != $d) return false;
        }
        return true;
    }
}

// Função para sanitizar input
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Funções de Mensagem Flash
if (!function_exists('setSuccessMessage')) {
    function setSuccessMessage($message) {
        $_SESSION['success_message'] = $message;
    }
}

if (!function_exists('setErrorMessage')) {
    function setErrorMessage($message) {
        $_SESSION['error_message'] = $message;
    }
}

if (!function_exists('getSuccessMessage')) {
    function getSuccessMessage() {
        if (isset($_SESSION['success_message'])) {
            $message = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            return $message;
        }
        return null;
    }
}

if (!function_exists('getErrorMessage')) {
    function getErrorMessage() {
        if (isset($_SESSION['error_message'])) {
            $message = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            return $message;
        }
        return null;
    }
}
?>