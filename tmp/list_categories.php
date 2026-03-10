<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$cats = db_query("SELECT DISTINCT category FROM products");
$output = "";
foreach ($cats as $c) {
    $output .= "[" . $c['category'] . "]" . PHP_EOL;
}
file_put_contents(__DIR__ . '/cat_list.txt', $output);
echo "Categories saved to tmp/cat_list.txt" . PHP_EOL;
