<?php
require_once __DIR__ . '/includes/db.php';
$res = db_query("SELECT product_id, name, category, price FROM products WHERE price = 0");
foreach($res as $p) {
    echo "ID: {$p['product_id']} | Name: {$p['name']} | Cat: {$p['category']} | Price: {$p['price']}\n";
}
?>
