<?php
require_once __DIR__ . '/includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS order_item_revisions (
    revision_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    staff_id INT,
    revision_reason TEXT,
    design_image LONGBLOB,
    design_image_name VARCHAR(255),
    design_image_mime VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($conn, $sql)) {
    echo "Table 'order_item_revisions' created successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
