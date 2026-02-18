<?php
// Initialize DB Sessions Table
require_once __DIR__ . '/../includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    access INT(10) UNSIGNED NOT NULL,
    data TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'sessions' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
