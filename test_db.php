<?php
require_once __DIR__ . '/includes/db.php';

$customer_id = 1; // dummy
$subtotal = 0.00;
$downpayment_amount = 0.00;
$payment_status = 'Pending Payment';
$payment_type = 'upon_pickup';
$notes = null;

$order_sql = "INSERT INTO orders (customer_id, order_date, total_amount, downpayment_amount, status, payment_status, payment_type, notes)
              VALUES (?, NOW(), ?, ?, 'Pending Review', ?, ?, ?)";

$result = db_execute($order_sql, 'iddsss', [$customer_id, $subtotal, $downpayment_amount, $payment_status, $payment_type, $notes]);

if ($result) {
    echo "Success! Order ID: " . $result . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
