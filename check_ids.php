<?php
require __DIR__ . '/includes/functions.php';
$rows = db_query("SELECT id FROM job_orders ORDER BY id DESC LIMIT 10");
foreach($rows as $r) {
    echo "ID: " . $r['id'] . "\n";
}
echo "---\n";
// Also check if any of the problematic IDs exist
$check_ids = [310, 317, 321];
foreach($check_ids as $id) {
    $res = db_query("SELECT id FROM job_orders WHERE id = ?", 'i', [$id]);
    echo "ID $id exists: " . (empty($res) ? 'NO' : 'YES') . "\n";
}
