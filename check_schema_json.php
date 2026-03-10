<?php
require_once __DIR__ . '/includes/db.php';
$res = db_query("DESCRIBE order_items");
echo json_encode($res, JSON_PRETTY_PRINT);
?>
