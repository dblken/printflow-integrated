<?php
/**
 * Shopping Cart Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

// Handle updates/removals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $pid => $qty) {
            if ($qty > 0 && isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid]['quantity'] = (int)$qty;
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $pid = $_POST['remove_item'];
        unset($_SESSION['cart'][$pid]);
    }
    header("Location: cart.php");
    exit;
}

$cart_items = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$page_title = 'Shopping Cart - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width:1100px;">
        <h1 class="ct-page-title">Shopping Cart</h1>

        <?php 
        $customer_id = get_user_id();
        $cancel_count = get_customer_cancel_count($customer_id);
        $is_restricted = is_customer_restricted($customer_id);
        
        if ($is_restricted): ?>
            <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem; color: #b91c1c; font-size: 0.95rem; display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">🚫</span>
                <div><strong>Account Restricted:</strong> You are currently blocked from placing new orders due to excessive cancellations (7+). Please contact support.</div>
            </div>
        <?php elseif ($cancel_count >= 3): ?>
            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem; display: flex; gap: 0.75rem; align-items: flex-start;">
                <span style="font-size: 1.5rem;">⚠️</span>
                <div>
                    <h3 style="color: #92400e; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.25rem;">Shopping Experience Warning</h3>
                    <p style="color: #b45309; font-size: 0.85rem; line-height: 1.5;">
                        You have <strong><?php echo $cancel_count; ?></strong> recent cancellations. 
                        <?php if ($cancel_count >= 4): ?>
                            Because you have 4 or more cancellations, <strong>'Pay Later' orders will require a 50% downpayment</strong> to proceed.
                        <?php else: ?>
                            Excessive cancellations may lead to payment restrictions or account suspension.
                        <?php endif; ?>
                        Complete a successful order to reset this counter!
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="ct-empty">
                <div class="ct-empty-icon">🛒</div>
                <p>Your cart is empty</p>
                <a href="products.php" class="btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="card" style="padding:0;">
                    <div class="overflow-x-auto">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead style="background:#f9fafb; font-size:0.875rem; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280;">
                                <tr>
                                    <th style="padding:1rem; text-align:left;">Product</th>
                                    <th style="padding:1rem; text-align:center;">Price</th>
                                    <th style="padding:1rem; text-align:center;">Quantity</th>
                                    <th style="padding:1rem; text-align:right;">Total</th>
                                    <th style="padding:1rem;"></th>
                                </tr>
                            </thead>
                            <tbody style="font-size:0.95rem;">
                                <?php foreach ($cart_items as $pid => $item): ?>
                                    <tr style="border-bottom:1px solid #f3f4f6;">
                                        <td style="padding:1rem; display:flex; align-items:center; gap:1rem;">
                                            <div style="width:48px; height:48px; background:#f3f4f6; border-radius:6px; display:flex; align-items:center; justify-content:center;">📦</div>
                                            <div>
                                                <div style="font-weight:600;"><?php echo htmlspecialchars($item['name']); ?></div>
                                            </div>
                                        </td>
                                        <td style="padding:1rem; text-align:center;">
                                            <?php echo format_currency($item['price']); ?>
                                        </td>
                                        <td style="padding:1rem; text-align:center;">
                                            <input type="number" name="quantities[<?php echo $pid; ?>]" value="<?php echo $item['quantity']; ?>" min="1" style="width:60px; text-align:center; padding:0.25rem; border:1px solid #d1d5db; border-radius:4px;">
                                        </td>
                                        <td style="padding:1rem; text-align:right; font-weight:600;">
                                            <?php echo format_currency($item['price'] * $item['quantity']); ?>
                                        </td>
                                        <td style="padding:1rem; text-align:center;">
                                            <button type="submit" name="remove_item" value="<?php echo $pid; ?>" style="color:#ef4444; background:none; border:none; cursor:pointer;" title="Remove">🗑️</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="padding:1.5rem; background:#f9fafb; display:flex; justify-content:space-between; align-items:center;">
                        <button type="submit" name="update_cart" class="btn-secondary" style="background:#fff; border:1px solid #d1d5db; padding:0.5rem 1rem; border-radius:6px;">Update Cart</button>
                        
                        <div style="text-align:right;">
                            <div style="font-size:0.875rem; color:#6b7280; margin-bottom:0.25rem;">Subtotal</div>
                            <div style="font-size:1.5rem; font-weight:700; color:#1f2937; margin-bottom:1rem;"><?php echo format_currency($total); ?></div>
                            <?php if ($is_restricted): ?>
                                <button type="button" class="btn-primary" style="padding:0.75rem 2rem; opacity:0.5; cursor:not-allowed;" disabled>Proceed to Checkout</button>
                            <?php else: ?>
                                <a href="checkout.php" class="btn-primary" style="padding:0.75rem 2rem;">Proceed to Checkout</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
