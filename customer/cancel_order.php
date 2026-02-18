<?php
/**
 * Handle Order Cancellation
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancel'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    $order_id = (int)($_POST['order_id'] ?? 0);
    $customer_id = get_user_id();
    $reason = $_POST['reason'] ?? 'Other';
    $details = $_POST['details'] ?? '';

    // Verify order ownership and status
    $order = db_query("SELECT status FROM orders WHERE order_id = ? AND customer_id = ?", 'ii', [$order_id, $customer_id]);

    if (empty($order)) {
        redirect('orders.php');
    }

    if ($order[0]['status'] !== 'Pending') {
        $_SESSION['error'] = "Order #{$order_id} cannot be cancelled as it is already being processed.";
        redirect("order_details.php?id=$order_id");
    }

    // Update order status
    $sql = "UPDATE orders SET status = 'Cancelled', cancellation_reason = ?, cancellation_details = ? WHERE order_id = ?";
    $success = db_execute($sql, 'ssi', [$reason, $details, $order_id]);

    if ($success) {
        // Notify Customer
        create_notification($customer_id, 'Customer', "Order #{$order_id} has been cancelled.", 'Order', false, false);

        // Notify Staff
        $staff_users = db_query("SELECT user_id FROM users WHERE role = 'Staff' AND status = 'Activated'");
        foreach ($staff_users as $staff) {
            create_notification($staff['user_id'], 'Staff', "Order #{$order_id} was cancelled by the customer. Reason: $reason", 'Order', false, false);
        }

        $_SESSION['success'] = "Order #{$order_id} has been successfully cancelled.";
    } else {
        $_SESSION['error'] = "Failed to cancel order. Please try again.";
    }

    redirect("order_details.php?id=$order_id");
} else {
    redirect('orders.php');
}
