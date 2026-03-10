<?php
require_once 'includes/db.php';

$sql = file_get_contents('database/job_orders_migration.sql');

// Split SQL by semicolons
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($queries as $query) {
    if (empty($query)) continue;
    
    // Remove comments to check the actual statement
    $clean_query = preg_replace('/--.*?\n/s', '', $query);
    $clean_query = trim($clean_query);
    
    // For DDL (CREATE TABLE), use mysqli_query directly to avoid prepared statement issues
    if (stripos($clean_query, 'CREATE TABLE') === 0 || stripos($clean_query, 'ALTER TABLE') === 0 || stripos($clean_query, 'DROP TABLE') === 0) {
        if ($conn->query($clean_query)) {
            $success_count++;
            echo "Successfully ran DDL: " . substr($clean_query, 0, 50) . "...\n";
        } else {
            echo "Error executing DDL query: " . $conn->error . "\n";
            $error_count++;
        }
    } else {
        try {
            // For other queries (INSERT, etc.), use db_execute (prepared statements)
            $res = db_execute($clean_query);
            if ($res !== false) {
                $success_count++;
                echo "Successfully ran DML: " . substr($clean_query, 0, 50) . "...\n";
            } else {
                echo "Error executing DML query: " . $conn->error . "\n";
                $error_count++;
            }
        } catch (Exception $e) {
            echo "Exception executing query: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
}

echo "\nMigration finished: $success_count queries succeeded, $error_count queries failed.\n";
