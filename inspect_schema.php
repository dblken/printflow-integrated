<?php
require_once 'includes/db.php';

$tables = ['order_messages', 'orders', 'customers', 'users'];

foreach ($tables as $table) {
    echo "--- $table ---\n";
    $columns = db_query("DESCRIBE $table");
    if ($columns) {
        foreach ($columns as $column) {
            echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']} - {$column['Default']} - {$column['Extra']}\n";
        }
    } else {
        echo "Table not found!\n";
    }
    echo "\n";
}
