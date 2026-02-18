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
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <div style="display:flex; align-items:center; gap:12px;">
                <h1 class="ct-page-title" style="margin:0;">Order #<?php echo $order_id; ?></h1>
                <?php echo status_badge($order['status'], 'order'); ?>
            </div>
            
            <?php if ($order['status'] === 'Pending'): ?>
                <button type="button" onclick="openCancelModal()" class="btn-secondary" style="color:#dc2626; border-color:#fecaca;">
                    ✕ Cancel Order
                </button>
            <?php endif; ?>
        </div>

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
                document.body.style.overflow = 'hidden';
            }
            function closeCancelModal() {
                document.getElementById('cancelModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            // Close on background click
            window.onclick = function(event) {
                const modal = document.getElementById('cancelModal');
                if (event.target == modal) {
                    closeCancelModal();
                }
            }
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
        </div>

        <div class="card">
            <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.5rem;">Order Items</h2>
            <div class="overflow-x-auto">
                <table style="width:100%; border-collapse:collapse;">
                    <thead style="background:#f9fafb; font-size:0.875rem; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280;">
                        <tr>
                            <th style="padding:0.75rem 1rem; text-align:left;">Product</th>
                            <th style="padding:0.75rem 1rem; text-align:center;">Price</th>
                            <th style="padding:0.75rem 1rem; text-align:center;">Quantity</th>
                            <th style="padding:0.75rem 1rem; text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody style="font-size:0.95rem;">
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:1rem; font-weight:500;">
                                    <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div style="font-size:0.75rem; color:#6b7280;"><?php echo htmlspecialchars($item['category']); ?></div>
                                </td>
                                <td style="padding:1rem; text-align:center;">
                                    <?php echo format_currency($item['unit_price']); ?>
                                </td>
                                <td style="padding:1rem; text-align:center;">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td style="padding:1rem; text-align:right; font-weight:600;">
                                    <?php echo format_currency($item['unit_price'] * $item['quantity']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" style="padding:1.5rem 1rem; text-align:right; font-weight:600;">Total</td>
                            <td style="padding:1.5rem 1rem; text-align:right; font-size:1.25rem; font-weight:700; color:#4F46E5;">
                                <?php echo format_currency($order['total_amount']); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
