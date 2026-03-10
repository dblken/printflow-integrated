<?php
require 'c:\xampp\htdocs\printflow\includes\db.php';
$res = $conn->query("SELECT product_id, name, category, price FROM products WHERE category LIKE '%Sintra%'");
echo json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
