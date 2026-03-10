<?php
require_once __DIR__ . '/includes/db.php';

$result = db_query("SELECT product_id, name, category FROM products WHERE name LIKE '%Tarpaulin%'");
print_r($result);
?>
