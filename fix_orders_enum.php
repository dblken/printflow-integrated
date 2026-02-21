<?php
require 'includes/db.php';

// Update orders status enum to include 'Pending Review'
$sql = "ALTER TABLE orders MODIFY COLUMN status ENUM('Pending Review', 'Pending', 'Processing', 'Ready for Pickup', 'Completed', 'Cancelled') DEFAULT 'Pending'";
if ($conn->query($sql)) {
    echo "Orders status ENUM updated successfully.\n";
} else {
    echo "Error updating status ENUM: " . $conn->error . "\n";
}

// Also check and update notifications type enum if needed
$sql2 = "ALTER TABLE notifications MODIFY COLUMN type ENUM('Order', 'Stock', 'System', 'Message') DEFAULT 'System'";
if ($conn->query($sql2)) {
    echo "Notifications type ENUM verified/updated successfully.\n";
} else {
    echo "Error updating notifications type ENUM: " . $conn->error . "\n";
}
