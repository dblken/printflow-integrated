<?php
require __DIR__ . '/includes/functions.php';
$rows = db_query("SELECT COUNT(*) as c FROM job_orders");
echo "FINAL JOB ORDERS COUNT: " . $rows[0]['c'] . "\n";
$rows = db_query("SELECT status, COUNT(*) as c FROM job_orders GROUP BY status");
foreach($rows as $r) {
    echo $r['status'] . ": " . $r['c'] . "\n";
}
