<?php
header('Content-Type: text/plain');
require_once 'config/database.php';

echo "===============================\n";
echo "DATABASE SCHEMA (users table)\n";
echo "===============================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $table = 'users';

    $stmt = $db->query("PRAGMA table_info($table)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($columns)) {
        echo "---- TABLE: $table (does not exist or is empty) ----\n\n";
    } else {
        echo "---- TABLE: $table ----\n";
        foreach ($columns as $column) {
            echo str_pad($column['name'], 25) . " | " . $column['type'] . "\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Failed to connect to the database: " . $e->getMessage() . "\n";
}
?>
