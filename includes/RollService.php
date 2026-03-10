<?php
/**
 * RollService.php
 * Manages vinyl/tarp roll tracking for the inventory system.
 */
class RollService {

    /**
     * Create (receive) a new roll into the system.
     */
    public static function createRoll($itemId, $totalLength, $rollCode = '', $supplier = '') {
        $sql = "INSERT INTO inv_rolls (item_id, roll_code, total_length_ft, remaining_length_ft, supplier, status)
                VALUES (?, ?, ?, ?, ?, 'AVAILABLE')";
        $id = db_execute($sql, "isdds", [$itemId, $rollCode, $totalLength, $totalLength, $supplier]);

        // Record an IN transaction in the ledger
        if ($id) {
            InventoryManager::recordTransaction(
                $itemId, 'IN', $totalLength, 'ft',
                'roll_receipt', $id, null, "Roll received: $rollCode"
            );
        }

        return $id;
    }

    /**
     * Void (write-off) a roll.
     */
    public static function voidRoll($rollId, $notes = '') {
        $roll = db_query("SELECT * FROM inv_rolls WHERE id = ?", "i", [$rollId]);
        if (empty($roll)) return false;

        $roll = $roll[0];
        db_execute(
            "UPDATE inv_rolls SET status = 'VOID', notes = ? WHERE id = ?",
            "si", [$notes, $rollId]
        );

        // Record any remaining as OUT
        if ((float)$roll['remaining_length_ft'] > 0) {
            InventoryManager::recordTransaction(
                $roll['item_id'], 'OUT', (float)$roll['remaining_length_ft'], 'ft',
                'roll_void', $rollId, null, $notes ?: 'Roll voided'
            );
        }

        return true;
    }

    /**
     * Deduct footage from a specific roll.
     */
    public static function deductFromRoll($rollId, $lengthFt, $refType = 'usage', $refId = null) {
        $roll = db_query("SELECT * FROM inv_rolls WHERE id = ?", "i", [$rollId]);
        if (empty($roll)) return false;

        $roll = $roll[0];
        $remaining = max(0, (float)$roll['remaining_length_ft'] - $lengthFt);
        $status = $remaining <= 0 ? 'DEPLETED' : 'AVAILABLE';

        db_execute(
            "UPDATE inv_rolls SET remaining_length_ft = ?, status = ? WHERE id = ?",
            "dsi", [$remaining, $status, $rollId]
        );

        InventoryManager::recordTransaction(
            $roll['item_id'], 'OUT', $lengthFt, 'ft',
            $refType, $refId, null, "Roll #$rollId deduction"
        );

        return true;
    }
}
