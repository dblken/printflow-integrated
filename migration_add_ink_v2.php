<?php
require_once __DIR__ . '/includes/db.php';
try {
    $cols = db_query("SHOW COLUMNS FROM job_orders LIKE 'ink_id'");
    if (empty($cols)) {
        db_execute("ALTER TABLE job_orders ADD COLUMN ink_id INT DEFAULT NULL AFTER machine_id");
        echo "Success: ink_id added to job_orders\n";
    } else {
        echo "Info: ink_id already exists\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
