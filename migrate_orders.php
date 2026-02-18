<?php
require_once __DIR__ . '/includes/db.php';
global $conn;

// Add cancellation columns to orders table
$columns = [
    'cancellation_reason' => "VARCHAR(255) NULL AFTER payment_status",
    'cancellation_details' => "TEXT NULL AFTER cancellation_reason"
];

foreach ($columns as $col => $definition) {
    // Check if column exists
    $check = db_query("SHOW COLUMNS FROM orders LIKE ?", 's', [$col]);
    if (empty($check)) {
        echo "Adding column $col... ";
        $res = $conn->query("ALTER TABLE orders ADD COLUMN $col $definition");
        echo $res ? "SUCCESS\n" : "FAILED: " . $conn->error . "\n";
    } else {
        echo "Column $col already exists.\n";
    }
}
?>
