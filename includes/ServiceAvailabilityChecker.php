<?php
/**
 * ServiceAvailabilityChecker.php
 * Reconstructed to handle service-to-material mapping checks.
 */
class ServiceAvailabilityChecker {
    
    /**
     * Check if a service is available based on stock levels.
     */
    public static function isServiceAvailable($serviceType) {
        $rules = db_query("SELECT item_id FROM service_material_rules WHERE service_type = ?", "s", [$serviceType]);
        if (empty($rules)) return true;

        foreach ($rules as $r) {
            $soh = InventoryManager::getStockOnHand($r['item_id']);
            if ($soh <= 0) return false;
        }

        return true;
    }

    /**
     * Get list of all services and their availability status.
     */
    public static function getServiceStatusList() {
        $services = db_query("SELECT DISTINCT service_type FROM service_material_rules");
        $list = [];
        foreach ($services ?: [] as $s) {
            $list[$s['service_type']] = self::isServiceAvailable($s['service_type']);
        }
        return $list;
    }
}
