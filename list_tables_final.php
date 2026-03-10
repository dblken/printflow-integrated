<?php
require_once 'includes/db.php';
$tables = db_query("SHOW TABLES");
foreach ($tables as $t) {
    echo array_values($t)[0] . "\n";
}
