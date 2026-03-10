<?php
require_once 'includes/db.php';

$tables = ['order_items', 'service_orders'];
$columns = [
    'width' => 'DECIMAL(10,2) DEFAULT NULL',
    'height' => 'DECIMAL(10,2) DEFAULT NULL',
    'thickness' => 'VARCHAR(50) DEFAULT NULL',
    'stand_type' => 'VARCHAR(100) DEFAULT NULL',
    'lamination' => 'VARCHAR(100) DEFAULT NULL',
    'cut_type' => 'VARCHAR(100) DEFAULT NULL',
    'design_notes' => 'TEXT DEFAULT NULL',
    'design_file_path' => 'VARCHAR(255) DEFAULT NULL'
];

foreach ($tables as $table) {
    foreach ($columns as $col => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$col` $definition";
            if ($conn->query($sql)) {
                echo "Added $col to $table\n";
            } else {
                echo "Error adding $col to $table: " . $conn->error . "\n";
            }
        } else {
            echo "Column $col already exists in $table\n";
        }
    }
}
?>
