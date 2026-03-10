<?php
require_once 'includes/db.php';

echo "Adding payment_type column to orders table...\n";

$sql = "ALTER TABLE orders 
        ADD COLUMN payment_type ENUM('full_payment', '50_percent', 'upon_pickup') DEFAULT 'full_payment' AFTER payment_status";

$success = db_execute($sql);

if ($success) {
    echo "Database schema updated successfully.\n";
} else {
    echo "Failed to update database schema: " . $conn->error . "\n";
}
?>
