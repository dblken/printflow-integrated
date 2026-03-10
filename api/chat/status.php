<?php
/**
 * API: Update Chat Status (Typing & Heartbeat)
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
$order_id  = (int)($_POST['order_id'] ?? 0);
$is_typing = (int)($_POST['is_typing'] ?? 0); // Should be the order_id if typing, else 0

$table = ($user_type === 'Customer') ? 'customers' : 'users';
$pk    = ($user_type === 'Customer') ? 'customer_id' : 'user_id';

// Update typing status and last activity
// If is_typing is true, we store the order_id. If false, we store 0.
$typing_value = ($is_typing) ? $order_id : 0;

$success = db_execute("UPDATE $table SET last_activity = NOW(), is_online = 1, is_typing = ? WHERE $pk = ?", 'ii', [$typing_value, $user_id]);

echo json_encode(['success' => (bool)$success]);
