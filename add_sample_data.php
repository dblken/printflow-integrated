<?php
require_once __DIR__ . '/includes/db.php';
$items = [
    ['Carbon Paper', 3, 'pcs', 100],
    ['Transfer Tape', 2, 'ft', 50],
    ['Black Ink', 1, 'liter', 10],
    ['White Ink', 1, 'liter', 10],
    ['Color Ink', 1, 'liter', 10],
    ['Eco-Solvent Ink', 1, 'liter', 10]
];

foreach($items as $item) {
    db_execute("INSERT INTO materials (material_name, category_id, unit, current_stock) VALUES (?, ?, ?, ?)", 'sisi', $item);
}
echo "Sample data inserted.\n";
?>
