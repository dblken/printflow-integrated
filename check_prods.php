<?php
$conn = new mysqli('localhost', 'root', '', 'printflow_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$r = $conn->query('SELECT product_id, name, category FROM products WHERE product_id BETWEEN 51 AND 54');
while($row = $r->fetch_assoc()) echo json_encode($row) . "\n";

$conn->close();
?>
