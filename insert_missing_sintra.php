<?php
require_once __DIR__ . '/includes/functions.php';

$products = [
    [
        'product_id' => 51,
        'category' => 'Sintraboard & Standees',
        'name' => 'Sintraboard Sample 1',
        'description' => 'Sintraboard Flat Type Sample 1',
        'price' => 150.00,
        'product_image' => 'public/images/products/standeeflat.jpg',
        'status' => 'Activated'
    ],
    [
        'product_id' => 52,
        'category' => 'Sintraboard & Standees',
        'name' => 'Sintraboard Sample 2',
        'description' => 'Sintraboard Flat Type Sample 2',
        'price' => 150.00,
        'product_image' => 'public/images/products/standeeflat (2).jpg',
        'status' => 'Activated'
    ],
    [
        'product_id' => 53,
        'category' => 'Sintraboard & Standees',
        'name' => 'Sintraboard Sample 3',
        'description' => 'Sintraboard Flat Type Sample 3',
        'price' => 150.00,
        'product_image' => 'public/images/products/standeeflat (3).jpg',
        'status' => 'Activated'
    ]
];

foreach ($products as $p) {
    $check = db_query("SELECT product_id FROM products WHERE product_id = ?", "i", [$p['product_id']]);
    if (empty($check)) {
        db_execute(
            "INSERT INTO products (product_id, category, name, description, price, product_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "isssdss",
            [$p['product_id'], $p['category'], $p['name'], $p['description'], $p['price'], $p['product_image'], $p['status']]
        );
        echo "Inserted product ID " . $p['product_id'] . "\n";
    } else {
        echo "Product ID " . $p['product_id'] . " already exists. Skipping.\n";
    }
}
