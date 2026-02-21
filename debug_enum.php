<?php
require 'includes/db.php';
$res = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
$row = $res->fetch_assoc();
echo "Type: " . $row['Type'] . "\n";
