<?php
require_once __DIR__ . '/includes/db.php';
$tables = ['inventory_items', 'inventory_categories', 'job_orders'];
foreach($tables as $t) {
    echo "--- $t ---\n";
    $res = db_query("DESCRIBE $t");
    foreach($res as $col) {
        echo "{$col['Field']} | {$col['Type']}\n";
    }
}
?>
