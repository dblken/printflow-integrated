<?php
require_once 'includes/db.php';
global $conn;

$statements = [
    "CREATE TABLE IF NOT EXISTS `product_variants` (
      `variant_id` int NOT NULL AUTO_INCREMENT,
      `product_id` int NOT NULL,
      `variant_name` varchar(255) NOT NULL,
      `price` decimal(10,2) NOT NULL DEFAULT '0.00',
      `status` enum('Active','Inactive') DEFAULT 'Active',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`variant_id`),
      KEY `fk_pv_product` (`product_id`),
      CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `product_variant_materials` (
      `id` int NOT NULL AUTO_INCREMENT,
      `variant_id` int NOT NULL,
      `material_id` int NOT NULL,
      `quantity_required` decimal(10,4) NOT NULL DEFAULT '0.0000',
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_variant_material` (`variant_id`,`material_id`),
      KEY `fk_pvm_material` (`material_id`),
      CONSTRAINT `fk_pvm_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`) ON DELETE CASCADE,
      CONSTRAINT `fk_pvm_material` FOREIGN KEY (`material_id`) REFERENCES `materials` (`material_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($statements as $sql) {
    if ($conn->query($sql)) {
        $tableName = preg_match('/`([^`]+)`/', $sql, $m) ? $m[1] : '?';
        echo "Table '$tableName': OK\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
echo "Done.\n";
