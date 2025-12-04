<?php
require_once 'config/database.php';

echo "<h1>🔧 Configuração Multi-Tenancy</h1>";
echo "<p>Atualizando banco de dados para suportar múltiplos usuários...</p>";

$db = Database::getInstance()->getConnection();

try {
    // 1. Verificar se colunas user_id existem
    echo "<h2>1. Verificando estrutura do banco...</h2>";
    
    $tables = ['products', 'clients', 'productions'];
    foreach ($tables as $table) {
        $result = $db->query("PRAGMA table_info($table)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        $hasUserId = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'user_id') {
                $hasUserId = true;
                break;
            }
        }
        
        echo "<p>✓ Tabela <strong>$table</strong>: ";
        echo $hasUserId ? "<span style='color:green'>user_id OK</span>" : "<span style='color:red'>user_id AUSENTE</span>";
        echo "</p>";
    }
    
    // 2. Contar usuários
    echo "<h2>2. Usuários cadastrados:</h2>";
    $stmt = $db->query("SELECT id, name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><strong>{$user['role']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Atualizar dados existentes
    echo "<h2>3. Atualizando dados existentes...</h2>";
    
    // Se existir user_id=0 ou NULL, atribuir ao primeiro usuário (admin)
    $firstUserId = $users[0]['id'] ?? 1;
    
    foreach ($tables as $table) {
        try {
            // Contar registros sem user_id
            $count = $db->query("SELECT COUNT(*) FROM $table WHERE user_id IS NULL OR user_id = 0")->fetchColumn();
            
            if ($count > 0) {
                $db->exec("UPDATE $table SET user_id = $firstUserId WHERE user_id IS NULL OR user_id = 0");
                echo "<p>✓ <strong>$table</strong>: $count registros atualizados para user_id = $firstUserId</p>";
            } else {
                echo "<p>✓ <strong>$table</strong>: Nenhum registro precisa ser atualizado</p>";
            }
        } catch (Exception $e) {
            echo "<p>⚠ <strong>$table</strong>: " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. Mostrar estatísticas
    echo "<h2>4. Estatísticas por usuário:</h2>";
    
    foreach ($users as $user) {
        echo "<h3>{$user['name']} (#{$user['id']})</h3>";
        echo "<ul>";
        
        foreach ($tables as $table) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $count = $stmt->fetchColumn();
                
                $tableLabel = ucfirst($table);
                echo "<li><strong>$tableLabel:</strong> $count registros</li>";
            } catch (Exception $e) {
                echo "<li><strong>$tableLabel:</strong> Erro ao contar</li>";
            }
        }
        
        echo "</ul>";
    }
    
    // 5. Testar funções multi-tenancy
    echo "<h2>5. Testando funções multi-tenancy:</h2>";
    
    session_start();
    
    // Simular login como primeiro usuário
    $_SESSION['user_id'] = $firstUserId;
    $_SESSION['user_role'] = $users[0]['role'] ?? 'admin';
    
    require_once 'includes/multi_tenancy.php';
    
    echo "<p>✓ Usuário logado (simulado): #{$firstUserId}</p>";
    echo "<p>✓ Role: " . getCurrentUserRole() . "</p>";
    echo "<p>✓ É Admin? " . (isAdmin() ? 'Sim' : 'Não') . "</p>";
    echo "<p>✓ Filtro SQL: <code>" . getUserFilter() . "</code></p>";
    
    // 6. Resumo
    echo "<h2>6. ✅ Configuração Completa!</h2>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border: 2px solid #28a745;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>Sistema Multi-Tenancy Ativado!</h3>";
    echo "<p><strong>Como funciona:</strong></p>";
    echo "<ul>";
    echo "<li>Cada usuário vê APENAS seus próprios dados</li>";
    echo "<li>Admin vê TODOS os dados de TODOS os usuários</li>";
    echo "<li>Novos registros são automaticamente vinculados ao usuário logado</li>";
    echo "<li>Dados existentes foram atribuídos ao primeiro usuário (admin)</li>";
    echo "</ul>";
    
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Acesse a tela de login</li>";
    echo "<li>Entre com usuários diferentes (admin@admin.com / Admin@1234)</li>";
    echo "<li>Crie novos usuários no painel de administração</li>";
    echo "<li>Cada usuário verá apenas seus próprios produtos, clientes e produções</li>";
    echo "</ol>";
    
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>IR PARA LOGIN</a> ";
    echo "<a href='MULTI_TENANCY_GUIDE.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>VER GUIA DE USO</a>";
    echo "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border: 2px solid #dc3545;'>";
    echo "<h3 style='color: #721c24;'>❌ Erro na configuração</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        background: #f8f9fa;
    }
    h1 {
        color: #1e40af;
        border-bottom: 3px solid #3b82f6;
        padding-bottom: 10px;
    }
    h2 {
        color: #0f172a;
        margin-top: 30px;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    p, li {
        line-height: 1.6;
    }
    table {
        background: white;
        width: 100%;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    th {
        background: #1e40af;
        color: white;
        padding: 12px;
    }
    td {
        padding: 10px;
    }
    tr:nth-child(even) {
        background: #f8f9fa;
    }
    code {
        background: #e9ecef;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
    }
</style>
