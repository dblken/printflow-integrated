<?php
require_once 'includes/db.php';
global $conn;

$sql = "CREATE TABLE IF NOT EXISTS `product_variant_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `variant_id` int NOT NULL,
  `material_id` int NOT NULL,
  `quantity_required` decimal(10,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_variant_material` (`variant_id`,`material_id`),
  KEY `fk_pvm_material` (`material_id`),
  CONSTRAINT `fk_pvm_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pvm_material` FOREIGN KEY (`material_id`) REFERENCES `materials` (`material_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "product_variant_materials: OK\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
echo "Done.\n";
