<?php
require_once __DIR__ . '/../includes/db.php';

echo "<h1>Manual DB Write Test</h1>";

$id = 'test_session_' . time();
$access = time();
$data = 'test_data_' . rand(1000, 9999);

$stmt = $conn->prepare("REPLACE INTO sessions (id, access, data) VALUES (?, ?, ?)");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("sis", $id, $access, $data);
if ($stmt->execute()) {
    echo "Successfully wrote to DB sessions table.<br>";
    echo "ID: $id<br>";
    echo "Data: $data<br>";
} else {
    echo "Execute failed: " . $stmt->error . "<br>";
}

$res = $conn->query("SELECT * FROM sessions ORDER BY access DESC LIMIT 5");
echo "<h3>Recent Sessions:</h3>";
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Data: " . $row['data'] . "<br>";
}
?>
