<?php
require_once __DIR__ . '/includes/db.php';
$mats = db_query("SELECT material_id, material_name, category_id FROM materials");
foreach($mats as $m) {
    echo "ID: {$m['material_id']} | Name: {$m['material_name']} | Cat: {$m['category_id']}\n";
}
?>
