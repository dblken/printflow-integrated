<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/InventoryManager.php';
require_once 'includes/JobOrderService.php';
require_once 'includes/branch_context.php';

echo "Testing reconstructed backend...\n";

try {
    // 1. Test InventoryManager::getItem
    $item = InventoryManager::getItem(1);
    echo "InventoryManager::getItem(1) called. Result: " . ($item ? "Success (".$item['name'].")" : "Empty (Normal if no data)") . "\n";

    // 2. Test branch_context
    $ctx = init_branch_context(true);
    echo "init_branch_context called. Result: Selected Branch = " . $ctx['selected_branch_id'] . "\n";

    // 3. Test db_query on new tables
    $branches = db_query("SELECT * FROM branches");
    echo "Count of branches: " . count($branches ?: []) . "\n";

    echo "Backend verification script finished.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
