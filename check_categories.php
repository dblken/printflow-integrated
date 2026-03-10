<?php
require_once __DIR__ . '/includes/db.php';
$categories = db_query("SELECT DISTINCT category FROM products");
echo "CATEGORIES:\n";
print_r($categories);
$services = db_query("SELECT product_id, name, category, price FROM products WHERE category IN ('Tarpaulin', 'T-Shirt', 'Stickers', 'Sintraboard', 'Signage', 'Merchandise')");
echo "\nSERVICE PRODUCTS:\n";
print_r($services);
?>
