<?php
/**
 * Customer Order Creation / Product Details Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

$product_id = $_GET['product_id'] ?? 0;
$product = null;

if ($product_id) {
    $result = db_query("SELECT * FROM products WHERE product_id = ? AND status = 'Activated'", 'i', [$product_id]);
    if (!empty($result)) {
        $product = $result[0];
    }
}

if (!$product) {
    // Product not found or not activated
    header("Location: products.php");
    exit;
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if product already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => '📦' // Placeholder for now, or actual image path if available
            ];
        }
        
        // Redirect to cart or stay on page with success message
        header("Location: cart.php");
        exit;
    }
}

$page_title = $product['name'] . ' - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width:1100px;">
        <a href="products.php" class="back-link" style="display:inline-flex; align-items:center; gap:6px; color:#6b7280; margin-bottom:1rem; text-decoration:none;">← Back to Products</a>

        <div class="card" style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; padding:2rem;">
            <!-- Product Image Area -->
            <div style="background:#f3f4f6; border-radius:12px; display:flex; align-items:center; justify-content:center; min-height:400px; font-size:5rem;">
                📦
            </div>

            <!-- Product Details -->
            <div>
                <span class="ct-product-category" style="font-size:0.875rem; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; font-weight:600;"><?php echo htmlspecialchars($product['category']); ?></span>
                <h1 class="ct-page-title" style="margin-top:0.5rem; margin-bottom:1rem;"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div style="font-size:2rem; font-weight:700; color:#1f2937; margin-bottom:1.5rem;">
                    <?php echo format_currency($product['price']); ?>
                </div>

                <div style="margin-bottom:2rem; color:#4b5563; line-height:1.6;">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <form method="POST" style="margin-top:2rem;">
                        <input type="hidden" name="add_to_cart" value="1">
                        
                        <div style="margin-bottom:1.5rem;">
                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.5rem;">Quantity</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <button type="button" onclick="decrementQty()" style="width:40px; height:40px; border:1px solid #d1d5db; background:white; border-radius:6px; cursor:pointer;">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="width:60px; height:40px; text-align:center; border:1px solid #d1d5db; border-radius:6px;">
                                <button type="button" onclick="incrementQty()" style="width:40px; height:40px; border:1px solid #d1d5db; background:white; border-radius:6px; cursor:pointer;">+</button>
                                <span style="font-size:0.875rem; color:#6b7280; margin-left:10px;"><?php echo $product['stock_quantity']; ?> items available</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="width:100%; padding:1rem; font-size:1.1rem;">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <div style="padding:1rem; background:#fee2e2; color:#991b1b; border-radius:8px; text-align:center; font-weight:600;">
                        Out of Stock
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function incrementQty() {
        const input = document.getElementById('quantity');
        const max = parseInt(input.getAttribute('max'));
        let val = parseInt(input.value);
        if (val < max) {
            input.value = val + 1;
        }
    }
    
    function decrementQty() {
        const input = document.getElementById('quantity');
        let val = parseInt(input.value);
        if (val > 1) {
            input.value = val - 1;
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
