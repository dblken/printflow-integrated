<?php
require 'includes/db.php';
$jo = db_query("SELECT * FROM job_orders WHERE id = 319");
$cid = $jo[0]['customer_id'];
$created = $jo[0]['created_at'];
echo "JO 319 Created info: CID=$cid, Created=$created\n";
$oi = db_query("SELECT oi.*, o.created_at as order_created 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.order_id 
                WHERE o.customer_id = ?", 'i', [$cid]);
echo json_encode($oi);
?>
