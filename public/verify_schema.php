<?php
require_once __DIR__ . '/../includes/db.php';
$res = $conn->query("DESCRIBE sessions");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
        echo "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
