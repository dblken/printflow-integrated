<?php
require __DIR__ . '/includes/functions.php';
$id = 310;
$order = db_query("
    SELECT jo.*,
           c.first_name, c.last_name, c.email, c.phone
    FROM job_orders jo
    LEFT JOIN customers c ON jo.customer_id = c.customer_id
    WHERE jo.id = ?
", 'i', [$id]);

echo "Result count for ID $id: " . count($order) . "\n";
if (!empty($order)) {
    print_r($order[0]);
} else {
    echo "Query returned empty result.\n";
    // Check if job exists WITHOUT join
    $simple = db_query("SELECT * FROM job_orders WHERE id = ?", 'i', [$id]);
    echo "Exists without join: " . (empty($simple) ? 'NO' : 'YES') . "\n";
}
