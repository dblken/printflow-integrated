<?php
require 'includes/db.php';
$jo = db_query("SELECT id, order_item_id FROM job_orders WHERE id = 319");
echo json_encode($jo);
?>
