<?php
require_once 'includes/db.php';
global $conn;
$r = $conn->query('DESCRIBE job_orders');
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo $row['Field'] . ': ' . $row['Type'] . "\n";
    }
} else {
    echo 'Table missing or error: ' . $conn->error . "\n";
}
