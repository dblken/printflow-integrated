<?php
/**
 * Staff Order Details Page
 * PrintFlow - Printing Shop PWA
 * View order details + customer info (read-only) + update status
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Staff');
require_once __DIR__ . '/../includes/staff_pending_check.php';

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    redirect('/printflow/staff/orders.php');
}

// Handle status update
$success = '';
$error = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $new_status = $_POST['status'];
        if (update_order_status($order_id, $new_status)) {
            $_SESSION['success'] = "Order status has been successfully updated to '{$new_status}'.";
            redirect("order_details.php?id=$order_id");
        } else {
            $_SESSION['error'] = 'Failed to update order status. Please try again.';
            redirect("order_details.php?id=$order_id");
        }
    } else {
        $_SESSION['error'] = 'Invalid request payload. Security token verification failed.';
        redirect("order_details.php?id=$order_id");
    }
}

// Get order with customer info
$order_result = db_query("
    SELECT o.*, 
           c.first_name as cust_first, c.last_name as cust_last, 
           c.email as cust_email, c.contact_number as cust_phone,
           c.customer_id as cust_id
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.customer_id 
    WHERE o.order_id = ?
", 'i', [$order_id]);

if (empty($order_result)) {
    redirect('/printflow/staff/orders.php');
}
$order = $order_result[0];

// Get order items
$items = db_query("
    SELECT oi.*, p.name as product_name, p.sku, p.category
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
", 'i', [$order_id]);

// Get other orders from this customer
$customer_orders = db_query("
    SELECT order_id, order_date, total_amount, status 
    FROM orders 
    WHERE customer_id = ? AND order_id != ?
    ORDER BY order_date DESC LIMIT 5
", 'ii', [$order['cust_id'], $order_id]);

$page_title = "Order #{$order_id} - Staff";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/printflow/public/assets/css/output.css">
    <?php include __DIR__ . '/../includes/admin_style.php'; ?>
    <link rel="stylesheet" href="/printflow/public/assets/css/chat.css">
    <style>
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: 600; color: #1f2937; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #6b7280; font-size: 13px; text-decoration: none; transition: color 0.15s; }
        .back-link:hover { color: #1f2937; }
        .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; }
        .alert-error { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; }
        .customer-card { background: #f9fafb; border-radius: 12px; padding: 20px; border: 1px solid #f3f4f6; }
        .customer-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .customer-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <div class="main-content">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <a href="orders" class="back-link">← Back to Orders</a>
                    <h1 class="page-title" style="margin-top:4px;">Order #<?php echo $order_id; ?></h1>
                </div>
                <button type="button" onclick="openOrderChat(<?php echo $order_id; ?>, '<?php echo $order_id; ?>')" class="btn-primary" style="background:#4F46E5; color:white; border:none; padding:10px 20px; border-radius:10px; font-weight:700; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 6px -1px rgba(79,70,229,0.2);">
                    💬 Message Customer
                </button>
            </div>
        </header>

        <?php include __DIR__ . '/../includes/order_chat.php'; ?>

        <main>
            <?php if ($success): ?>
                <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="detail-grid" style="grid-template-columns: repeat(3, 1fr);">
                <!-- Order Information -->
                <div class="card">
                    <h2 style="font-size:18px; font-weight:600; margin-bottom:20px;">Order Information</h2>
                    <div class="detail-row">
                        <span class="detail-label">Order Date</span>
                        <span class="detail-value"><?php echo format_datetime($order['order_date']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount</span>
                        <span class="detail-value"><?php echo format_currency($order['total_amount']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Current Status</span>
                        <span class="detail-value"><?php echo status_badge($order['status'], 'order'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status</span>
                        <span class="detail-value"><?php echo status_badge($order['payment_status'], 'order'); ?></span>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                    <div style="margin-top:24px; padding:20px; background:linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border:1px solid #fde68a; border-radius:16px; box-shadow: 0 4px 6px -1px rgba(251, 191, 36, 0.1);">
                        <h3 style="font-size:15px; font-weight:800; color:#92400e; margin-bottom:10px; display:flex; align-items:center; gap:10px;">
                            <span style="font-size: 1.25rem;">📝</span> Global Customer Notes
                        </h3>
                        <div style="font-size:14px; color:#b45309; line-height:1.6; font-weight: 500;">
                            <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['payment_reference']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Payment Reference</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['payment_reference']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($order['downpayment_amount'] > 0): ?>
                    <div class="detail-row">
                        <span class="detail-label" style="color:#b45309; font-weight:600;">Mandatory Downpayment</span>
                        <span class="detail-value" style="color:#b45309; font-weight:700;"><?php echo format_currency($order['downpayment_amount']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'Cancelled'): ?>
                    <div style="margin-top:15px; padding:12px; background:#fef2f2; border:1px solid #fee2e2; border-radius:8px;">
                        <div style="font-weight:700; color:#ef4444; font-size:13px; margin-bottom:4px;">Cancellation Details:</div>
                        <div style="font-size:12px; color:#b91c1c;">
                            <strong>By:</strong> <?php echo htmlspecialchars($order['cancelled_by'] ?? 'Unknown'); ?><br>
                            <strong>Reason:</strong> <?php echo htmlspecialchars($order['cancel_reason'] ?? 'None'); ?><br>
                            <strong>At:</strong> <?php echo !empty($order['cancelled_at']) ? format_datetime($order['cancelled_at']) : 'N/A'; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'For Revision' && !empty($order['revision_reason'])): ?>
                    <div style="margin-top:15px; padding:12px; background:#eff6ff; border:1px solid #dbeafe; border-radius:8px;">
                        <div style="font-weight:700; color:#2563eb; font-size:13px; margin-bottom:4px;">Revision Requested:</div>
                        <div style="font-size:12px; color:#1e40af;">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($order['revision_reason']); ?><br>
                            <strong>Count:</strong> <?php echo (int)$order['revision_count']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Customer Information -->
                <div class="card">
                    <h2 style="font-size:18px; font-weight:600; margin-bottom:20px;">Customer Information</h2>
                    <div class="customer-card">
                        <div class="customer-header">
                            <div class="customer-avatar"><?php echo strtoupper(substr($order['cust_first'] ?? 'C', 0, 1)); ?></div>
                            <div>
                                <div style="font-weight:600; font-size:15px;"><?php echo htmlspecialchars(($order['cust_first'] ?? '') . ' ' . ($order['cust_last'] ?? '')); ?></div>
                                <div style="font-size:12px; color:#9ca3af;">Customer</div>
                            </div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['cust_email'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Contact Number</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['cust_phone'] ?? 'N/A'); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($customer_orders)): ?>
                    <div style="margin-top:20px;">
                        <h3 style="font-size:14px; font-weight:600; margin-bottom:12px;">Other Orders</h3>
                        <?php foreach ($customer_orders as $co): ?>
                        <div class="detail-row">
                            <span>
                                <a href="order_details.php?id=<?php echo $co['order_id']; ?>" style="color:#10b981; text-decoration:none; font-weight:500;">#<?php echo $co['order_id']; ?></a>
                                <span class="detail-label" style="margin-left:8px;"><?php echo format_date($co['order_date']); ?></span>
                            </span>
                            <span class="detail-value"><?php echo format_currency($co['total_amount']); ?> <?php echo status_badge($co['status'], 'order'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Order Management -->
                <div class="card">
                    <h3 style="font-size:14px; font-weight:600; margin-bottom:12px;">Order Management</h3>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <form method="POST" style="display:flex; flex-direction:column; gap:10px;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="update_status" value="1">
                            <select name="status" class="input-field">
                                <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Pending Approval" <?php echo $order['status'] === 'Pending Approval' ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="In Production" <?php echo $order['status'] === 'In Production' ? 'selected' : ''; ?>>In Production</option>
                                <option value="Printing" <?php echo $order['status'] === 'Printing' ? 'selected' : ''; ?>>Printing</option>
                                <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <button type="submit" class="btn-primary">Update Status</button>
                        </form>

                        <?php if (!in_array($order['status'], ['In Production', 'Printing', 'Completed', 'Cancelled'])): ?>
                            <div style="display:grid; grid-template-columns: 1fr; gap:10px;">
                                <button type="button" onclick="openRevisionModal()" class="btn-secondary" style="color:#d97706; border-color:#fde68a; justify-content:center;">
                                    📋 Request Revision
                                </button>
                                <button type="button" onclick="openStaffCancelModal()" class="btn-secondary" style="color:#dc2626; border-color:#fecaca; justify-content:center;">
                                    ✕ Cancel Order
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Revision Modal -->
                    <div id="revisionModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:20px;">
                        <div class="card" style="max-width:500px; width:100%; position:relative; background:white; padding:1.5rem; border-radius:12px;">
                            <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem; color:#111827;">Request Revision #<?php echo $order_id; ?></h2>
                            <p style="color:#6b7280; font-size:0.875rem; margin-bottom:1.5rem;">Tell the customer what needs to be fixed.</p>
                            <form action="request_revision_process.php" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                <div style="margin-bottom:1.5rem;">
                                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.75rem;">Reason for Revision *</label>
                                    <textarea name="revision_reason" class="input-field" style="width:100%; min-height:100px; font-size:0.9rem;" required></textarea>
                                </div>
                                <div style="display:flex; justify-content:flex-end; gap:12px;">
                                    <button type="button" onclick="closeRevisionModal()" class="btn-secondary">Close</button>
                                    <button type="submit" class="btn-primary" style="background:#d97706;">Send Request</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Cancel Modal -->
                    <div id="staffCancelModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:20px;">
                        <div class="card" style="max-width:500px; width:100%; position:relative; background:white; padding:1.5rem; border-radius:12px;">
                            <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem; color:#111827;">Cancel Order #<?php echo $order_id; ?></h2>
                            <form action="cancel_order_process.php" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                <div style="margin-bottom:1.5rem;">
                                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.75rem;">Reason for Cancellation *</label>
                                    <select name="reason" class="input-field" required>
                                        <option value="">-- Select a Reason --</option>
                                        <option value="Invalid design file">Invalid design file</option>
                                        <option value="Low quality">Low quality</option>
                                        <option value="Out of stock">Out of stock</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div style="display:flex; justify-content:flex-end; gap:12px;">
                                    <button type="button" onclick="closeStaffCancelModal()" class="btn-secondary">Close</button>
                                    <button type="submit" name="staff_cancel" class="btn-primary" style="background:#dc2626;">Confirm</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        function openStaffCancelModal() { document.getElementById('staffCancelModal').style.display = 'flex'; }
                        function closeStaffCancelModal() { document.getElementById('staffCancelModal').style.display = 'none'; }
                        function openRevisionModal() { document.getElementById('revisionModal').style.display = 'flex'; }
                        function closeRevisionModal() { document.getElementById('revisionModal').style.display = 'none'; }
                    </script>
                </div>
            </div>
            </div>

            <!-- Order Items -->
            <div class="card" style="margin-top:24px;">
                <h2 style="font-size:18px; font-weight:600; margin-bottom:20px;">Order Items</h2>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:24px;">No items found</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td style="font-weight:500;">
                                        <?php echo htmlspecialchars($item['product_name'] ?? 'Unknown'); ?>
                                        <?php if ($item['customization_data'] || !empty($item['design_image'])): ?>
                                                <div style="display: flex; gap: 2rem; align-items: stretch; justify-content: center;">
                                                    <!-- Left: Customization Details Grid -->
                                                    <div style="flex: 1.8; min-width: 0;">
                                                        <div style="font-weight:800; color:#475569; margin-bottom:1rem; text-transform:uppercase; letter-spacing:0.05em; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
                                                            Customization Details
                                                        </div>
                                                        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:1rem;">
                                                            <?php 
                                                            $custom_data = json_decode($item['customization_data'], true);
                                                            $description = '';
                                                            if ($custom_data):
                                                                foreach ($custom_data as $key => $val):
                                                                    if (!empty($val) && $key !== 'design_upload' && $key !== 'reference_upload'):
                                                                        if (strpos(strtolower($key), 'description') !== false):
                                                                            $description = $val;
                                                                            continue;
                                                                        endif;
                                                            ?>
                                                                <div style="padding: 6px 0;">
                                                                    <div style="color:#94a3b8; font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.025em;"><?php echo ucwords(str_replace('_', ' ', $key)); ?></div>
                                                                    <div style="font-weight:700; color: #1e293b; font-size: 1rem;"><?php echo htmlspecialchars($val); ?></div>
                                                                </div>
                                                            <?php 
                                                                    endif;
                                                                endforeach;
                                                            endif; 
                                                            ?>
                                                        </div>

                                                        <?php if ($description): ?>
                                                            <div style="margin-top: 1.25rem; padding: 16px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
                                                                <div style="font-size: 0.75rem; font-weight: 800; color: #92400e; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.05em;">📝 Design Description</div>
                                                                <div style="font-size: 0.95rem; color: #b45309; line-height: 1.6; font-weight: 600;">
                                                                    <?php echo nl2br(htmlspecialchars($description)); ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Right: Design & Reference Images -->
                                                    <div style="flex: 1.2; min-width: 0; padding-left: 1.5rem; border-left: 2px dashed #f1f5f9; display: flex; flex-direction: column; gap: 1.5rem;">
                                                        <?php if (!empty($item['design_image']) || !empty($item['design_file'])): ?>
                                                            <div>
                                                                <div style="font-weight:800; color:#475569; margin-bottom:0.75rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.025em;">Customer Design</div>
                                                                <?php $design_url = "/printflow/public/serve_design.php?type=order_item&id=" . (int)$item['order_item_id']; ?>
                                                                <a href="<?php echo $design_url; ?>" target="_blank" style="display: block; border-radius: 12px; overflow: hidden; border: 3px solid white; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
                                                                    <img src="<?php echo $design_url; ?>" style="width:100%; height:auto; display: block;" alt="Customer Design">
                                                                </a>
                                                                <a href="<?php echo $design_url; ?>" target="_blank" style="display: inline-block; margin-top: 10px; font-size:0.8rem; font-weight:800; color:#059669; text-decoration: none; background: #ecfdf5; padding: 6px 14px; border-radius: 8px;">↗ View Full</a>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($item['reference_image_file'])): ?>
                                                            <div>
                                                                <div style="font-weight:800; color:#475569; margin-bottom:0.75rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.025em;">Reference Image</div>
                                                                <?php $ref_url = "/printflow/public/serve_design.php?type=order_item&id=" . (int)$item['order_item_id'] . "&field=reference"; ?>
                                                                <a href="<?php echo $ref_url; ?>" target="_blank" style="display: block; border-radius: 12px; overflow: hidden; border: 3px solid white; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
                                                                    <img src="<?php echo $ref_url; ?>" style="width:100%; height:auto; display: block;" alt="Reference Image">
                                                                </a>
                                                                <a href="<?php echo $ref_url; ?>" target="_blank" style="display: inline-block; margin-top: 10px; font-size:0.8rem; font-weight:800; color:#1e40af; text-decoration: none; background: #eff6ff; padding: 6px 14px; border-radius: 8px;">↗ View Full</a>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (empty($item['design_image']) && empty($item['design_file']) && empty($item['reference_image_file'])): ?>
                                                            <div style="font-size:0.8rem; color:#9ca3af; font-style: italic; background: #f8fafc; padding: 20px; border-radius: 12px; text-align: center;">No images uploaded</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-family:monospace; font-size:12px;"><?php echo htmlspecialchars($item['sku'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? '—'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo format_currency($item['unit_price']); ?></td>
                                    <td style="font-weight:600;"><?php echo format_currency($item['quantity'] * $item['unit_price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr style="border-top:2px solid #e5e7eb;">
                                    <td colspan="5" style="text-align:right; font-weight:600;">Total</td>
                                    <td style="font-weight:700; font-size:16px;"><?php echo format_currency($order['total_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/success_modal.php'; ?>

<script>
    // Trigger success modal if success message exists in session
    window.addEventListener('DOMContentLoaded', () => {
        <?php if (isset($_SESSION['success'])): 
            $msg = $_SESSION['success'];
            unset($_SESSION['success']);
        ?>
        showSuccessModal(
            '✅ Action Successful',
            '<?php echo addslashes($msg); ?>',
            '#',
            'orders.php',
            'Stay on Page',
            'Back to Orders'
        );
        <?php endif; ?>
    });
</script>

</body>
</html>
