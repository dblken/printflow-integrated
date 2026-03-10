<?php
require_once __DIR__ . '/../includes/db.php';

try {
    global $conn;
    
    // 1. Add order_item_id column if it doesn't exist
    $res = db_query("SHOW COLUMNS FROM job_orders LIKE 'order_item_id'");
    if (empty($res)) {
        db_execute("ALTER TABLE job_orders ADD COLUMN order_item_id INT DEFAULT NULL AFTER customer_id");
        db_execute("ALTER TABLE job_orders ADD INDEX idx_order_item_id (order_item_id)");
        echo "Column order_item_id added to job_orders.\n";
    } else {
        echo "Column order_item_id already exists in job_orders.\n";
    }
    
    // 2. Backfill: matching orders and job_orders created around the same time by the same customer
    // This isn't perfect but helps preserve data. A more rigorous way matches exactly on qty and price.
    
    // Approach: for each jo that has order_item_id IS NULL:
    $unlinked_jos = db_query("SELECT id, customer_id, quantity, estimated_total, service_type, created_at FROM job_orders WHERE order_item_id IS NULL AND customer_id IS NOT NULL");
    
    $linkCount = 0;
    foreach ($unlinked_jos as $jo) {
        $cid = $jo['customer_id'];
        $qty = $jo['quantity'];
        $price = (float)$jo['estimated_total'] / max(1, $qty);
        // Find order items around the same time (+/- 10 minutes)
        // Let's just find the closest one
        $q = "SELECT oi.order_item_id 
              FROM order_items oi 
              JOIN orders o ON oi.order_id = o.order_id 
              WHERE o.customer_id = ? 
                AND oi.quantity = ? 
                AND ABS(TIMESTAMPDIFF(MINUTE, o.created_at, ?)) <= 30
              ORDER BY ABS(TIMESTAMPDIFF(MINUTE, o.created_at, ?)) ASC
              LIMIT 1";
        $matches = db_query($q, 'iiss', [$cid, $qty, $jo['created_at'], $jo['created_at']]);
        if (!empty($matches)) {
            $oi_id = $matches[0]['order_item_id'];
            db_execute("UPDATE job_orders SET order_item_id = ? WHERE id = ?", 'ii', [$oi_id, $jo['id']]);
            $linkCount++;
        }
    }
    
    echo "Backfilled $linkCount job_orders to their corresponding order_items.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
