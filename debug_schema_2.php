<?php
require_once __DIR__ . '/includes/db.php';

$tables = ['products', 'notifications', 'order_items'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Default']}\n";
        }
    } else {
        echo "Error describing $table: " . $conn->error . "\n";
    }
    echo "\n";
}
