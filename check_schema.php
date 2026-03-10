<?php
require_once __DIR__ . '/includes/db.php';
$res = db_query("DESCRIBE order_items");
foreach($res as $col) {
    echo "{$col['Field']} | {$col['Type']}\n";
}
?>
