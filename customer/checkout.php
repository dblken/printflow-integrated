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

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        // Start Transaction (if supported, otherwise manual checks)
        // 1. Create Order
        $order_sql = "INSERT INTO orders (customer_id, order_date, total_amount, status, payment_status) 
                      VALUES (?, NOW(), ?, 'Pending', 'Unpaid')";
        
        $payment_method = $_POST['payment_method'] ?? 'pay_later';
        
        // Removed payment_method from query as column doesn't exist
        $order_id = db_execute($order_sql, 'id', [$customer_id, $total]);
        
        if ($order_id) {
            // 2. Insert Order Items
            $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
            
            foreach ($cart_items as $pid => $item) {
                db_execute($item_sql, 'iiid', [$order_id, $pid, $item['quantity'], $item['price']]);
                
                // Update stock logic could go here
            }
            
            // 3. Clear Cart
            unset($_SESSION['cart']);
            
            // 4. Notification
            create_notification($customer_id, 'Customer', "Order #{$order_id} placed successfully!", 'Order', true, false);
            
            // Notify Staff
            $staff_users = db_query("SELECT user_id FROM users WHERE role = 'Staff' AND status = 'Activated'");
            foreach ($staff_users as $staff) {
                create_notification($staff['user_id'], 'Staff', "New Order #{$order_id} received from {$customer['first_name']}!", 'Order', false, false);
            }
            
            // Redirect
            redirect("orders.php");
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
            </div>

            <!-- Order Summary -->
            <div class="card" style="height:fit-content;">
                <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem;">Order Summary</h2>
                <div style="margin-bottom:1.5rem;">
                    <?php foreach ($cart_items as $item): ?>
                        <div style="display:flex; justify-content:space-between; margin-bottom:0.75rem; font-size:0.9rem;">
                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                            <span style="font-weight:600;"><?php echo format_currency($item['price'] * $item['quantity']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="border-top:1px solid #f3f4f6; padding-top:1rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:600;">Total</span>
                    <span style="font-size:1.5rem; font-weight:700; color:#4F46E5;"><?php echo format_currency($total); ?></span>
                </div>
                
                <button type="submit" name="place_order" class="btn-primary" style="width:100%;">Place Order</button>
                <a href="cart.php" style="display:block; text-align:center; font-size:0.875rem; color:#6b7280; margin-top:1rem; text-decoration:none;">Returns to Cart</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
