<?php
require_once 'includes/db.php';
$tables = ['branches', 'inv_items', 'inv_categories', 'job_orders', 'job_order_materials', 'inventory_transactions'];
echo "TARGET TABLES CHECK:\n";
foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    if ($res->num_rows > 0) {
        echo "[EXISTS] $t\n";
    } else {
        echo "[MISSING] $t\n";
    }
}
?>
