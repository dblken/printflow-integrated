<?php
require 'includes/db.php';
$res = $conn->query("DESCRIBE order_items");
$out = "Field | Type | Null | Key | Default | Extra\n";
$out .= "----------------------------------------------\n";
while($row = $res->fetch_assoc()) {
    $out .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
}
file_put_contents('tmp/db_schema.txt', $out);
echo "Done\n";
