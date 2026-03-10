<?php
/**
 * Secure Design Image Server
 * PrintFlow - Serves design images stored as LONGBLOB in the database.
 *
 * Usage:
 *   /printflow/public/serve_design.php?type=order_item&id=123
 *   /printflow/public/serve_design.php?type=service_file&id=456
 *
 * Access restricted to Staff and Admin roles only.
 * No file is ever read from disk — data comes directly from the DB.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// ---- Auth: Staff/Admin always allowed; Customers may see their own designs ----
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    http_response_code(403);
    die('Access denied.');
}

$session_role = $_SESSION['user_type'];
$allowed_roles = ['Staff', 'Admin', 'Customer'];
if (!in_array($session_role, $allowed_roles)) {
    http_response_code(403);
    die('Access denied.');
}

// ---- Input validation ----
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if (!$id || !in_array($type, ['order_item', 'service_file', 'revision_item'])) {
    http_response_code(400);
    die('Invalid request.');
}

// ---- Fetch data from DB ----
if ($type === 'order_item') {
    $field_req = $_GET['field'] ?? 'design'; // 'design' or 'reference'

    // Customers may only view designs belonging to their own orders
    if ($session_role === 'Customer') {
        $ownership = db_query(
            "SELECT oi.order_item_id FROM order_items oi
             JOIN orders o ON oi.order_id = o.order_id
             WHERE oi.order_item_id = ? AND o.customer_id = ? LIMIT 1",
            'ii', [$id, (int)$_SESSION['user_id']]
        );
        if (empty($ownership)) {
            http_response_code(403);
            die('Access denied.');
        }
    }

    // Fetch from order_items
    $row = db_query(
        "SELECT design_image, design_image_mime, design_image_name, design_file, reference_image_file FROM order_items WHERE order_item_id = ? LIMIT 1",
        'i',
        [$id]
    );

    if (empty($row)) {
        http_response_code(404);
        die('Order item not found.');
    }

    $item = $row[0];
    $data = null;
    $mime = 'image/jpeg';
    $name = 'design.jpg';

    if ($field_req === 'reference') {
        if (!empty($item['reference_image_file'])) {
            // Path from DB looks like: /printflow/uploads/orders/ref_xxx.jpg
            $relative_path = str_replace('/printflow/', '', $item['reference_image_file']);
            $abs_path = __DIR__ . '/../' . $relative_path;
            
            if (file_exists($abs_path)) {
                $data = file_get_contents($abs_path);
                $mime = mime_content_type($abs_path);
                $name = basename($abs_path);
            }
        }
    } else {
        // Default: design
        if (!empty($item['design_file'])) {
            $relative_path = str_replace('/printflow/', '', $item['design_file']);
            $abs_path = __DIR__ . '/../' . $relative_path;
            
            if (file_exists($abs_path)) {
                $data = file_get_contents($abs_path);
                $mime = mime_content_type($abs_path);
                $name = basename($abs_path);
            }
        }
        
        // Fallback to BLOB if file not found or path empty
        if ($data === null && !empty($item['design_image'])) {
            $data = $item['design_image'];
            $mime = $item['design_image_mime'] ?: 'image/jpeg';
            $name = $item['design_image_name'] ?: 'design.jpg';
        }
    }

    if ($data === null) {
        http_response_code(404);
        die('Image not found.');
    }

} elseif ($type === 'revision_item') {
    // Customers may only view designs belonging to their own orders
    if ($session_role === 'Customer') {
        $ownership = db_query(
            "SELECT r.revision_id FROM order_item_revisions r
             JOIN orders o ON r.order_id = o.order_id
             WHERE r.revision_id = ? AND o.customer_id = ? LIMIT 1",
            'ii', [$id, (int)$_SESSION['user_id']]
        );
        if (empty($ownership)) {
            http_response_code(403);
            die('Access denied.');
        }
    }

    // Fetch from order_item_revisions
    $row = db_query(
        "SELECT design_image, design_image_mime, design_image_name FROM order_item_revisions WHERE revision_id = ? LIMIT 1",
        'i',
        [$id]
    );

    if (empty($row) || empty($row[0]['design_image'])) {
        http_response_code(404);
        die('Design not found.');
    }

    $data = $row[0]['design_image'];
    $mime = $row[0]['design_image_mime'] ?: 'image/jpeg';
    $name = $row[0]['design_image_name'] ?: 'design.jpg';

} else {
    // Fetch from service_order_files
    $row = db_query(
        "SELECT file_data, mime_type, original_name FROM service_order_files WHERE id = ? LIMIT 1",
        'i',
        [$id]
    );

    if (empty($row) || empty($row[0]['file_data'])) {
        http_response_code(404);
        die('Design not found.');
    }

    $data = $row[0]['file_data'];
    $mime = $row[0]['mime_type'] ?: 'image/jpeg';
    $name = $row[0]['original_name'] ?: 'design.jpg';
}

// ---- Whitelist MIME type before outputting ----
$allowed_mime = ['image/jpeg', 'image/jpg', 'image/png'];
if (!in_array($mime, $allowed_mime)) {
    http_response_code(415);
    die('Unsupported media type.');
}

// ---- Output image with appropriate headers ----
header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($data));
header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) . '"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

echo $data;
exit;
