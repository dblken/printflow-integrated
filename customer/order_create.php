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

// Handle Add to Cart / Buy Now
$error_msg = '';
$success_msg = '';

$customer_id = get_user_id();
$cancel_count = get_customer_cancel_count($customer_id);
$is_restricted = is_customer_restricted($customer_id);

if ($is_restricted) {
    $error_msg = "🚫 <strong>Account Restricted:</strong> You are currently blocked from placing new orders due to excessive cancellations (7+). Please contact support.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
    if ($is_restricted) {
        $error_msg = "Account restricted. Cannot place order.";
    } else {
        $quantity = (int)$_POST['quantity'];
        
        // ----------------------------------------------------------------
        // File Upload handling — image stored in session as binary (BLOB)
    // NO file is ever saved to the local filesystem.
    // Allowed: JPG / PNG only. Max: 5MB.
    // ----------------------------------------------------------------
    $design_binary = null;  // raw image bytes
    $design_mime   = null;  // e.g. 'image/jpeg'
    $design_name   = null;  // original filename for display

    if (isset($_FILES['design_upload']) && $_FILES['design_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['design_upload']['tmp_name'];
        $file_name = $_FILES['design_upload']['name'];
        $file_size = $_FILES['design_upload']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 1. Extension whitelist
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_ext)) {
            $error_msg = "Invalid file type. Only JPG and PNG images are allowed.";
        }
        // 2. File size limit (5 MB)
        elseif ($file_size > 5 * 1024 * 1024) {
            $error_msg = "File too large. Maximum size is 5MB.";
        }
        else {
            // 3. MIME validation using finfo (reads actual file bytes, not the extension)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            $allowed_mime = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($mime, $allowed_mime)) {
                $error_msg = "Invalid file content. Only JPG and PNG images are accepted.";
            } else {
                // 4. Read binary data — never move_uploaded_file()
                $data = file_get_contents($file_tmp);
                if ($data === false || $data === '') {
                    $error_msg = "Failed to read uploaded file. Please try again.";
                } else {
                    $design_binary = $data;
                    $design_mime   = $mime;
                    $design_name   = $file_name;
                }
            }
        }
    } elseif (isset($_FILES['design_upload']) && $_FILES['design_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
        // A file was attempted but had an upload error
        $error_msg = "File upload error. Please try again.";
    }

    // Collect customization data (everything except system fields)
    $customization = [];
    $system_fields = ['add_to_cart', 'buy_now', 'quantity', 'product_id', 'csrf_token'];
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $system_fields)) {
            $customization[$key] = sanitize($value);
        }
    }

    if (empty($error_msg) && $quantity > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Unique key per line item to support multiple customizations of the same product
        $item_key = $product_id . '_' . time();

        // Save binary image to a temp file (NOT in session — large data breaks session save)
        $design_tmp_path = null;
        if ($design_binary) {
            $design_tmp_path = tempnam(sys_get_temp_dir(), 'pf_design_');
            file_put_contents($design_tmp_path, $design_binary);
        }

        $_SESSION['cart'][$item_key] = [
            'product_id'     => $product_id,
            'name'           => $product['name'],
            'price'          => $product['price'],
            'quantity'       => $quantity,
            'image'          => '📦',
            'customization'  => $customization,
            // Store temp file path (not binary data — session can't handle large blobs)
            'design_tmp_path' => $design_tmp_path,
            'design_mime'     => $design_mime,
            'design_name'     => $design_name,
        ];
        
        // Redirect to review page (Buy Now) or cart (Add to Cart)
        if (isset($_POST['buy_now'])) {
            header("Location: order_review.php?item=" . urlencode($item_key));
        } else {
            header("Location: cart.php");
        }
        exit;
        }
    }
}

$page_title = $product['name'] . ' - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width:1100px;">
        <a href="products.php" class="back-link" style="display:inline-flex; align-items:center; gap:6px; color:#6b7280; margin-bottom:1rem; text-decoration:none;">← Back to Products</a>

        <?php if ($cancel_count >= 3 && !$is_restricted): ?>
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

        <?php if ($error_msg): ?>
            <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem; color: #b91c1c; font-size: 0.95rem; display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">🚫</span>
                <div><?php echo $error_msg; ?></div>
            </div>
        <?php endif; ?>

        <div class="card" style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; padding:2rem;">
            <!-- Product Image Area -->
            <div style="background:#f3f4f6; border-radius:12px; display:flex; align-items:center; justify-content:center; min-height:400px; font-size:5rem;">
                📦
            </div>

            <!-- Product Details -->
            <div>
                <h1 class="ct-page-title" style="margin-top:0.5rem; margin-bottom:1rem;"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div style="font-size:2rem; font-weight:700; color:#1f2937; margin-bottom:1.5rem;">
                    <?php echo format_currency($product['price']); ?>
                </div>

                <div style="margin-bottom:2rem; color:#4b5563; line-height:1.6;">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <?php if ($error_msg): ?>
                        <div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:12px; border-radius:8px; margin-bottom:1.5rem; font-size:0.9rem;">
                            ⚠️ <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="customization-form" style="margin-top:2rem;" x-data="{ 
                        category: '<?php echo $product['category']; ?>',
                        isValid: false,
                        validate() {
                            const form = document.getElementById('customization-form');
                            this.isValid = form.checkValidity();
                        }
                    }" @change="validate()" @input="validate()" x-init="validate()">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        
                        <!-- Customization Fields Section -->
                        <div style="background:#f9fafb; border:1px solid #f3f4f6; border-radius:12px; padding:1.5rem; margin-bottom:2rem;">
                            <h3 style="font-size:1rem; font-weight:700; color:#374151; margin-bottom:1.5rem; display:flex; align-items:center; gap:8px;">
                                🛠️ Customization Details
                            </h3>

                            <div class="service-fields">
                                <?php 
                                $cat = $product['category'];
                                ?>

                                <?php if ($cat === 'Tarpaulin Printing'): ?>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Width (ft) *</label>
                                            <input type="number" name="width" required class="input-field" placeholder="e.g. 2">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Height (ft) *</label>
                                            <input type="number" name="height" required class="input-field" placeholder="e.g. 3">
                                        </div>
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Finish Type *</label>
                                        <select name="finish_type" required class="input-field">
                                            <option value="Matte">Matte</option>
                                            <option value="Glossy" selected>Glossy</option>
                                        </select>
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">With Eyelets? *</label>
                                        <select name="with_eyelets" required class="input-field">
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'T-Shirt Printing'): ?>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Size *</label>
                                            <select name="size" required class="input-field">
                                                <option value="S">S</option>
                                                <option value="M" selected>M</option>
                                                <option value="L">L</option>
                                                <option value="XL">XL</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Color *</label>
                                            <select name="color" required class="input-field">
                                                <option value="Black">Black</option>
                                                <option value="White" selected>White</option>
                                                <option value="Red">Red</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Print Placement *</label>
                                        <select name="placement" required class="input-field">
                                            <option value="Front">Front</option>
                                            <option value="Back">Back</option>
                                            <option value="Both">Both</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'Stickers'): ?>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Shape *</label>
                                            <select name="shape" required class="input-field">
                                                <option value="Circle">Circle</option>
                                                <option value="Rectangle">Rectangle</option>
                                                <option value="Custom">Custom</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Size (inches) *</label>
                                            <input type="text" name="size" required class="input-field" placeholder="e.g. 2x2">
                                        </div>
                                    </div>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Finish *</label>
                                            <select name="finish" required class="input-field">
                                                <option value="Glossy">Glossy</option>
                                                <option value="Matte">Matte</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Waterproof? *</label>
                                            <select name="waterproof" required class="input-field">
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                    </div>

                                <?php elseif ($cat === 'Glass/Wall/Frosted'): ?>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Width *</label>
                                            <input type="text" name="width" required class="input-field">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Height *</label>
                                            <input type="text" name="height" required class="input-field">
                                        </div>
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Installation Needed? *</label>
                                        <select name="installation" required class="input-field">
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'Transparent Stickers'): ?>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Size *</label>
                                        <input type="text" name="size" required class="input-field" placeholder="e.g. 2x2">
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">With White Ink? *</label>
                                        <select name="white_ink" required class="input-field">
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'Layout Service'): ?>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Type of Layout *</label>
                                        <select name="layout_type" required class="input-field">
                                            <option value="Logo">Logo</option>
                                            <option value="Banner">Banner</option>
                                            <option value="Invitation">Invitation</option>
                                            <option value="Others">Others</option>
                                        </select>
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Rush? *</label>
                                        <select name="rush" required class="input-field">
                                            <option value="No">No</option>
                                            <option value="Yes">Yes</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'Reflectorized'): ?>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Size *</label>
                                        <input type="text" name="size" required class="input-field">
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Material Type *</label>
                                        <input type="text" name="material" required class="input-field">
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Installation? *</label>
                                        <select name="installation" required class="input-field">
                                            <option value="No">No</option>
                                            <option value="Yes">Yes</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'Stickers on Sintraboard'): ?>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Thickness *</label>
                                            <select name="thickness" required class="input-field">
                                                <option value="3mm">3mm</option>
                                                <option value="5mm">5mm</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Size *</label>
                                            <input type="text" name="size" required class="input-field">
                                        </div>
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">With Stand? *</label>
                                        <select name="with_stand" required class="input-field">
                                            <option value="No">No</option>
                                            <option value="Yes">Yes</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'Sintraboard Standees'): ?>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Size *</label>
                                        <input type="text" name="size" required class="input-field">
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">With Stand? *</label>
                                        <select name="with_stand" required class="input-field">
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>

                                <?php elseif ($cat === 'Souvenirs'): ?>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Type *</label>
                                        <input type="text" name="type" required class="input-field" placeholder="Mug, Keychain, etc.">
                                    </div>
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Custom Print? *</label>
                                        <select name="custom_print" required class="input-field">
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <!-- Common Fields -->
                                <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px dashed #d1d5db;">
                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">
                                            Upload Design/Reference (JPG or PNG only) *
                                        </label>
                                        <input type="file" name="design_upload" required class="input-field" accept=".jpg,.jpeg,.png" style="padding:0.4rem;">
                                        <p style="font-size:0.75rem; color:#6b7280; margin-top:4px;">Accepted: JPG, PNG · Max size: 5MB</p>
                                    </div>

                                    <div style="margin-bottom:1rem;">
                                        <label style="display:block; font-size:0.875rem; font-weight:600; margin-bottom:0.4rem;">Additional Notes</label>
                                        <textarea name="notes" rows="2" class="input-field" placeholder="Any special instructions..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom:2rem;">
                            <label style="display:block; font-size:0.875rem; font-weight:700; color:#374151; margin-bottom:0.5rem;">Total Quantity</label>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <button type="button" onclick="decrementQty()" style="width:44px; height:44px; border:1px solid #d1d5db; background:white; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.2rem; font-weight:600;">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="width:70px; height:44px; text-align:center; border:1px solid #d1d5db; border-radius:8px; font-weight:600; font-size:1.1rem;">
                                <button type="button" onclick="incrementQty()" style="width:44px; height:44px; border:1px solid #d1d5db; background:white; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.2rem; font-weight:600;">+</button>
                                <span style="font-size:0.875rem; color:#6b7280; font-weight:500;"><?php echo $product['stock_quantity']; ?> items available</span>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:10fr 21fr; gap:1rem;">
                            <button type="submit" name="add_to_cart" value="1" class="btn-secondary" :disabled="!isValid" style="padding:1.1rem; font-weight:600; cursor:pointer; border-radius:10px; opacity: 1;" :style="!isValid ? 'opacity:0.5; cursor:not-allowed' : ''">Add to Cart</button>
                            <button type="submit" name="buy_now" value="1" class="btn-primary" :disabled="!isValid" style="padding:1.1rem; font-weight:600; cursor:pointer; border-radius:10px; opacity: 1;" :style="!isValid ? 'opacity:0.5; cursor:not-allowed' : ''">BUY IT NOW</button>
                        </div>
                        
                        <p x-show="!isValid" style="color:#ef4444; font-size:0.75rem; margin-top:10px; text-align:center; font-weight:500;">
                            Please fill out all required fields and upload a design to proceed.
                        </p>
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
