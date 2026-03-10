<?php
require_once 'includes/db.php';

echo "Adding customer_type column to customers table...\n";

$sql = "ALTER TABLE customers ADD COLUMN IF NOT EXISTS customer_type ENUM('new', 'regular') DEFAULT 'new' AFTER is_restricted";

$success = db_execute($sql);

if ($success) {
    echo "Successfully added customer_type column.\n";
} else {
    // If ALTER TABLE ... ADD COLUMN ... IF NOT EXISTS is not supported in this MySQL version, 
    // we might need a more manual check, but most modern ones support it or we can just try.
    echo "Check if column exists or failed to add: " . $conn->error . "\n";
}
?>
