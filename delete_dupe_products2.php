<?php
require_once __DIR__ . '/includes/db.php';

// Keep the first (lowest product_id) of each duplicate group
// But only delete those without any order items
$sql = "DELETE t1 FROM products t1
        INNER JOIN products t2 
        WHERE t1.product_id > t2.product_id 
        AND t1.name = t2.name
        AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.product_id = t1.product_id);";

$success = db_execute($sql);
if ($success) {
    echo "Duplicate products without orders removed successfully.\n";
} else {
    global $conn;
    echo "Error removing duplicates: " . $conn->error . "\n";
}
?>
