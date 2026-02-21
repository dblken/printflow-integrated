<?php
require 'includes/db.php';
$tables = ['orders', 'order_details', 'products'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Default: {$row['Default']}\n";
        }
    } else {
        echo "Error describing table: " . $conn->error . "\n";
    }
    echo "\n";
}
