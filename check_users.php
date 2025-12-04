<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query('SELECT id, name, email, role, status FROM users');

echo "<h2>Usuários no Sistema:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Role</th><th>Status</th></tr>";

while($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td>{$user['status']}</td>";
    echo "</tr>";
}

echo "</table>";

// Verificar se existe admin
$adminStmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$adminCount = $adminStmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Total de Admins:</strong> {$adminCount['count']}</p>";

// Se não existir admin, criar
if ($adminCount['count'] == 0) {
    echo "<p style='color: red;'>NENHUM ADMIN ENCONTRADO! Criando usuário admin...</p>";
    
    $hashedPassword = password_hash('Admin@1234', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
    $stmt->execute(['Administrador', 'admin@admin.com', $hashedPassword]);
    
    echo "<p style='color: green;'>✓ Usuário admin criado com sucesso!</p>";
    echo "<p><strong>Email:</strong> admin@admin.com</p>";
    echo "<p><strong>Senha:</strong> Admin@1234</p>";
}
?>
