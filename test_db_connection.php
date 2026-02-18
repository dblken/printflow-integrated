<?php
// Define constants to prevent redefined notice if included multiple times
if (!defined('DB_HOST')) {
    require_once 'includes/db.php';
}

if ($conn->ping()) {
    echo "Connected successfully to database: " . DB_NAME;
} else {
    echo "Error logging ping: " . $conn->error;
}
