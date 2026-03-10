<?php
require_once __DIR__ . '/includes/db.php';
try {
    db_execute("ALTER TABLE job_orders ADD COLUMN IF NOT EXISTS ink_id INT DEFAULT NULL AFTER machine_id");
    echo "Success: ink_id added to job_orders\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
