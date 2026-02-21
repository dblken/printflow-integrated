<?php
/**
 * Customer Order Details Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

$order_id = (int)($_GET['id'] ?? 0);
$customer_id = get_user_id();

if (!$order_id) {
    redirect('orders.php');
}

// Get order details (ensure it belongs to the customer)
$order_result = db_query("
    SELECT * FROM orders 
    WHERE order_id = ? AND customer_id = ?
", 'ii', [$order_id, $customer_id]);

if (empty($order_result)) {
    // Order not found or doesn't belong to customer
    redirect('orders.php');
}
$order = $order_result[0];

// Get order items
$items = db_query("
    SELECT oi.*, p.name as product_name, p.category
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
", 'i', [$order_id]);

$page_title = "Order #{$order_id} - PrintFlow";
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width:1100px;">
        <a href="orders.php" class="back-link" style="display:inline-flex; align-items:center; gap:6px; color:#6b7280; margin-bottom:1rem; text-decoration:none;">← Back to My Orders</a>
        
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:20px; margin-bottom:2rem; flex-wrap:wrap;">
            <div style="flex:1; min-width:200px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                    <h1 class="ct-page-title" style="margin:0;">Order #<?php echo $order_id; ?></h1>
                    <?php echo status_badge($order['status'], 'order'); ?>
                </div>
                <p style="margin:0; font-size:0.875rem; color:#6b7280;">Placed on <?php echo format_datetime($order['order_date']); ?></p>
            </div>
            
            <div style="display:flex; gap:12px; align-items:center;">
                <?php if ($order['status'] === 'For Revision'): ?>
                    <a href="edit_order.php?id=<?php echo $order_id; ?>" class="btn-primary" style="background:linear-gradient(135deg,#d97706,#f59e0b); color:white; border:none; padding:10px 20px; border-radius:10px; font-weight:700; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 6px -1px rgba(217,119,6,0.2);">
                        ✏️ Edit & Resubmit Order
                    </a>
                <?php endif; ?>

                <?php if (can_customer_cancel_order($order)): ?>
                    <button type="button" onclick="openCancelModal()" class="btn-secondary" style="color:#dc2626; border-color:#fecaca; padding:10px 20px; border-radius:10px; font-weight:600;">
                        ✕ Cancel Order
                    </button>
                <?php endif; ?>
            </div>
        </div>



        <!-- Revision Required Alert -->
        <?php if ($order['status'] === 'For Revision'): ?>

            <div style="background-color: #eff6ff; border: 1px solid #dbeafe; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center;">
                <div style="background: #2563eb; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.25rem;">ℹ️</div>
                <div>
                    <h3 style="color: #1e40af; font-weight: 700; font-size: 1rem; margin-bottom: 0.25rem;">Revision Required</h3>
                    <p style="color: #1e3a8a; font-size: 0.875rem; line-height: 1.5; margin-bottom:0.5rem;">
                        The shop has requested a revision for this order. Please review the reason below, update your order details, and resubmit.
                    </p>
                    <div style="background:white; border:1px solid #bfdbfe; padding:12px; border-radius:8px; font-size:0.9rem; color:#1e40af;">
                        <strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($order['revision_reason'] ?? 'Not specified')); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Cancellation Alert for Cancelled Orders -->
        <?php if ($order['status'] === 'Cancelled'): ?>
            <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center;">
                <div style="background: #ef4444; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.25rem;">✕</div>
                <div>
                    <h3 style="color: #991b1b; font-weight: 700; font-size: 1rem; margin-bottom: 0.25rem;">This order has been cancelled</h3>
                    <p style="color: #b91c1c; font-size: 0.875rem; line-height: 1.5;">
                        <strong>Cancelled By:</strong> <?php echo htmlspecialchars($order['cancelled_by'] ?? 'N/A'); ?><br>
                        <strong>Reason:</strong> <?php echo htmlspecialchars($order['cancel_reason'] ?? 'Not specified'); ?><br>
                        <strong>Date:</strong> <?php echo !empty($order['cancelled_at']) ? format_datetime($order['cancelled_at']) : 'N/A'; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Cancellation Modal -->
        <div id="cancelModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center; padding:20px;">
            <div class="card" style="max-width:500px; width:100%; position:relative;">
                <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem; color:#111827;">Cancel Order #<?php echo $order_id; ?></h2>
                <p style="color:#6b7280; font-size:0.875rem; margin-bottom:1.5rem;">Please tell us why you want to cancel this order. This cannot be undone.</p>
                
                <form action="cancel_order.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.75rem;">Reason for Cancellation</label>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="reason" value="Wrong item ordered" required> Wrong item ordered
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="reason" value="Found better price elsewhere"> Found better price elsewhere
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="reason" value="Changed my mind"> Changed my mind
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                                <input type="radio" name="reason" value="Other"> Other (Please specify below)
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Additional Details (Optional)</label>
                        <textarea name="details" class="input-field" style="width:100%; min-height:80px; font-size:0.9rem;" placeholder="e.g. personal issue..."></textarea>
                    </div>
                    
                    <div style="display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" onclick="closeCancelModal()" class="btn-secondary">Keep Order</button>
                        <button type="submit" name="confirm_cancel" class="btn-primary" style="background:#dc2626; color:white;">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openCancelModal() {
                document.getElementById('cancelModal').style.display = 'flex';
            }
            function closeCancelModal() {
                document.getElementById('cancelModal').style.display = 'none';
            }

            // Trigger success modal if success message exists
            window.addEventListener('DOMContentLoaded', () => {
                <?php if (isset($_SESSION['success'])): 
                    $msg = $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                showSuccessModal(
                    '✅ Action Completed',
                    '<?php echo addslashes($msg); ?>',
                    'orders.php',
                    'dashboard.php',
                    'View My Orders',
                    'Go to Dashboard'
                );
                <?php endif; ?>
            });
        </script>

        <div class="card" style="margin-bottom:2rem;">
            <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.5rem;">Order Information</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem;">
                <div>
                    <label style="display:block; font-size:0.875rem; color:#6b7280; margin-bottom:0.25rem;">Date Placed</label>
                    <div style="font-weight:600;"><?php echo format_datetime($order['order_date']); ?></div>
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; color:#6b7280; margin-bottom:0.25rem;">Total Amount</label>
                    <div style="font-weight:600;"><?php echo format_currency($order['total_amount']); ?></div>
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; color:#6b7280; margin-bottom:0.25rem;">Payment Status</label>
                    <div style="font-weight:600;"><?php echo status_badge($order['payment_status'], 'payment'); ?></div>
                </div>
                <div>
                    <label style="display:block; font-size:0.875rem; color:#6b7280; margin-bottom:0.25rem;">Estimated Completion</label>
                    <div style="font-weight:600;"><?php echo ($order['estimated_completion'] ?? null) ? format_date($order['estimated_completion']) : 'TBD'; ?></div>
                </div>
            </div>

            <?php if (!empty($order['notes'])): ?>
                <div style="margin-top:1.5rem; padding:1.25rem; background:#fffbeb; border:1px solid #fef3c7; border-radius:12px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.5rem;">
                        <span style="font-size:1.1rem;">📝</span>
                        <h3 style="font-size:0.95rem; font-weight:700; color:#92400e; margin:0;">Your Order Notes</h3>
                    </div>
                    <div style="font-size:0.9rem; color:#b45309; line-height:1.5;">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
            <div style="padding:1.25rem 1.5rem; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-size:1.1rem; font-weight:700; color:#111827; margin:0;">Order Items</h2>
                <div style="font-size:0.875rem; color:#6b7280;"><?php echo count($items); ?> Items</div>
            </div>
            <div class="overflow-x-auto">
                <table style="width:100%; border-collapse:collapse;">
                    <thead style="background:#f9fafb; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280;">
                        <tr>
                            <th style="padding:1rem 1.5rem; text-align:left; font-weight:600;">Product & Customization</th>
                            <th style="padding:1rem; text-align:center; font-weight:600;">Price</th>
                            <th style="padding:1rem; text-align:center; font-weight:600;">Quantity</th>
                            <th style="padding:1rem 1.5rem; text-align:right; font-weight:600;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody style="font-size:0.95rem;">
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:1.5rem;">
                                    <div style="display:flex; gap:1rem; align-items:flex-start;">
                                        <?php if (!empty($item['design_image'])): ?>
                                            <a href="/printflow/public/serve_design.php?type=order_item&id=<?php echo (int)$item['order_item_id']; ?>" target="_blank" style="display: block; width:60px; height:60px; border-radius: 8px; overflow: hidden; border: 2px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); flex-shrink:0;">
                                                <img src="/printflow/public/serve_design.php?type=order_item&id=<?php echo (int)$item['order_item_id']; ?>"
                                                     style="width:100%; height:100%; object-fit:cover;" 
                                                     alt="Design">
                                            </a>
                                        <?php else: ?>
                                            <div style="width:60px; height:60px; background:#f3f4f6; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid #e5e7eb; overflow:hidden;">
                                                <span style="font-size:1.5rem;">📦</span>
                                            </div>
                                        <?php endif; ?>
                                        <div style="min-width:0;">
                                            <div style="font-weight:700; color:#111827; margin-bottom:2px;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            <div style="font-size:0.75rem; color:#6b7280; margin-bottom:8px;"><?php echo htmlspecialchars($item['category']); ?></div>
                                            
                                            <?php if (!empty($item['customization_data'])): ?>
                                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                                    <?php 
                                                        $c_data = json_decode($item['customization_data'], true);
                                                        if ($c_data):
                                                            foreach ($c_data as $ck => $cv):
                                                                if (empty($cv) || $ck === 'notes' || $ck === 'design_upload') continue;
                                                    ?>
                                                        <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:500;">
                                                            <?php echo ucwords(str_replace('_', ' ', $ck)); ?>: <strong><?php echo htmlspecialchars($cv); ?></strong>
                                                        </span>
                                                    <?php 
                                                            endforeach;
                                                        endif;
                                                    ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($item['design_image'])): ?>
                                                <div style="margin-top:8px; font-size:0.75rem; color:#059669; font-weight:600; display:flex; align-items:center; gap:4px;">
                                                    <span style="background:#ecfdf5; padding:2px 6px; border-radius:4px;">✅ Design Provided</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:1rem; text-align:center; color:#4b5563;">
                                    <?php echo format_currency($item['unit_price']); ?>
                                </td>
                                <td style="padding:1rem; text-align:center; font-weight:600; color:#111827;">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td style="padding:1rem 1.5rem; text-align:right; font-weight:700; color:#111827;">
                                    <?php echo format_currency($item['unit_price'] * $item['quantity']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="padding:1.5rem; background:#f9fafb; border-top:2px solid #f3f4f6;">
                <div style="max-width:300px; margin-left:auto; display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; justify-content:space-between; font-size:0.95rem; color:#6b7280;">
                        <span>Total Items Value</span>
                        <span><?php echo format_currency($order['total_amount']); ?></span>
                    </div>
                    <?php if (($order['downpayment_amount'] ?? 0) > 0): ?>
                        <div style="display:flex; justify-content:space-between; font-size:0.95rem; color:#92400e;">
                            <span>Downpayment Required</span>
                            <span><?php echo format_currency($order['downpayment_amount']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e5e7eb; padding-top:12px; margin-top:4px;">
                        <span style="font-weight:700; color:#111827;">Total Amount</span>
                        <span style="font-size:1.5rem; font-weight:800; color:#4F46E5;"><?php echo format_currency($order['total_amount']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
