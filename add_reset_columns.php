<?php
require_once 'config/database.php';

echo "<h1>Adding Password Reset Columns to 'users' table</h1>";

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add reset_token
    if (!hasColumn($db, 'users', 'reset_token')) {
        $db->exec("ALTER TABLE users ADD COLUMN reset_token TEXT");
        echo "<p style='color:green;'>✓ Column 'reset_token' added.</p>";
    } else {
        echo "<p style='color:blue;'>✓ Column 'reset_token' already exists.</p>";
    }

    // Add reset_expires
    if (!hasColumn($db, 'users', 'reset_expires')) {
        $db->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME");
        echo "<p style='color:green;'>✓ Column 'reset_expires' added.</p>";
    } else {
        echo "<p style='color:blue;'>✓ Column 'reset_expires' already exists.</p>";
    }

    echo "<h2 style='color:green;'>Schema update complete!</h2>";

} catch (Exception $e) {
    echo "<h1 style='color:red;'>An error occurred:</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

function hasColumn($db, $table, $column) {
    $stmt = $db->query("PRAGMA table_info($table)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['name'] === $column) {
            return true;
        }
    }
    return false;
}
?>
