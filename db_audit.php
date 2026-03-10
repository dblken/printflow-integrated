<?php
require_once 'includes/db.php';
$result = $conn->query("SHOW TABLES");
echo "DATABASE TABLES AUDIT:\n";
echo "======================\n";
while ($row = $result->fetch_array()) {
    $table = $row[0];
    $count = $conn->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0];
    echo "- $table ($count rows)\n";
}
echo "======================\n";
?>
