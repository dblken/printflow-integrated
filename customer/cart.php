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
                                            <?php
                                            $prod_id = (int)($item['product_id'] ?? 0);
                                            $product_img = "";
                                            
                                            // 1. Try explicit product ID
                                            if ($prod_id > 0) {
                                                $img_base = "../public/images/products/product_" . $prod_id;
                                                if (file_exists($img_base . ".jpg")) {
                                                    $product_img = "/printflow/public/images/products/product_" . $prod_id . ".jpg";
                                                } elseif (file_exists($img_base . ".png")) {
                                                    $product_img = "/printflow/public/images/products/product_" . $prod_id . ".png";
                                                }
                                            }
                                            
                                            // 2. Fallback based on category/service_type for Service Orders
                                            if (empty($product_img)) {
                                                $cat_lower = strtolower(($item['category'] ?? '') . ' ' . $item['name']);
                                                if (strpos($cat_lower, 'reflectorized') !== false || strpos($cat_lower, 'signage') !== false) {
                                                    $product_img = "/printflow/public/images/products/signage.jpg";
                                                } elseif (strpos($cat_lower, 'tarpaulin') !== false) {
                                                    $product_img = "/printflow/public/images/products/product_41.jpg";
                                                } elseif (strpos($cat_lower, 'sintraboard') !== false || strpos($cat_lower, 'standee') !== false) {
                                                    $product_img = "/printflow/public/images/services/Sintraboard Standees.jpg";
                                                } elseif (strpos($cat_lower, 't-shirt') !== false || strpos($cat_lower, 'shirt') !== false) {
                                                    $product_img = "/printflow/public/images/products/product_31.jpg";
                                                } elseif (strpos($cat_lower, 'sticker') !== false || strpos($cat_lower, 'decal') !== false) {
                                                    if (strpos($cat_lower, 'glass') !== false || strpos($cat_lower, 'frosted') !== false) {
                                                        $product_img = "/printflow/public/images/products/Glass Stickers  Wall  Frosted Stickers.png";
                                                    } else {
                                                        $product_img = "/printflow/public/images/products/product_21.jpg";
                                                    }
                                                } elseif (strpos($cat_lower, 'souvenir') !== false) {
                                                    $product_img = "/printflow/public/assets/images/icon-192.png";
                                                }
                                            }
                                            ?>
                                            <div style="width:48px; height:48px; border-radius:6px; overflow:hidden; border:1px solid #e2e8f0; background:#f8fafc; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                                <?php if (!empty($product_img)): ?>
                                                    <img src="<?php echo $product_img; ?>" style="width:100%; height:100%; object-fit:cover;" alt="Product">
                                                <?php else: ?>
                                                    <img src="/printflow/public/assets/images/icon-192.png" style="width:70%; height:70%; object-fit:contain; opacity:0.8;" alt="Logo">
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:600;"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <?php if (!empty($item['category'])): ?>
                                                    <div style="font-size:0.75rem; color:#6b7280;"><?php echo htmlspecialchars($item['category']); ?></div>
                                                <?php endif; ?>
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
                                            <button type="button" onclick="confirmRemove('<?php echo $pid; ?>')" style="color:#ef4444; background:none; border:none; cursor:pointer;" title="Remove">🗑️</button>
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

<!-- Remove Confirmation Modal -->
<div id="removeModal" style="display:none; position:fixed; inset:0; z-index:50; align-items:center; justify-content:center;">
    <div style="position:absolute; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(2px);" onclick="closeRemoveModal()"></div>
    <div style="position:relative; background:white; padding:2rem; border-radius:12px; max-width:400px; width:90%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); z-index:51;">
        <h3 style="font-size:1.25rem; font-weight:700; color:#111827; margin-bottom:0.5rem;">Remove from Cart?</h3>
        <p style="color:#4b5563; margin-bottom:1.5rem; line-height:1.5;">Are you sure you want to remove this item from your shopping cart?</p>
        <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
            <button type="button" onclick="closeRemoveModal()" style="padding:0.5rem 1.25rem; border-radius:8px; background:#f1f5f9; color:#475569; font-weight:600; border:none; cursor:pointer; transition:background 0.2s;">Cancel</button>
            <form method="POST" id="removeForm" style="margin:0;">
                <input type="hidden" name="remove_item" id="removeItemId" value="">
                <button type="submit" style="padding:0.5rem 1.25rem; border-radius:8px; background:#ef4444; color:white; font-weight:600; border:none; cursor:pointer; transition:background 0.2s;">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmRemove(pid) {
    document.getElementById('removeItemId').value = pid;
    document.getElementById('removeModal').style.display = 'flex';
}
function closeRemoveModal() {
    document.getElementById('removeModal').style.display = 'none';
    document.getElementById('removeItemId').value = '';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
