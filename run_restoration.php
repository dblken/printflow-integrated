<?php
require_once 'includes/db.php';

$sql = file_get_contents('database/admin_restore.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    if (trim($query)) {
        try {
            if (stripos($query, 'ALTER TABLE') !== false || stripos($query, 'CREATE TABLE') !== false) {
                // Use direct query for DDL
                global $conn;
                $conn->query($query);
            } else {
                db_execute($query);
            }
            echo "Executed: " . substr(trim($query), 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration complete.\n";
