<?php
require 'includes/db.php';
// Find the order item id for order #69
$oi = db_query("SELECT order_item_id FROM order_items WHERE order_id = 69 LIMIT 1");
if (!empty($oi)) {
    $oi_id = $oi[0]['order_item_id'];
    echo "Updating JO 319 with order_item_id $oi_id\n";
    db_execute("UPDATE job_orders SET order_item_id = ? WHERE id = 319", 'i', [$oi_id]);
} else {
    echo "No order item found for order 69\n";
}
?>
