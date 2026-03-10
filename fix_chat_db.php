<?php
require_once 'includes/db.php';

// Drop the old order_messages table
db_execute("DROP TABLE IF EXISTS order_messages");

$sql = "CREATE TABLE order_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('Customer', 'User') NOT NULL,
    message TEXT,
    image_path VARCHAR(255) NULL,
    message_type ENUM('text', 'image') DEFAULT 'text',
    is_seen TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
)";

if (db_execute($sql)) {
    echo "Table order_messages created successfully.\n";
} else {
    echo "Failed to create order_messages table.\n";
}

// Add columns to users if they don't exist
$cols_users = db_query("SHOW COLUMNS FROM users LIKE 'last_activity'");
if (empty($cols_users)) {
    db_execute("ALTER TABLE users ADD COLUMN last_activity TIMESTAMP NULL DEFAULT NULL");
    db_execute("ALTER TABLE users ADD COLUMN is_online TINYINT(1) DEFAULT 0");
    db_execute("ALTER TABLE users ADD COLUMN is_typing INT DEFAULT 0");
}

$cols_cust = db_query("SHOW COLUMNS FROM customers LIKE 'last_activity'");
if (empty($cols_cust)) {
    db_execute("ALTER TABLE customers ADD COLUMN last_activity TIMESTAMP NULL DEFAULT NULL");
    db_execute("ALTER TABLE customers ADD COLUMN is_online TINYINT(1) DEFAULT 0");
    db_execute("ALTER TABLE customers ADD COLUMN is_typing INT DEFAULT 0");
}

echo "Migration done.\n";
