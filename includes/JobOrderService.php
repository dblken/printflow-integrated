<?php
/**
 * JobOrderService class
 * Reconstructed to support the new job order management system.
 */
require_once __DIR__ . '/InventoryManager.php';

class JobOrderService {

    /**
     * Get a single job order.
     */
    public static function getOrder($id) {
        $res = db_query("SELECT jo.*, 
                            CONCAT(c.first_name, ' ', IFNULL(c.last_name, '')) AS customer_full_name, 
                            c.customer_type, 
                            c.contact_number AS customer_contact,
                            oi.customization_data,
                            oi.design_notes,
                            oi.design_image_name
                        FROM job_orders jo 
                        LEFT JOIN customers c ON jo.customer_id = c.customer_id 
                        LEFT JOIN order_items oi ON jo.order_item_id = oi.order_item_id
                        WHERE jo.id = ?", "i", [$id]);
        $order = $res[0] ?? null;

        if ($order) {
            $order['customization'] = json_decode($order['customization_data'] ?? '{}', true) ?? [];
            $order['materials'] = db_query("SELECT jom.*, i.name as item_name, i.track_by_roll 
                                            FROM job_order_materials jom 
                                            LEFT JOIN inv_items i ON jom.item_id = i.id 
                                            WHERE jom.order_id = ?", "i", [$id]) ?: [];
            
            $order['files'] = [];
            // Assuming job_order_files exists or we can use artwork_path
            $files_query = db_query("SELECT * FROM job_order_files WHERE order_id = ?", "i", [$id]);
            if ($files_query) {
                $order['files'] = $files_query;
            } elseif (!empty($order['artwork_path'])) {
                $order['files'][] = [
                    'id' => 1,
                    'file_name' => basename($order['artwork_path']),
                    'file_path' => $order['artwork_path']
                ];
            }
        }
        return $order;
    }

    /**
     * Create a new job order with materials.
     */
    public static function createOrder($data, $materials = []) {
        $sql = "INSERT INTO job_orders (customer_id, customer_name, service_type, width_ft, height_ft, quantity, total_sqft, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $orderId = db_execute($sql, "issdddssi", [
            $data['customer_id'], $data['customer_name'], $data['service_type'], 
            $data['width_ft'], $data['height_ft'], $data['quantity'], 
            $data['total_sqft'], $data['notes'], $data['created_by']
        ]);

        if ($orderId && !empty($materials)) {
            foreach ($materials as $m) {
                self::addMaterial($orderId, $m['item_id'], $m['quantity'], $m['uom']);
            }
        }

        return $orderId;
    }

    /**
     * Update job order status and machine assignment.
     */
    public static function updateStatus($id, $status, $machineId = null) {
        $sql = "UPDATE job_orders SET status = ?, machine_id = ? WHERE id = ?";
        return (bool)db_execute($sql, "sii", [$status, $machineId, $id]);
    }

    /**
     * Add material to a job order.
     */
    public static function addMaterial($orderId, $itemId, $quantity, $uom = 'pcs', $rollId = null, $notes = '', $metadata = null) {
        $sql = "INSERT INTO job_order_materials (order_id, item_id, quantity, uom, notes) 
                VALUES (?, ?, ?, ?, ?)";
        return db_execute($sql, "iidss", [$orderId, $itemId, $quantity, $uom, $notes]);
    }

    /**
     * Calculate material readiness for an order.
     */
    public static function getMaterialReadiness($orderId) {
        // Implementation stub - returns percentage based on SOH vs required
        $materials = db_query("SELECT item_id, quantity FROM job_order_materials WHERE order_id = ?", "i", [$orderId]);
        if (empty($materials)) return 100;

        $ready_count = 0;
        foreach ($materials as $m) {
            $soh = InventoryManager::getStockOnHand($m['item_id']);
            if ($soh >= $m['quantity']) $ready_count++;
        }

        return round(($ready_count / count($materials)) * 100);
    }

    /**
     * Calculate estimated job cost.
     */
    public static function calculateJobCost($orderId) {
        $sql = "SELECT SUM(i.unit_cost * jom.quantity) as total_cost 
                FROM job_order_materials jom 
                JOIN inv_items i ON jom.item_id = i.id 
                WHERE jom.order_id = ?";
        $res = db_query($sql, "i", [$orderId]);
        return (float)($res[0]['total_cost'] ?? 0);
    }
}
