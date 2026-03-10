<?php
require __DIR__ . '/includes/functions.php';
global $conn;
$conn->query("ALTER TABLE job_orders ADD COLUMN amount_paid DECIMAL(12,2) DEFAULT 0.00 AFTER estimated_total");
$conn->query("ALTER TABLE job_orders ADD COLUMN priority ENUM('NORMAL', 'HIGH') DEFAULT 'NORMAL' AFTER status");
echo "Columns added to job_orders.\n";
