<?php
require_once __DIR__ . '/includes/db.php';
$res = db_query("DESCRIBE orders");
echo "<pre>";
print_r($res);
echo "</pre>";
?>
