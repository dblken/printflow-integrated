<?php
require 'includes/db.php';

// Re-add category to products
$conn->query("ALTER TABLE products ADD COLUMN category VARCHAR(50) AFTER sku");

// Add customization storage to order_items
$conn->query("ALTER TABLE order_items ADD COLUMN customization_data TEXT AFTER unit_price");
$conn->query("ALTER TABLE order_items ADD COLUMN design_file VARCHAR(255) AFTER customization_data");

echo "Migration completed successfully.";
