<?php
require_once __DIR__ . '/includes/functions.php';

// Rename products 51-54 if they are Penshoppe
$result = db_execute(
    "UPDATE products SET name = 'Sintra Board Standees' WHERE product_id BETWEEN 51 AND 54 AND name LIKE '%Penshoppe%'"
);

echo "Renamed products successfully.\n";

// Update their category if needed
db_execute(
    "UPDATE products SET category = 'Sintra Board Standees' WHERE product_id BETWEEN 51 AND 54"
);

echo "Updated categories successfully.\n";
?>
