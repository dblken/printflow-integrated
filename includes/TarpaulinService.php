<?php
/**
 * TarpaulinService.php
 * Handles tarpaulin-specific roll and material operations.
 */
class TarpaulinService {

    /**
     * Get available rolls for a given width.
     */
    public static function getAvailableRolls($widthFt) {
        $sql = "SELECT r.*, i.name as item_name
                FROM inv_rolls r
                JOIN inv_items i ON r.item_id = i.id
                WHERE r.status = 'AVAILABLE'
                  AND (r.width_ft = ? OR r.width_ft IS NULL)
                ORDER BY r.remaining_length_ft DESC";
        return db_query($sql, "i", [(int)$widthFt]) ?: [];
    }

    /**
     * Assign a roll to a job order, deducting the footage needed.
     */
    public static function assignRoll($rollId, $jobOrderId, $heightFt, $quantity = 1) {
        $deductFt = $heightFt * $quantity;
        return RollService::deductFromRoll($rollId, $deductFt, 'job_order', $jobOrderId);
    }

    /**
     * Deduct tarpaulin inventory for a completed order.
     * Reads from order_tarp_details (if it exists) and deducts assigned rolls.
     */
    public static function deductInventoryForOrder($orderId) {
        // Check if the order_tarp_details table exists
        global $conn;
        $tableCheck = $conn->query("SHOW TABLES LIKE 'order_tarp_details'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            // Table doesn't exist — skip silently
            return true;
        }

        // Get all tarp detail rows for this order
        $details = db_query(
            "SELECT otd.*, r.item_id
             FROM order_tarp_details otd
             JOIN inv_rolls r ON otd.roll_id = r.id
             WHERE otd.order_id = ? AND otd.roll_id IS NOT NULL",
            "i", [$orderId]
        ) ?: [];

        foreach ($details as $d) {
            if (!empty($d['roll_id']) && !empty($d['height_ft'])) {
                RollService::deductFromRoll(
                    (int)$d['roll_id'],
                    (float)$d['height_ft'] * (int)($d['quantity'] ?? 1),
                    'order_completion',
                    $orderId
                );
            }
        }

        return true;
    }
}
