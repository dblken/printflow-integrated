<?php
/**
 * Stream chat image stored in database
 * URL pattern: /printflow/api/chat/image.php?id=123
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}

$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type']; // 'Customer', 'Staff', 'Admin'
$image_id  = (int)($_GET['id'] ?? 0);

if (!$image_id) {
    http_response_code(400);
    exit('Invalid image id');
}

// Fetch image along with related order & customer for authorization
$rows = db_query(
    "SELECT ci.mime, ci.data, om.order_id, o.customer_id
     FROM chat_images ci
     JOIN order_messages om ON ci.message_id = om.id
     JOIN orders o ON om.order_id = o.order_id
     WHERE ci.id = ?",
    'i',
    [$image_id]
);

if (empty($rows)) {
    http_response_code(404);
    exit('Image not found');
}

$row = $rows[0];

// Authorization: customers can only see their own order images
if ($user_type === 'Customer' && (int)$row['customer_id'] !== (int)$user_id) {
    http_response_code(403);
    exit('Forbidden');
}

// Staff/Admin: allowed (same behavior as fetch_messages, which does not restrict by staff id)

$mime = $row['mime'] ?: 'application/octet-stream';
$data = $row['data'];

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($data));
header('Cache-Control: private, max-age=86400');

echo $data;
exit;

