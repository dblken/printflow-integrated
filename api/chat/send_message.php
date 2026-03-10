<?php
/**
 * API: Send Order Message
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
$user_type = $_SESSION['user_type']; // 'Customer', 'Staff', or 'Admin'
$sender_type = ($user_type === 'Customer') ? 'Customer' : 'User';

$order_id = (int)($_POST['order_id'] ?? 0);
$message  = trim($_POST['message'] ?? '');

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid Order ID']);
    exit();
}

// 1. Verify User Authorization for this Order
$order = db_query("SELECT customer_id FROM orders WHERE order_id = ?", 'i', [$order_id]);
if (empty($order)) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit();
}

if ($user_type === 'Customer' && $order[0]['customer_id'] !== $user_id) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// 2. Handle Image Upload (store in DB instead of filesystem)
$image_path   = null;
$message_type = 'text';
$chat_image_id = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file      = $_FILES['image'];
    $max_size  = 5 * 1024 * 1024; // 5MB
    $allowed   = ['jpg', 'jpeg', 'png', 'gif'];
    $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'Image too large. Maximum size is 5MB']);
        exit();
    }

    if (!in_array($ext, $allowed, true)) {
        echo json_encode(['success' => false, 'error' => 'Invalid image type']);
        exit();
    }

    $tmp_path = $file['tmp_name'];
    $image_data = @file_get_contents($tmp_path);
    if ($image_data === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to read uploaded image']);
        exit();
    }

    // Determine MIME type safely
    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmp_path);
            finfo_close($finfo);
        }
    }
    if (!$mime) {
        // Fallback based on extension
        $mime = ($ext === 'jpg' ? 'image/jpeg' : 'image/' . $ext);
    }

    // Ensure chat_images table exists (stores binary data in DB)
    db_execute("
        CREATE TABLE IF NOT EXISTS chat_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message_id INT NULL,
            mime VARCHAR(100) NOT NULL,
            data LONGBLOB NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (message_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Insert image blob
    $chat_image_id = db_execute(
        "INSERT INTO chat_images (mime, data) VALUES (?, ?)",
        'sb',
        [$mime, $image_data]
    );

    if (!$chat_image_id) {
        echo json_encode(['success' => false, 'error' => 'Failed to save image']);
        exit();
    }

    // Store a URL that will stream the image from the database
    $image_path   = "/printflow/api/chat/image.php?id=" . $chat_image_id;
    $message_type = 'image';
}

if ($message === '' && $image_path === null) {
    echo json_encode(['success' => false, 'error' => 'Message is empty']);
    exit();
}

// 3. Insert Message
$sql = "INSERT INTO order_messages (order_id, sender_id, sender_type, message, image_path, message_type) VALUES (?, ?, ?, ?, ?, ?)";
$params = [$order_id, $user_id, $sender_type, $message, $image_path, $message_type];
$msg_id = db_execute($sql, 'iissss', $params);

// Link image record to message if needed
if ($msg_id && $chat_image_id) {
    db_execute("UPDATE chat_images SET message_id = ? WHERE id = ?", 'ii', [$msg_id, $chat_image_id]);
}

if ($msg_id) {
    // Update sender's last activity
    $table = ($user_type === 'Customer') ? 'customers' : 'users';
    $pk    = ($user_type === 'Customer') ? 'customer_id' : 'user_id';
    db_execute("UPDATE $table SET last_activity = NOW(), is_online = 1 WHERE $pk = ?", 'i', [$user_id]);

    echo json_encode(['success' => true, 'message_id' => $msg_id, 'timestamp' => date('F j, Y g:i A'), 'image_path' => $image_path]);
} else {
    // Log the error for debugging
    error_log("Chat insertion failed for Order #$order_id by User #$user_id ($user_type). SQL error might be in PHP error log.");
    echo json_encode(['success' => false, 'error' => 'Failed to send message. Please check server logs.']);
}
