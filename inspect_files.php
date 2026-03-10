<?php
require __DIR__ . '/includes/functions.php';
$rows = db_query("DESCRIBE job_order_files");
foreach($rows as $r) {
    echo $r['Field'] . PHP_EOL;
}
