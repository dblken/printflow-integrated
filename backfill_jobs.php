<?php
require __DIR__ . '/includes/functions.php';

// Backfill job_orders from existing orders
// Fields needed for job_orders:
// job_title, service_type, customer_id, width_ft, height_ft, quantity, status, customer_type, estimated_total, created_at

$query = "
    SELECT 
        oi.order_item_id,
        oi.order_id,
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        oi.customization_data,
        oi.width AS item_width,
        oi.height AS item_height,
        o.customer_id,
        o.status AS order_status,
        o.created_at AS order_created_at,
        c.customer_type,
        p.name AS product_name,
        p.category AS product_category
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    ORDER BY o.order_id ASC
";

$items = db_query($query);

if (!$items) {
    die("No items found to backfill or query failed.\n");
}

echo "Starting backfill of " . count($items) . " items...\n\n";

$inserted = 0;
foreach ($items as $item) {
    $job_title = $item['product_name'] ?? 'Custom Order';
    $service_type = $item['product_category'] ?? 'General';
    
    // Status mapping
    $status = 'PENDING';
    $order_status = strtoupper($item['order_status']);
    if (in_array($order_status, ['PENDING', 'PENDING REVIEW'])) $status = 'PENDING';
    elseif (strpos($order_status, 'APPROVE') !== false) $status = 'APPROVED';
    elseif (strpos($order_status, 'PRODUCTION') !== false) $status = 'IN_PRODUCTION';
    elseif (strpos($order_status, 'COMPLETED') !== false) $status = 'COMPLETED';
    elseif (strpos($order_status, 'CANCEL') !== false) $status = 'CANCELLED';
    elseif (strpos($order_status, 'PAY') !== false) $status = 'TO_PAY';
    elseif (strpos($order_status, 'RECEIVE') !== false) $status = 'TO_RECEIVE';

    // Dimension parsing
    $width_ft = (float)($item['item_width'] ?? 0);
    $height_ft = (float)($item['item_height'] ?? 0);
    
    if ($width_ft == 0 || $height_ft == 0) {
        $custom = json_decode($item['customization_data'] ?? '{}', true) ?: [];
        $dims = $custom['dimensions'] ?? '';
        if ($dims && strpos(strtolower($dims), 'x') !== false) {
            $parts = array_map('trim', explode('x', strtolower($dims)));
            $width_ft  = (float)($parts[0] ?? 0);
            $height_ft = (float)($parts[1] ?? 0);
        }
    }

    $cust_type = ($item['customer_type'] === 'regular') ? 'REGULAR' : 'NEW';
    $estimated_total = (float)$item['unit_price'] * (int)$item['quantity'];
    $created_at = $item['order_created_at'];

    $res = db_execute(
        "INSERT INTO job_orders (job_title, service_type, customer_id, width_ft, height_ft, quantity, status, customer_type, estimated_total, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        "ssiidissds",
        [
            $job_title,
            $service_type,
            $item['customer_id'],
            $width_ft,
            $height_ft,
            (int)$item['quantity'],
            $status,
            $cust_type,
            $estimated_total,
            $created_at
        ]
    );

    if ($res) {
        $inserted++;
        echo "Inserted Job #$inserted: $job_title ($service_type)\n";
    } else {
        echo "Failed to insert item ID: " . $item['order_item_id'] . "\n";
    }
}

echo "\nBackfill complete. $inserted job orders created.\n";
