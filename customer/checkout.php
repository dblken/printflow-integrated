<?php
/**
 * Checkout Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

$cart_items = $_SESSION['cart'] ?? [];

if (empty($cart_items)) {
    redirect('cart.php');
}

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$customer_id = get_user_id();
$customer = db_query("SELECT * FROM customers WHERE customer_id = ?", 'i', [$customer_id])[0];

// Fetch cancel count for downpayment check (needed on both GET and POST)
$cancel_count = get_customer_cancel_count($customer_id);
$is_restricted = is_customer_restricted($customer_id);

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    global $conn; // needed for send_long_data BLOB insertion
    
    // Check restriction AGAIN at submission
    $cancel_count = get_customer_cancel_count($customer_id);
    $is_restricted = is_customer_restricted($customer_id);
    
    if ($is_restricted) {
        $error = "🚫 Your account is restricted from placing new orders.";
    } elseif (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        // Calculate mandatory downpayment if cancel_count >= 4
        $downpayment_amount = 0;
        if ($cancel_count >= 4) {
            $downpayment_amount = $total * 0.5;
        }

        // Start Transaction (if supported, otherwise manual checks)
        // 1. Create Order
        $notes = $_POST['notes'] ?? null;
        $order_sql = "INSERT INTO orders (customer_id, order_date, total_amount, downpayment_amount, status, payment_status, notes) 
                      VALUES (?, NOW(), ?, ?, 'Pending Review', 'Unpaid', ?)";
        
        $payment_method = $_POST['payment_method'] ?? 'pay_later';
        
        // Removed payment_method from query as column doesn't exist
        $order_id = db_execute($order_sql, 'idds', [$customer_id, $total, $downpayment_amount, $notes]);
        
        if ($order_id) {
            // 2. Insert Order Items (design stored as LONGBLOB, never on disk)
            foreach ($cart_items as $pid => $item) {
                $custom_data    = isset($item['customization']) ? json_encode($item['customization']) : null;
                $design_binary  = null;
                $design_mime    = $item['design_mime']   ?? null;
                $design_name    = $item['design_name']   ?? null;

                // Read binary from temp file (session only stores path, not raw bytes)
                if (!empty($item['design_tmp_path']) && file_exists($item['design_tmp_path'])) {
                    $design_binary = file_get_contents($item['design_tmp_path']);
                }

                if ($design_binary) {
                    // INSERT with BLOB using send_long_data
                    $item_stmt = $conn->prepare(
                        "INSERT INTO order_items (order_id, product_id, quantity, unit_price, customization_data, design_image, design_image_mime, design_image_name)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    if ($item_stmt) {
                        $null = NULL;
                        $item_stmt->bind_param('iiidsbss',
                            $order_id,
                            $item['product_id'],
                            $item['quantity'],
                            $item['price'],
                            $custom_data,
                            $null,          // placeholder for BLOB
                            $design_mime,
                            $design_name
                        );
                        $item_stmt->send_long_data(5, $design_binary);
                        $item_stmt->execute();
                        $item_stmt->close();
                    }
                } else {
                    // No design uploaded — insert without BLOB
                    db_execute(
                        "INSERT INTO order_items (order_id, product_id, quantity, unit_price, customization_data)
                         VALUES (?, ?, ?, ?, ?)",
                        'iiids',
                        [$order_id, $item['product_id'], $item['quantity'], $item['price'], $custom_data]
                    );
                }
            }
            
            // 3. Clean up temp design files and clear Cart
            foreach ($cart_items as $ci) {
                if (!empty($ci['design_tmp_path']) && file_exists($ci['design_tmp_path'])) {
                    @unlink($ci['design_tmp_path']);
                }
            }
            unset($_SESSION['cart']);
            
            // 4. Notification
            create_notification($customer_id, 'Customer', "Order #{$order_id} placed successfully!", 'Order', true, false);
            
            // Notify Staff
            $staff_users = db_query("SELECT user_id FROM users WHERE role = 'Staff' AND status = 'Activated'");
            foreach ($staff_users as $staff) {
                create_notification($staff['user_id'], 'Staff', "New Order #{$order_id} received from {$customer['first_name']}!", 'Order', false, false);
            }
            
            $_SESSION['success'] = "Your order #{$order_id} has been placed successfully! Our team will review it shortly. You can track the status here.";
            
            // Redirect to the new order's details page
            redirect("order_details.php?id=$order_id");
        } else {
            $error = "Failed to place order. Please try again.";
        }
    } else {
        $error = "Invalid request.";
    }
}

$page_title = 'Checkout - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width:1100px;">
        <h1 class="ct-page-title">Checkout</h1>

        <form method="POST" style="display:grid; grid-template-columns: 1fr 340px; gap:2rem;">
            <?php echo csrf_field(); ?>
            
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                <?php if (isset($error)): ?>
                    <div class="alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Customer Info -->
                <div class="card">
                    <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.5rem;">Contact Information</h2>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" class="input-field" value="<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>" disabled style="background:#f9fafb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="text" class="input-field" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled style="background:#f9fafb;">
                        </div>
                        <div style="grid-column:span 2;">
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" class="input-field" value="<?php echo htmlspecialchars($customer['contact_number']); ?>" disabled style="background:#f9fafb;">
                        </div>
                    </div>
                    <p style="font-size:0.8rem; color:#6b7280; margin-top:10px;">* Please update your profile if this information is incorrect.</p>
                </div>
                
                <!-- Payment Method -->
                <div class="card">
                    <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.5rem;">Payment Method</h2>
                    <div>
                        <label style="display:flex; align-items:center; gap:10px; padding:10px; border:1px solid #d1d5db; border-radius:8px; cursor:pointer;">
                            <input type="radio" name="payment_method" value="pay_later" checked>
                            <span style="font-weight:600;">Cash on Pickup / Pay Later</span>
                        </label>
                    </div>
                </div>

                <!-- Global Order Notes -->
                <div class="card">
                    <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.5rem;">Order Notes (Optional)</h2>
                    <p style="font-size:0.85rem; color:#6b7280; margin-bottom:0.75rem;">Add any special instructions or general notes for your entire order here.</p>
                    <textarea name="notes" class="input-field" style="width:100%; min-height:100px; resize:vertical; font-size:0.9rem;" placeholder="e.g. Please wrap carefully, I will pick up around 5pm..."></textarea>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card" style="height:fit-content;">
                <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem;">Order Summary</h2>
                <div style="margin-bottom:1.5rem; display:flex; flex-direction:column; gap:1rem;">
                    <?php foreach ($cart_items as $item):
                        $item_total     = $item['price'] * $item['quantity'];
                        $custom         = $item['customization'] ?? [];
                        $design_preview = null;
                        if (!empty($item['design_tmp_path']) && file_exists($item['design_tmp_path']) && !empty($item['design_mime'])) {
                            $bin = file_get_contents($item['design_tmp_path']);
                            if ($bin) $design_preview = 'data:' . $item['design_mime'] . ';base64,' . base64_encode($bin);
                        }
                    ?>
                        <div style="border:1px solid #f3f4f6; border-radius:10px; padding:0.85rem; display:flex; gap:0.85rem; align-items:flex-start;">
                            <!-- Thumbnail -->
                            <div style="flex-shrink:0; width:58px; height:58px; border-radius:8px; overflow:hidden; background:#f3f4f6; display:flex; align-items:center; justify-content:center; border:1px solid #e5e7eb;">
                                <?php if ($design_preview): ?>
                                    <img src="<?php echo $design_preview; ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <span style="font-size:1.8rem;">📦</span>
                                <?php endif; ?>
                            </div>
                            <!-- Info -->
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:600; font-size:0.9rem; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <?php if (!empty($custom)): ?>
                                <div style="font-size:0.75rem; color:#6b7280; margin-top:3px;">
                                    <?php foreach ($custom as $k => $v):
                                        if ($v === '') continue;
                                    ?>
                                        <span style="background:#f3f4f6; padding:1px 6px; border-radius:4px; margin-right:4px;"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$k))); ?>: <strong><?php echo htmlspecialchars($v); ?></strong></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div style="font-size:0.78rem; color:#9ca3af; margin-top:4px;">Qty: <?php echo (int)$item['quantity']; ?> × <?php echo format_currency($item['price']); ?></div>
                            </div>
                            <!-- Price -->
                            <div style="font-weight:700; color:#4F46E5; white-space:nowrap;"><?php echo format_currency($item_total); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="border-top:1px solid #f3f4f6; padding-top:1rem; margin-bottom:1.5rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:600; color:#6b7280;">Subtotal</span>
                        <span style="font-weight:600;"><?php echo format_currency($total); ?></span>
                    </div>
                    <?php if ($cancel_count >= 4): 
                        $dp = $total * 0.5;
                        $bal = $total - $dp;
                    ?>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:600; color:#92400e;">Downpayment (50%)</span>
                            <span style="font-weight:700; color:#92400e;"><?php echo format_currency($dp); ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid #e5e7eb; padding-top:0.5rem; margin-top:0.5rem;">
                            <span style="font-weight:600;">Due at Pickup</span>
                            <span style="font-size:1.5rem; font-weight:700; color:#4F46E5;"><?php echo format_currency($bal); ?></span>
                        </div>
                    <?php else: ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid #e5e7eb; padding-top:0.5rem; margin-top:0.5rem;">
                            <span style="font-weight:600;">Total</span>
                            <span style="font-size:1.5rem; font-weight:700; color:#4F46E5;"><?php echo format_currency($total); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="place_order" class="btn-primary" style="width:100%;">Place Order</button>
                <a href="cart.php" style="display:block; text-align:center; font-size:0.875rem; color:#6b7280; margin-top:1rem; text-decoration:none;">Returns to Cart</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
