<?php
/**
 * AJAX: Get Order Items (Customer)
 * Returns order items + full order details as JSON for modal display
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

header('Content-Type: application/json');

$order_id = (int)($_GET['id'] ?? 0);
$customer_id = get_user_id();

if (!$order_id) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// Verify order belongs to this customer
$order_result = db_query("SELECT * FROM orders WHERE order_id = ? AND customer_id = ?", 'ii', [$order_id, $customer_id]);
if (empty($order_result)) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}
$order = $order_result[0];

// Get items with design info
$items = db_query("
    SELECT oi.*, p.name as product_name, p.category
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
", 'i', [$order_id]);

$items_out = [];
foreach ($items as $item) {
    $custom_data = json_decode($item['customization_data'] ?? '{}', true) ?? [];
    unset($custom_data['design_upload']);

    $items_out[] = [
        'order_item_id' => (int)$item['order_item_id'],
        'product_name'  => $item['product_name'] ?? 'Unknown Product',
        'category'      => $item['category'] ?? '',
        'quantity'      => (int)$item['quantity'],
        'unit_price'    => format_currency($item['unit_price']),
        'subtotal'      => format_currency($item['quantity'] * $item['unit_price']),
        'customization' => $custom_data,
        'has_design'    => !empty($item['design_image']),
        'design_url'    => !empty($item['design_image'])
                            ? '/printflow/public/serve_design.php?type=order_item&id=' . (int)$item['order_item_id']
                            : null,
    ];
}

// Cancellation / revision details
$cancel_info = '';
if ($order['status'] === 'Cancelled') {
    $cancel_info = trim(($order['cancelled_by'] ? 'By: ' . $order['cancelled_by'] : '') . ' | ' . ($order['cancel_reason'] ?? ''), ' |');
}

echo json_encode([
    'order_id'         => $order['order_id'],
    'order_date'       => format_datetime($order['order_date']),
    'total_amount'     => format_currency($order['total_amount']),
    'status'           => $order['status'],
    'payment_status'   => $order['payment_status'],
    'estimated_comp'   => ($order['estimated_completion'] ?? null) ? format_date($order['estimated_completion']) : 'TBD',
    'notes'            => $order['notes'] ?? '',
    'cancelled_by'     => $order['cancelled_by'] ?? '',
    'cancel_reason'    => $order['cancel_reason'] ?? '',
    'cancelled_at'     => !empty($order['cancelled_at']) ? format_datetime($order['cancelled_at']) : '',
    'revision_reason'  => $order['revision_reason'] ?? '',
    'items'            => $items_out,
]);
