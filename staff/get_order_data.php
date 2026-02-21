<?php
/**
 * AJAX: Get Order Data (Staff)
 * Returns full order details as JSON for modal display
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Staff');

header('Content-Type: application/json');

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// Get order with customer info
$order_result = db_query("
    SELECT o.*,
           c.first_name as cust_first, c.last_name as cust_last,
           c.email as cust_email, c.contact_number as cust_phone,
           c.customer_id as cust_id
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_id = ?
", 'i', [$order_id]);

if (empty($order_result)) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}
$order = $order_result[0];

// Get order items
$items = db_query("
    SELECT oi.*, p.name as product_name, p.sku, p.category
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
", 'i', [$order_id]);

// Get other orders from same customer
$customer_orders = db_query("
    SELECT order_id, order_date, total_amount, status
    FROM orders
    WHERE customer_id = ? AND order_id != ?
    ORDER BY order_date DESC LIMIT 5
", 'ii', [$order['cust_id'], $order_id]);

// Build items array
$items_out = [];
foreach ($items as $item) {
    $custom_data = json_decode($item['customization_data'] ?? '{}', true) ?? [];
    // Remove design_upload key from display
    unset($custom_data['design_upload']);

    $items_out[] = [
        'order_item_id' => $item['order_item_id'],
        'product_name'  => $item['product_name'] ?? 'Unknown',
        'sku'           => $item['sku'] ?? '',
        'category'      => $item['category'] ?? '',
        'quantity'      => (int)$item['quantity'],
        'unit_price'    => (float)$item['unit_price'],
        'subtotal'      => (float)($item['quantity'] * $item['unit_price']),
        'customization' => $custom_data,
        'has_design'    => !empty($item['design_image']),
        'design_name'   => $item['design_image_name'] ?? '',
        'design_url'    => !empty($item['design_image'])
                            ? '/printflow/public/serve_design.php?type=order_item&id=' . (int)$item['order_item_id']
                            : null,
    ];
}

// Build customer orders array
$cust_orders_out = [];
foreach ($customer_orders as $co) {
    $cust_orders_out[] = [
        'order_id'     => $co['order_id'],
        'order_date'   => format_date($co['order_date']),
        'total_amount' => format_currency($co['total_amount']),
        'status'       => $co['status'],
    ];
}

echo json_encode([
    'order_id'            => $order['order_id'],
    'order_date'          => format_datetime($order['order_date']),
    'total_amount'        => format_currency($order['total_amount']),
    'total_raw'           => (float)$order['total_amount'],
    'status'              => $order['status'],
    'payment_status'      => $order['payment_status'],
    'payment_reference'   => $order['payment_reference'] ?? '',
    'downpayment_amount'  => (float)($order['downpayment_amount'] ?? 0),
    'notes'               => $order['notes'] ?? '',
    'cancelled_by'        => $order['cancelled_by'] ?? '',
    'cancel_reason'       => $order['cancel_reason'] ?? '',
    'cancelled_at'        => !empty($order['cancelled_at']) ? format_datetime($order['cancelled_at']) : '',
    'revision_reason'     => $order['revision_reason'] ?? '',
    'revision_count'      => (int)($order['revision_count'] ?? 0),
    'cust_name'           => trim(($order['cust_first'] ?? '') . ' ' . ($order['cust_last'] ?? '')),
    'cust_initial'        => strtoupper(substr($order['cust_first'] ?? 'C', 0, 1)),
    'cust_email'          => $order['cust_email'] ?? '',
    'cust_phone'          => $order['cust_phone'] ?? '',
    'items'               => $items_out,
    'customer_orders'     => $cust_orders_out,
    'csrf_token'          => generate_csrf_token(),
]);
