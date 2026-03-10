<?php
require_once __DIR__ . '/includes/db.php';
$res = db_query("SHOW TABLES");
$tables = [];
foreach($res as $row) {
    $tables[] = array_values($row)[0];
}
echo implode("\n", $tables);
?>
