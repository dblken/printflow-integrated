<?php
require_once __DIR__ . '/includes/db.php';
$out = [];
$out[] = "Categories:";
$cats = db_query("SELECT * FROM material_categories");
foreach($cats as $c) $out[] = "ID: {$c['category_id']} | Name: {$c['category_name']}";

$out[] = "\nInks/Materials Search:";
$mats = db_query("SELECT material_id, material_name, category_id FROM materials");
foreach($mats as $m) $out[] = "ID: {$m['material_id']} | Name: {$m['material_name']} | CatID: {$m['category_id']}";

$out[] = "\nJob Orders Details:";
$cols = db_query("DESCRIBE job_orders");
foreach($cols as $c) $out[] = "{$c['Field']} | {$c['Type']}";

file_put_contents('schema_dump.txt', implode("\n", $out));
?>
