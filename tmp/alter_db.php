<?php
require 'includes/db.php';
$sql = "ALTER TABLE order_items MODIFY COLUMN product_id INT NULL";
if ($conn->query($sql)) {
    echo "Success: Table altered\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}
