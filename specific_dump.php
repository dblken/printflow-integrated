<?php
require_once __DIR__ . '/includes/db.php';
echo "Categories:\n";
$cats = db_query("SELECT * FROM material_categories");
foreach($cats as $c) echo "ID: {$c['category_id']} | Name: {$c['category_name']}\n";

echo "\nInks/Materials Search:\n";
$mats = db_query("SELECT material_id, material_name, category_id FROM materials WHERE material_name LIKE '%Ink%' OR material_name LIKE '%Carbon%' OR material_name LIKE '%Tape%'");
foreach($mats as $m) echo "ID: {$m['material_id']} | Name: {$m['material_name']} | CatID: {$m['category_id']}\n";

echo "\nJob Orders Details:\n";
$cols = db_query("DESCRIBE job_orders");
foreach($cols as $c) echo "{$c['Field']} | {$c['Type']}\n";
?>
