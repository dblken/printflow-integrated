<?php
require_once 'includes/db.php';

$sql = "UPDATE customers SET customer_type = 'regular'";
$success = db_execute($sql);

if ($success) {
    echo "All customers set to 'regular'.\n";
} else {
    echo "Failed to update customers: " . $conn->error . "\n";
}
?>
