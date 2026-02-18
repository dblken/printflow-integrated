<?php
// Standalone migration script
$conn = new mysqli('localhost', 'root', '122704', 'printflow');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$columns = [
    'cancellation_reason' => "VARCHAR(255) NULL AFTER payment_status",
    'cancellation_details' => "TEXT NULL AFTER cancellation_reason"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM orders LIKE '$col'");
    if ($check->num_rows == 0) {
        echo "Adding column $col... ";
        $res = $conn->query("ALTER TABLE orders ADD COLUMN $col $definition");
        echo $res ? "SUCCESS\n" : "FAILED: " . $conn->error . "\n";
    } else {
        echo "Column $col already exists.\n";
    }
}
?>
