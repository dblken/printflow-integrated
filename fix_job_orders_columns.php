<?php
require_once 'includes/db.php';
global $conn;

// Get existing columns
$existing = [];
$r = $conn->query("SHOW COLUMNS FROM job_orders");
while ($row = $r->fetch_assoc()) {
    $existing[] = $row['Field'];
}

$toAdd = [
    'payment_status'  => "ENUM('UNPAID','PENDING_VERIFICATION','PARTIAL','PAID') NOT NULL DEFAULT 'UNPAID'",
    'priority'        => "ENUM('NORMAL','RUSH','URGENT') NOT NULL DEFAULT 'NORMAL'",
    'due_date'        => "DATE DEFAULT NULL",
    'width_ft'        => "DECIMAL(8,2) DEFAULT NULL",
    'height_ft'       => "DECIMAL(8,2) DEFAULT NULL",
    'total_sqft'      => "DECIMAL(10,2) DEFAULT NULL",
    'estimated_total' => "DECIMAL(10,2) DEFAULT NULL",
    'quantity'        => "INT NOT NULL DEFAULT 1",
    'notes'           => "TEXT DEFAULT NULL",
    'service_type'    => "VARCHAR(255) DEFAULT NULL",
    'customer_id'     => "INT DEFAULT NULL",
    'branch_id'       => "INT DEFAULT NULL",
    'created_at'      => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP",
    'status'          => "VARCHAR(30) NOT NULL DEFAULT 'PENDING'",
];

foreach ($toAdd as $col => $def) {
    if (!in_array($col, $existing)) {
        $sql = "ALTER TABLE `job_orders` ADD COLUMN `{$col}` {$def}";
        if ($conn->query($sql)) {
            echo "Added: {$col}\n";
        } else {
            echo "Error adding {$col}: " . $conn->error . "\n";
        }
    } else {
        echo "Exists: {$col}\n";
    }
}
echo "Done.\n";
