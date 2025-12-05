<?php
// Página de gerenciamento de permissões por papel
require_once __DIR__ . '/config/config.php';
$permissions = require __DIR__ . '/config/permissions.php';

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPermissions = [];
    foreach ($permissions as $role => $perms) {
        foreach ($perms as $perm => $value) {
            $newPermissions[$role][$perm] = isset($_POST[$role . '_' . $perm]) ? true : false;
        }
    }
    // Salva como PHP
    $content = "<?php\nreturn " . var_export($newPermissions, true) . ";\n";
    file_put_contents(__DIR__ . '/../config/permissions.php', $content);
    header('Location: permissions_manager.php?success=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="icon" type="image/png" href="images/icon-seictech.png">
    <meta charset="UTF-8">
    <title>Gerenciar Permissões</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 1rem; box-shadow: 0 2px 8px #0001; padding: 2rem; }
        h1 { color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.7rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; color: #6b7280; }
        tr:last-child td { border-bottom: none; }
        .role-title { font-weight: bold; color: #374151; margin-top: 2rem; }
        .btn-save { background: #4f46e5; color: #fff; border: none; padding: 0.7rem 2rem; border-radius: 0.5rem; font-size: 1rem; cursor: pointer; margin-top: 1.5rem; }
        .btn-save:hover { background: #3730a3; }
        .success { color: #065f46; background: #ecfdf5; border: 1px solid #d1fae5; padding: 0.7rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Permissões por Papel</h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="success">Permissões salvas com sucesso!</div>
        <?php endif; ?>
        <form method="post">
            <?php foreach ($permissions as $role => $perms): ?>
                <div class="role-title">Permissões para: <span style="color:#4f46e5; text-transform:uppercase;"><?= htmlspecialchars($role) ?></span></div>
                <table>
                    <thead>
                        <tr>
                            <th>Permissão</th>
                            <th>Ativo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perms as $perm => $value): ?>
                        <tr>
                            <td><?= htmlspecialchars($perm) ?></td>
                            <td>
                                <input type="checkbox" name="<?= $role . '_' . $perm ?>" <?= $value ? 'checked' : '' ?> />
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            <button type="submit" class="btn-save">Salvar Permissões</button>
        </form>
    </div>
</body>
</html>
