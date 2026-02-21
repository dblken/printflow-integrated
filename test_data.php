<?php
require 'includes/db.php';
$conn->query("UPDATE products SET category = 'Tarpaulin Printing' WHERE name LIKE '%Tarpaulin%' LIMIT 1");
$conn->query("UPDATE products SET category = 'T-Shirt Printing' WHERE name LIKE '%Shirt%' LIMIT 1");
echo "Test data updated successfully.";
