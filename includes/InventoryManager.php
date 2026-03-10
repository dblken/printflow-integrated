<?php
/**
 * InventoryManager class
 * Reconstructed to support the dynamic inventory module.
 */
class InventoryManager {
    
    /**
     * Get Stockholm on Hand (SOH) for a specific item.
     */
    public static function getStockOnHand($itemId, $branchId = null) {
        $sql = "SELECT SUM(IF(direction='IN', quantity, -quantity)) as soh 
                FROM inventory_transactions 
                WHERE item_id = ?";
        $params = [$itemId];
        $types = "i";

        if ($branchId && $branchId !== 'all') {
            $sql .= " AND branch_id = ?";
            $params[] = $branchId;
            $types .= "i";
        }

        $res = db_query($sql, $types, $params);
        return (float)($res[0]['soh'] ?? 0);
    }

    /**
     * Record an inventory transaction.
     */
    public static function recordTransaction($itemId, $direction, $quantity, $uom = null, $refType = 'adjustment_manual', $refId = null, $branchId = null, $notes = '', $userId = null, $date = null) {
        $date = $date ?: date('Y-m-d');
        $branchId = $branchId ?: ($_SESSION['branch_id'] ?? 1);
        $userId = $userId ?: ($_SESSION['user_id'] ?? null);

        $sql = "INSERT INTO inventory_transactions (item_id, branch_id, direction, quantity, uom, ref_type, ref_id, user_id, notes, transaction_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $res = db_execute($sql, "iisdssiiss", [
            $itemId, $branchId, $direction, $quantity, $uom, $refType, $refId, $userId, $notes, $date
        ]);

        self::updateBranchInventory($itemId, $branchId);
        return $res;
    }

    /**
     * Update the cached stock quantity in branch_inventory table.
     */
    private static function updateBranchInventory($itemId, $branchId) {
        $soh = self::getStockOnHand($itemId, $branchId);
        
        $exists = db_query("SELECT id FROM branch_inventory WHERE branch_id = ? AND item_id = ?", "ii", [$branchId, $itemId]);
        
        if ($exists) {
            db_execute("UPDATE branch_inventory SET stock_quantity = ? WHERE id = ?", "di", [$soh, $exists[0]['id']]);
        } else {
            db_execute("INSERT INTO branch_inventory (branch_id, item_id, stock_quantity) VALUES (?, ?, ?)", "iid", [$branchId, $itemId, $soh]);
        }
    }

    /**
     * Get item details.
     */
    public static function getItem($id) {
        $res = db_query("SELECT i.*, c.name as category_name 
                        FROM inv_items i 
                        LEFT JOIN inv_categories c ON i.category_id = c.id 
                        WHERE i.id = ?", "i", [$id]);
        return $res[0] ?? null;
    }
}
