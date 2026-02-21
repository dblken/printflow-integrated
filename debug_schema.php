<?php
require 'includes/db.php';
$res = $conn->query('DESCRIBE orders');
while($row = $res->fetch_assoc()) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . " | Default: " . $row['Default'] . "\n";
}
echo "\nDESCRIBE order_items:\n";
$res = $conn->query('DESCRIBE order_items');
while($row = $res->fetch_assoc()) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . " | Default: " . $row['Default'] . "\n";
}
