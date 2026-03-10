<?php
require_once 'includes/db.php';

// Check if column exists
$result = db_query("SHOW COLUMNS FROM orders LIKE 'production_started_at'");
if (empty($result)) {
    echo "Column production_started_at does not exist. Adding it...\n";
    $success = db_execute("ALTER TABLE orders ADD COLUMN production_started_at DATETIME NULL AFTER order_date");
    if ($success) {
        echo "Column added successfully.\n";
    } else {
        echo "Failed to add column.\n";
    }
} else {
    echo "Column production_started_at already exists.\n";
}
?>
