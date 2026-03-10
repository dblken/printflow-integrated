<?php
require __DIR__ . '/includes/functions.php';
global $conn;
$res = $conn->query("DELETE FROM job_orders");
if ($res) {
    echo "Table CLEARED via DELETE.\n";
} else {
    echo "DELETE failed: " . $conn->error . "\n";
}
$rows = db_query("SELECT COUNT(*) as c FROM job_orders");
echo "Count after clear: " . $rows[0]['c'] . "\n";
