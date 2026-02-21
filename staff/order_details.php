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
        <header>
            <div>
                <a href="orders" class="back-link">← Back to Orders</a>
                <h1 class="page-title" style="margin-top:4px;">Order #<?php echo $order_id; ?></h1>
            </div>
        </header>

        <main>
            <?php if ($success): ?>
                <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="detail-grid">
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

                    <!-- Update Status Form -->
                    <!-- Update Status Form -->
                    <div style="margin-top:20px; padding-top:20px; border-top:1px solid #f3f4f6;">
                        <h3 style="font-size:14px; font-weight:600; margin-bottom:12px;">Order Management</h3>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <form method="POST" style="display:flex; gap:10px;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" class="input-field" style="flex:1;">
                                    <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Pending Approval" <?php echo $order['status'] === 'Pending Approval' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="In Production" <?php echo $order['status'] === 'In Production' ? 'selected' : ''; ?>>In Production</option>
                                    <option value="Printing" <?php echo $order['status'] === 'Printing' ? 'selected' : ''; ?>>Printing</option>
                                    <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <button type="submit" class="btn-primary">Update Status</button>
                            </form>

                            <?php if (!in_array($order['status'], ['In Production', 'Printing', 'Completed', 'Cancelled'])): ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                    <button type="button" onclick="openRevisionModal()" class="btn-secondary" style="color:#d97706; border-color:#fde68a; justify-content:center;">
                                        📋 Request Revision
                                    </button>
                                    <button type="button" onclick="openStaffCancelModal()" class="btn-secondary" style="color:#dc2626; border-color:#fecaca; justify-content:center;">
                                        ✕ Cancel Order
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Request Revision Modal -->
                    <div id="revisionModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:20px;">
                        <div class="card" style="max-width:500px; width:100%; position:relative; background:white; padding:1.5rem; border-radius:12px;">
                            <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem; color:#111827;">Request Revision #<?php echo $order_id; ?></h2>
                            <p style="color:#6b7280; font-size:0.875rem; margin-bottom:1.5rem;">Tell the customer what needs to be fixed. They will be allowed to edit and resubmit their order.</p>
                            
                            <form action="request_revision_process.php" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                
                                <div style="margin-bottom:1.5rem;">
                                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.75rem;">Reason for Revision *</label>
                                    <textarea name="revision_reason" class="input-field" style="width:100%; min-height:100px; font-size:0.9rem;" placeholder="e.g. Blurry image, wrong size specified, missing placement notes..." required></textarea>
                                </div>
                                
                                <div style="display:flex; justify-content:flex-end; gap:12px;">
                                    <button type="button" onclick="closeRevisionModal()" class="btn-secondary">Close</button>
                                    <button type="submit" class="btn-primary" style="background:#d97706; color:white; border:none;">Send Request</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Staff Cancellation Modal -->
                    <div id="staffCancelModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:20px;">
                        <div class="card" style="max-width:500px; width:100%; position:relative; background:white; padding:1.5rem; border-radius:12px;">
                            <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem; color:#111827;">Cancel Order #<?php echo $order_id; ?></h2>
                            <p style="color:#6b7280; font-size:0.875rem; margin-bottom:1rem;">Are you sure you want to cancel this order? This action cannot be undone.</p>
                            <p style="color:#dc2626; font-size:0.875rem; font-weight:600; margin-bottom:1.5rem;">⚠️ A cancellation reason is REQUIRED. This will be visible to the customer.</p>
                            
                            <form action="cancel_order_process.php" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                
                                <div style="margin-bottom:1.5rem;">
                                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.75rem;">Reason for Cancellation *</label>
                                    <select name="reason" class="input-field" required style="width:100%;">
                                        <option value="">-- Select a Reason --</option>
                                        <option value="Invalid design file">Invalid design file</option>
                                        <option value="Blurry or low-quality image">Blurry or low-quality image</option>
                                        <option value="Offensive or prohibited content">Offensive or prohibited content</option>
                                        <option value="Payment not verified">Payment not verified</option>
                                        <option value="Out of stock materials">Out of stock materials</option>
                                        <option value="Fraudulent order">Fraudulent order</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div style="margin-bottom:1.5rem;">
                                    <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Additional Notes (Optional)</label>
                                    <textarea name="notes" class="input-field" style="width:100%; min-height:80px; font-size:0.9rem;" placeholder="Public notes for the customer..."></textarea>
                                </div>
                                
                                <div style="display:flex; justify-content:flex-end; gap:12px;">
                                    <button type="button" onclick="closeStaffCancelModal()" class="btn-secondary">Close</button>
                                    <button type="submit" name="staff_cancel" class="btn-primary" style="background:#dc2626; color:white; border:none;">Confirm Cancellation</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        function openStaffCancelModal() {
                            document.getElementById('staffCancelModal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                        function closeStaffCancelModal() {
                            document.getElementById('staffCancelModal').style.display = 'none';
                            document.body.style.overflow = 'auto';
                        }
                        function openRevisionModal() {
                            document.getElementById('revisionModal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                        function closeRevisionModal() {
                            document.getElementById('revisionModal').style.display = 'none';
                            document.body.style.overflow = 'auto';
                        }

                        // Trigger success modal if success message exists
                        window.addEventListener('DOMContentLoaded', () => {
                            <?php if ($success): ?>
                            showSuccessModal(
                                '✅ Action Completed',
                                '<?php echo addslashes($success); ?>',
                                'orders.php',
                                'dashboard.php',
                                'View Orders List',
                                'Go to Dashboard'
                            );
                            <?php endif; ?>
                        });
                    </script>
                </div>

                <!-- Customer Information (Read-Only) -->
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
                        <h3 style="font-size:14px; font-weight:600; margin-bottom:12px;">Other Orders from Customer</h3>
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
                                            <div style="margin-top:1rem; font-size:0.8rem; background:#f9fafb; padding:1rem; border-radius:12px; border:1px solid #e5e7eb;">
                                                <div style="font-weight:800; color:#374151; margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-size: 0.7rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">
                                                    Customization Details
                                                </div>
                                                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap:0.75rem;">
                                                    <?php 
                                                    $custom_data = json_decode($item['customization_data'], true);
                                                    if ($custom_data):
                                                        foreach ($custom_data as $key => $val):
                                                            if (!empty($val) && $key !== 'design_upload'):
                                                    ?>
                                                        <div>
                                                            <div style="color:#9ca3af; font-size: 0.65rem; text-transform: uppercase; font-weight: 700;"><?php echo ucwords(str_replace('_', ' ', $key)); ?></div>
                                                            <div style="font-weight:600; color: #111827;"><?php echo htmlspecialchars($val); ?></div>
                                                        </div>
                                                    <?php 
                                                            endif;
                                                        endforeach;
                                                    endif; 
                                                    ?>
                                                </div>
                                                
                                                <?php if (!empty($item['design_image'])): ?>
                                                    <div style="margin-top:1.25rem; padding-top:12px; border-top:1px dashed #d1d5db;">
                                                        <div style="font-weight:800; color:#374151; margin-bottom:0.75rem; font-size: 0.7rem; text-transform: uppercase;">Customer Design</div>
                                                        <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                                            <a href="/printflow/public/serve_design.php?type=order_item&id=<?php echo (int)$item['order_item_id']; ?>" target="_blank" style="display: block; border-radius: 8px; overflow: hidden; border: 2px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                                                <img src="/printflow/public/serve_design.php?type=order_item&id=<?php echo (int)$item['order_item_id']; ?>"
                                                                     style="max-width:180px; display: block; cursor: zoom-in; transition: transform 0.2s;"
                                                                     onmouseover="this.style.transform='scale(1.02)'"
                                                                     onmouseout="this.style.transform='scale(1)'"
                                                                     alt="Customer Design"
                                                                     onerror="this.outerHTML='<span style=\'color:#6b7280;font-size:0.8rem;\'>⚠️ Could not load image</span>'">
                                                            </a>
                                                            <div style="flex: 1;">
                                                                <div style="font-size:0.8rem; font-weight:700; color:#059669; display: flex; align-items: center; gap: 4px;">
                                                                    ✅ Design File Ready
                                                                </div>
                                                                <?php if (!empty($item['design_image_name'])): ?>
                                                                    <div style="font-size:0.75rem; color:#6b7280; margin-top:4px; word-break: break-all;">
                                                                        <?php echo htmlspecialchars($item['design_image_name']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div style="margin-top: 10px;">
                                                                    <a href="/printflow/public/serve_design.php?type=order_item&id=<?php echo (int)$item['order_item_id']; ?>" target="_blank" class="btn-secondary" style="font-size: 0.75rem; padding: 4px 10px;">
                                                                        View Full Size
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="margin-top:1rem; font-size:0.8rem; color:#9ca3af; display: flex; align-items: center; gap: 6px;">
                                                        📂 No specific design file for this item.
                                                    </div>
                                                <?php endif; ?>
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
