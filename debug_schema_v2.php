<?php
require 'includes/db.php';
function dump_table($name) {
    global $conn;
    echo "--- $name ---\n";
    $res = $conn->query("DESC $name");
    while($row = $res->fetch_assoc()) {
        printf("%-20s | %-15s | %-5s | %-5s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key']);
    }
}
dump_table('orders');
dump_table('order_items');
