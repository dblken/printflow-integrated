<?php
require_once __DIR__ . '/includes/db.php';

$result = db_query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
print_r($result);
?>
