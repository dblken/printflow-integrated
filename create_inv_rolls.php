<?php
require_once 'includes/db.php';
global $conn;

$sql = "CREATE TABLE IF NOT EXISTS `inv_rolls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `roll_code` varchar(50) DEFAULT NULL,
  `width_ft` int DEFAULT NULL,
  `total_length_ft` decimal(10,2) NOT NULL,
  `remaining_length_ft` decimal(10,2) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `status` enum('AVAILABLE','DEPLETED','VOID') DEFAULT 'AVAILABLE',
  `notes` text,
  `received_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_roll_item` (`item_id`),
  CONSTRAINT `fk_roll_item` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql)) {
    echo "inv_rolls table created OK\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
