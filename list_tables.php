<?php
require_once __DIR__ . '/includes/db.php';
$res = db_query("SHOW TABLES");
foreach($res as $row) {
    echo array_values($row)[0] . "\n";
}
?>
