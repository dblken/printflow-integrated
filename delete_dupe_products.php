<?php
require_once __DIR__ . '/includes/db.php';

// Keep the first (lowest product_id) of each duplicate group
$sql = "DELETE t1 FROM products t1
        INNER JOIN products t2 
        WHERE t1.product_id > t2.product_id AND t1.name = t2.name;";

$success = db_execute($sql);
if ($success) {
    echo "Duplicate products removed successfully.\n";
} else {
    global $conn;
    echo "Error removing duplicates: " . $conn->error . "\n";
}
?>
