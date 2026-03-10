<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';
$_SESSION = [];
$_SESSION['user_type'] = 'Staff';
$_SESSION['user_id'] = 1;

try {
    $sql = "SELECT jo.*, c.first_name, c.last_name, c.customer_type 
            FROM job_orders jo 
            LEFT JOIN customers c ON jo.customer_id = c.customer_id 
            WHERE 1=1 ORDER BY jo.priority = 'HIGH' DESC, jo.due_date ASC, jo.created_at DESC LIMIT 50 OFFSET 0";
    $orders = db_query($sql);
    
    require_once 'includes/JobOrderService.php';
    foreach ($orders as &$jo) {
        $jo['readiness'] = JobOrderService::getMaterialReadiness($jo['id']);
        $jo['estimated_cost'] = JobOrderService::calculateJobCost($jo['id']);
    }
    echo "SUCCESS\n";
    print_r(count($orders));
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
