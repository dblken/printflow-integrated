<?php
require_once __DIR__ . '/includes/db.php';
echo "--- Categories ---\n";
$cats = db_query("SELECT * FROM material_categories");
echo json_encode($cats, JSON_PRETTY_PRINT);
echo "\n--- Materials ---\n";
$mats = db_query("SELECT * FROM materials");
echo json_encode($mats, JSON_PRETTY_PRINT);
echo "\n--- Job Orders Sample ---\n";
$jos = db_query("SELECT * FROM job_orders LIMIT 1");
echo json_encode($jos, JSON_PRETTY_PRINT);
?>
