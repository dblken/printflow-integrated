<?php
/**
 * Order Review & Confirm Page
 * PrintFlow — Shown when customer clicks "Buy Now"
 * Displays full order summary with design image preview,
 * customization details, price, and Cancel / Confirm buttons.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

// ── Accept the "buy_now" item key from session ──────────────────
$item_key = $_GET['item'] ?? '';
$cart     = $_SESSION['cart'] ?? [];

if (!$item_key || !isset($cart[$item_key])) {
    redirect('products.php');
}

$item        = $cart[$item_key];
$customer_id = get_user_id();
$customer    = db_query("SELECT * FROM customers WHERE customer_id = ?", 'i', [$customer_id])[0] ?? [];

$subtotal = $item['price'] * $item['quantity'];

// ── Handle Cancel ──────────────────────────────────────────────
if (isset($_GET['cancel'])) {
    // Clean up temp file
    if (!empty($item['design_tmp_path']) && file_exists($item['design_tmp_path'])) {
        @unlink($item['design_tmp_path']);
    }
    unset($_SESSION['cart'][$item_key]);
    redirect('products.php');
}

// ── Handle Place Order ─────────────────────────────────────────
$order_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $order_error = 'Invalid request. Please try again.';
    } else {
        // Check restriction AGAIN at submission
        $cancel_count = get_customer_cancel_count($customer_id);
        $is_restricted = is_customer_restricted($customer_id);

        if ($is_restricted) {
            $order_error = "🚫 Your account is restricted from placing new orders.";
        } else {
            global $conn;

            // Calculate mandatory downpayment if cancel_count >= 4
            $downpayment_amount = 0;
            if ($cancel_count >= 4) {
                $downpayment_amount = $subtotal * 0.5;
            }

            // 1. Create order
            $notes = $_POST['notes'] ?? ($item['customization']['notes'] ?? null);
            $order_sql = "INSERT INTO orders (customer_id, order_date, total_amount, downpayment_amount, status, payment_status, notes)
                          VALUES (?, NOW(), ?, ?, 'Pending Review', 'Unpaid', ?)";
            $order_id  = db_execute($order_sql, 'idds', [$customer_id, $subtotal, $downpayment_amount, $notes]);

            if ($order_id) {
                $custom_data   = isset($item['customization']) ? json_encode($item['customization']) : null;
                $design_binary = null;
                $design_mime   = $item['design_mime']   ?? null;
                $design_name   = $item['design_name']   ?? null;

                // Read binary from temp file
                if (!empty($item['design_tmp_path']) && file_exists($item['design_tmp_path'])) {
                    $design_binary = file_get_contents($item['design_tmp_path']);
                }

                if ($design_binary) {
                    $stmt = $conn->prepare(
                        "INSERT INTO order_items (order_id, product_id, quantity, unit_price, customization_data, design_image, design_image_mime, design_image_name)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    if ($stmt) {
                        $null = NULL;
                        $stmt->bind_param('iiidsbss',
                            $order_id,
                            $item['product_id'],
                            $item['quantity'],
                            $item['price'],
                            $custom_data,
                            $null,
                            $design_mime,
                            $design_name
                        );
                        $stmt->send_long_data(5, $design_binary);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    db_execute(
                        "INSERT INTO order_items (order_id, product_id, quantity, unit_price, customization_data) VALUES (?, ?, ?, ?, ?)",
                        'iiids',
                        [$order_id, $item['product_id'], $item['quantity'], $item['price'], $custom_data]
                    );
                }

                // Clean up temp file and remove from cart
                if (!empty($item['design_tmp_path']) && file_exists($item['design_tmp_path'])) {
                    @unlink($item['design_tmp_path']);
                }
                unset($_SESSION['cart'][$item_key]);

                // Notifications
                create_notification($customer_id, 'Customer', "Order #{$order_id} placed successfully!", 'Order', true, false);
                $staff_users = db_query("SELECT user_id FROM users WHERE role='Staff' AND status='Activated'");
                foreach ($staff_users as $staff) {
                    create_notification($staff['user_id'], 'Staff', "New Order #{$order_id} from {$customer['first_name']}!", 'Order', false, false);
                }

                $_SESSION['success'] = "Your order #{$order_id} has been placed successfully! Our team will review it shortly. You can track the status here.";
                redirect("order_details.php?id=$order_id");
            } else {
                $order_error = 'Failed to place order. Please try again.';
            }
        }
    }
}

// ── Build design preview (base64 for inline display) ───────────
$design_preview_src = null;
if (!empty($item['design_tmp_path']) && file_exists($item['design_tmp_path']) && !empty($item['design_mime'])) {
    $binary = file_get_contents($item['design_tmp_path']);
    if ($binary) {
        $design_preview_src = 'data:' . $item['design_mime'] . ';base64,' . base64_encode($binary);
    }
}

$page_title      = 'Review Your Order — PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
<div class="container mx-auto px-4" style="max-width:860px;">

    <!-- Header -->
    <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.75rem;">
        <a href="products.php" style="color:#6b7280; text-decoration:none; font-size:0.9rem;">← Back to Products</a>
        <h1 class="ct-page-title" style="margin:0; flex:1; text-align:center;">Review Your Order</h1>
        <span style="width:120px;"></span>
    </div>

    <?php if ($order_error): ?>
        <div class="alert-error" style="margin-bottom:1.5rem;"><?php echo htmlspecialchars($order_error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrf_field(); ?>

        <div style="display:grid; grid-template-columns: 1fr 320px; gap:1.5rem; align-items:start;">

            <!-- Left: Product details -->
            <div style="display:flex; flex-direction:column; gap:1.25rem;">

                <!-- Product card -->
                <div class="card" style="display:flex; gap:1.25rem; align-items:flex-start;">
                    <!-- Design image preview or product icon -->
                    <div style="flex-shrink:0; width:140px; height:140px; border-radius:10px; overflow:hidden; border:1px solid #e5e7eb; background:#f3f4f6; display:flex; align-items:center; justify-content:center;">
                        <?php if ($design_preview_src): ?>
                            <img src="<?php echo $design_preview_src; ?>"
                                 alt="Your uploaded design"
                                 style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size:3.5rem;">📦</span>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div style="font-size:1.15rem; font-weight:700; color:#1f2937; margin-bottom:4px;">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <div style="font-size:0.85rem; color:#6b7280; margin-bottom:10px;">Product Order</div>

                        <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
                            <div>
                                <div style="font-size:0.75rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em;">Unit Price</div>
                                <div style="font-weight:700; color:#4F46E5; font-size:1.05rem;"><?php echo format_currency($item['price']); ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em;">Quantity</div>
                                <div style="font-weight:700; color:#1f2937; font-size:1.05rem;"><?php echo (int)$item['quantity']; ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em;">Subtotal</div>
                                <div style="font-weight:700; color:#1f2937; font-size:1.05rem;"><?php echo format_currency($subtotal); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customization details -->
                <?php
                $custom = $item['customization'] ?? [];
                $label_skip = ['design_upload']; // file inputs
                $has_custom = !empty(array_filter($custom, fn($v) => $v !== ''));
                ?>
                <?php if ($has_custom || $design_preview_src): ?>
                <div class="card">
                    <h2 style="font-size:1rem; font-weight:700; color:#374151; margin:0 0 1rem 0; padding-bottom:0.6rem; border-bottom:1px solid #f3f4f6;">
                        🎨 Customization Details
                    </h2>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:0.75rem 1.5rem;">
                        <?php foreach ($custom as $key => $val):
                            if ($val === '' || in_array($key, $label_skip)) continue;
                            $label = ucwords(str_replace(['_', '-'], ' ', $key));
                        ?>
                        <div>
                            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px;"><?php echo htmlspecialchars($label); ?></div>
                            <div style="font-size:0.9rem; font-weight:600; color:#1f2937;"><?php echo htmlspecialchars($val); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($design_preview_src): ?>
                    <div style="margin-top:1.25rem; padding-top:1.25rem; border-top:1px dashed #e5e7eb;">
                        <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.6rem;">Uploaded Design</div>
                        <div style="display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
                            <img src="<?php echo $design_preview_src; ?>"
                                 alt="Uploaded design"
                                 style="max-width:220px; max-height:180px; border-radius:8px; border:1px solid #e5e7eb; object-fit:contain; background:#f9fafb;">
                            <div>
                                <div style="font-size:0.85rem; font-weight:600; color:#15803d; display:flex; align-items:center; gap:6px;">
                                    ✅ Image uploaded successfully
                                </div>
                                <?php if (!empty($item['design_name'])): ?>
                                    <div style="font-size:0.78rem; color:#6b7280; margin-top:4px;">
                                        📄 <?php echo htmlspecialchars($item['design_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <div style="font-size:0.78rem; color:#9ca3af; margin-top:8px;">
                                    This image will be reviewed by our staff.
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Global Order Notes -->
                <div class="card">
                    <h2 style="font-size:1rem; font-weight:700; color:#374151; margin:0 0 1rem 0; padding-bottom:0.6rem; border-bottom:1px solid #f3f4f6;">
                        📝 Global Order Notes (Optional)
                    </h2>
                    <p style="font-size:0.8rem; color:#6b7280; margin-bottom:0.75rem;">Add any special instructions or general notes for this order.</p>
                    <textarea name="notes" class="input-field" style="width:100%; min-height:80px; resize:vertical; font-size:0.9rem;" placeholder="e.g. Please pick up around 5pm, wrap carefully..."><?php echo htmlspecialchars($item['customization']['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Contact info -->
                <div class="card">
                    <h2 style="font-size:1rem; font-weight:700; color:#374151; margin:0 0 1rem 0; padding-bottom:0.6rem; border-bottom:1px solid #f3f4f6;">
                        📋 Contact Information
                    </h2>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                        <div>
                            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Name</div>
                            <div style="font-weight:600; color:#1f2937;"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                        </div>
                        <div>
                            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Phone</div>
                            <div style="font-weight:600; color:#1f2937;"><?php echo htmlspecialchars($customer['contact_number'] ?? '—'); ?></div>
                        </div>
                        <div style="grid-column:span 2;">
                            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Email</div>
                            <div style="font-weight:600; color:#1f2937;"><?php echo htmlspecialchars($customer['email']); ?></div>
                        </div>
                    </div>
                    <p style="font-size:0.78rem; color:#9ca3af; margin-top:10px;">To update your info, visit your Profile page.</p>
                </div>
            </div>

            <!-- Right: Summary & actions -->
            <div style="display:flex; flex-direction:column; gap:1rem; position:sticky; top:100px;">
                <div class="card">
                    <h2 style="font-size:1rem; font-weight:700; color:#374151; margin:0 0 1rem 0;">Order Summary</h2>

                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; margin-bottom:0.5rem; color:#6b7280;">
                        <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo (int)$item['quantity']; ?></span>
                        <span><?php echo format_currency($subtotal); ?></span>
                    </div>

                    <div style="border-top:1px solid #f3f4f6; padding-top:0.75rem; margin-top:0.75rem; margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:700; font-size:0.95rem;">Total</span>
                        <span style="font-size:1.4rem; font-weight:800; color:#4F46E5;"><?php echo format_currency($subtotal); ?></span>
                    </div>

                    <div style="background:#fef9c3; border:1px solid #fde047; border-radius:8px; padding:10px 14px; font-size:0.8rem; color:#854d0e; margin-bottom:1.25rem;">
                        💳 <strong>Cash on Pickup / Pay Later</strong><br>
                        You'll pay when you pick up your order at our shop.
                    </div>

                    <!-- Place Order -->
                    <button type="submit" name="confirm_order"
                            style="width:100%; padding:14px; background:linear-gradient(135deg,#4F46E5,#7C3AED); color:#fff; font-size:1rem; font-weight:700; border:none; border-radius:10px; cursor:pointer; letter-spacing:.02em; transition:opacity .2s;"
                            onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                        ✅ Confirm &amp; Place Order
                    </button>

                    <!-- Cancel -->
                    <a href="?item=<?php echo urlencode($item_key); ?>&cancel=1"
                       onclick="return confirm('Cancel this order? Your selections will be lost.');"
                       style="display:block; text-align:center; margin-top:0.85rem; font-size:0.875rem; color:#ef4444; text-decoration:none; font-weight:600; padding:10px; border:1px solid #fecaca; border-radius:8px; transition:background .2s;"
                       onmouseover="this.style.background='#fff1f2'" onmouseout="this.style.background='transparent'">
                        ✕ Cancel Order
                    </a>
                </div>

                <!-- Safety note -->
                <div style="font-size:0.78rem; color:#9ca3af; text-align:center; line-height:1.6;">
                    🔒 Your order details are secure.<br>
                    You can always cancel from My Orders after placing.
                </div>
            </div>
        </div>
    </form>
</div>
</div>

<style>
@media (max-width: 700px) {
    form > div { grid-template-columns: 1fr !important; }
    .sticky-aside { position:static !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
