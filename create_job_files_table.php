<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS job_order_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES job_orders(id) ON DELETE CASCADE,
    KEY idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "job_order_files table created or already exists.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
