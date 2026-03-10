<?php
/**
 * API: Fetch Order Messages
 * PrintFlow - Order Chat System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$order_id  = (int)($_GET['order_id'] ?? 0);
$last_id   = (int)($_GET['last_id'] ?? 0);

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid Order ID']);
    exit();
}

// 1. Verify Authorization
$order_res = db_query("SELECT customer_id FROM orders WHERE order_id = ?", 'i', [$order_id]);
if (empty($order_res)) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit();
}

if ($user_type === 'Customer' && $order_res[0]['customer_id'] !== $user_id) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// 2. Mark messages as seen
// If I am a Customer, mark all messages from 'User' (Staff) as seen
// If I am a User (Staff/Admin), mark all messages from 'Customer' as seen
$target_sender_type = ($user_type === 'Customer') ? 'User' : 'Customer';
db_execute(
    "UPDATE order_messages SET is_seen = 1 WHERE order_id = ? AND sender_type = ? AND is_seen = 0",
    'is', [$order_id, $target_sender_type]
);

// 3. Fetch messages
$messages = db_query(
    "SELECT * FROM order_messages WHERE order_id = ? AND id > ? ORDER BY created_at ASC",
    'ii', [$order_id, $last_id]
);

// Format timestamps and identify self
$formatted_messages = [];
foreach ($messages as $msg) {
    $is_self = ($msg['sender_id'] == $user_id && (($msg['sender_type'] === 'Customer' && $user_type === 'Customer') || ($msg['sender_type'] === 'User' && $user_type !== 'Customer')));
    
    $formatted_messages[] = [
        'id'          => $msg['id'],
        'sender_type' => $msg['sender_type'],
        'message'     => $msg['message'],
        'image_path'  => $msg['image_path'],
        'message_type'=> $msg['message_type'],
        'is_seen'     => $msg['is_seen'],
        'created_at'  => date('F j, Y g:i A', strtotime($msg['created_at'])),
        'is_self'     => $is_self
    ];
}

// 4. Update last activity
$table = ($user_type === 'Customer') ? 'customers' : 'users';
$pk    = ($user_type === 'Customer') ? 'customer_id' : 'user_id';
db_execute("UPDATE $table SET last_activity = NOW(), is_online = 1 WHERE $pk = ?", 'i', [$user_id]);

// 5. Get partner status (Online & Typing)
$partner_status = [
    'is_online' => false,
    'is_typing' => false
];

if ($user_type === 'Customer') {
    // Check if ANY staff/admin is online or typing for this order
    // (A bit broad but works for multis-staff shops)
    $staff_status = db_query(
        "SELECT MAX(last_activity) as last_act, SUM(CASE WHEN is_typing = ? THEN 1 ELSE 0 END) as typing_count 
         FROM users WHERE status = 'Activated'", 
        'i', [$order_id]
    )[0];
    
    if ($staff_status['last_act'] && (time() - strtotime($staff_status['last_act'])) <= 10) {
        $partner_status['is_online'] = true;
    }
    if ($staff_status['typing_count'] > 0) {
        $partner_status['is_typing'] = true;
    }
} else {
    // Check specific customer for this order
    $cust_id = $order_res[0]['customer_id'];
    $cust_status = db_query(
        "SELECT last_activity, is_typing FROM customers WHERE customer_id = ?",
        'i', [$cust_id]
    )[0];
    
    if ($cust_status['last_activity'] && (time() - strtotime($cust_status['last_activity'])) <= 10) {
        $partner_status['is_online'] = true;
    }
    if ($cust_status['is_typing'] == $order_id) {
        $partner_status['is_typing'] = true;
    }
}

echo json_encode([
    'success'  => true,
    'messages' => $formatted_messages,
    'partner'  => $partner_status
]);
