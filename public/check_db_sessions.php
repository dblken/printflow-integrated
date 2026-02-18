<?php
require_once __DIR__ . '/../includes/db.php';

$result = $conn->query("SELECT * FROM sessions");
if ($result) {
    echo "Total sessions in DB: " . $result->num_rows . "<br><br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Access: " . date('Y-m-d H:i:s', $row['access']) . " | Data: " . $row['data'] . "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
