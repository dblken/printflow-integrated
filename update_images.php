<?php
$conn = new mysqli('localhost', 'root', '', 'printflow_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$img_path = "public/images/services/Sintraboard Standees.jpg";
$sql = "UPDATE products SET product_image = '$img_path' WHERE product_id BETWEEN 51 AND 54";

if ($conn->query($sql) === TRUE) echo "Images updated. ";

$conn->close();
?>
