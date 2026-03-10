<?php
$conn = new mysqli('localhost', 'root', '', 'printflow_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql1 = "UPDATE products SET name = 'Sintra Board Standees' WHERE product_id BETWEEN 51 AND 54";
$sql2 = "UPDATE products SET category = 'Sintra Board Standees' WHERE product_id BETWEEN 51 AND 54";

if ($conn->query($sql1) === TRUE) echo "Names updated. ";
if ($conn->query($sql2) === TRUE) echo "Categories updated. ";

$conn->close();
?>
