<?php
/**
 * Customer Dashboard
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require customer access
require_role('Customer');

$customer_id = get_user_id();

// Get specific products for the Decals & Stickers Showcase (New dedicated IDs: 21-29)
$featured_products = db_query("
    SELECT * FROM products
    WHERE product_id IN (21, 22, 23, 24, 25, 26, 27, 28, 29)
    ORDER BY product_id ASC
", '', []);

// Get specific products for the T-Shirt Grid (IDs: 31-34)
$tshirt_products = db_query("
    SELECT * FROM products
    WHERE product_id IN (31, 32, 33, 34)
    ORDER BY product_id ASC
", '', []);

// Get specific products for the Tarpaulin "JUST FOR YOU" section (IDs: 41-49)
$tarpaulin_products = db_query("
    SELECT * FROM products
    WHERE product_id BETWEEN 41 AND 49
    ORDER BY product_id ASC
", '', []);

// Get "From the Feed" Products (IDs 51-54)
$feed_products = db_query("
    SELECT * FROM products 
    WHERE product_id BETWEEN 51 AND 54 
    AND status = 'Activated' 
    ORDER BY product_id ASC
", '', []);

// Get recent notifications
$dashboard_notifications = db_query("SELECT * FROM notifications WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5", 'i', [$customer_id]);

// Get recent orders
$recent_orders = db_query("
    SELECT * FROM orders 
    WHERE customer_id = ? 
    ORDER BY order_date DESC 
    LIMIT 5
", 'i', [$customer_id]);

$page_title = 'Customer Dashboard - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width:1100px;">

        <!-- Order a Service -->
        <div class="card" style="margin-bottom:3rem; padding: 2rem;">
            <h2 class="ct-section-title">Order a Service</h2>
            <p class="text-gray-600 text-sm mb-4">Choose a service and submit your order form.</p>
            <div class="flex flex-wrap gap-2">
                <a href="order_tarpaulin.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Tarpaulin</a>
                <a href="order_tshirt.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">T-Shirt</a>
                <a href="order_stickers.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Stickers</a>
                <a href="order_glass_stickers.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Glass/Wall</a>
                <a href="order_transparent.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Transparent Stickers</a>
                <a href="order_reflectorized.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Reflectorized</a>
                <a href="order_sintraboard.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Sintraboard</a>
                <a href="order_standees.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Standees</a>
                <a href="order_souvenirs.php" class="px-3 py-2 bg-gray-50 text-black border border-gray-200 rounded text-sm font-medium hover:bg-black hover:text-white transition-colors">Souvenirs</a>
            </div>
            <p class="mt-4"><a href="service_orders.php" class="text-black font-semibold hover:underline">View My Service Orders →</a></p>
        </div>
    </div> <!-- End of container for full-width breakout -->

    <!-- Decals & Stickers Showcase (Full Width) -->
    <?php if (!empty($featured_products)): ?>
    <section class="ct-full-width-section">
        <div class="ct-showcase-header">
            <h2 class="ct-section-title" style="text-transform:uppercase; letter-spacing:0.12em; margin-bottom:0;">Decals & Stickers</h2>
            <a href="products.php?category=Decals%20%26%20Stickers" class="text-sm font-bold text-black border-b-2 border-black">View All</a>
        </div>
        
        <div class="ct-showcase-container">
            <?php foreach ($featured_products as $product): ?>
                <div class="ct-product-card">
                    <div class="ct-product-img">
                        <div class="ct-product-img-inner">
                            <?php 
                            $img_link = "/printflow/public/images/products/product_" . $product['product_id'];
                            $img_path = __DIR__ . "/../public/images/products/product_" . $product['product_id'];
                            $display_img = "";
                            if (file_exists($img_path . ".jpg")) {
                                $display_img = $img_link . ".jpg";
                            } elseif (file_exists($img_path . ".png")) {
                                $display_img = $img_link . ".png";
                            }
                            
                            if ($display_img): ?>
                                <img src="<?php echo $display_img; ?>" alt="Custom Decals" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f8f9fa; font-size:3rem;">📦</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ct-product-body" style="text-align: center;">
                        <h3 class="ct-product-name" style="margin-bottom: 1.5rem; height: auto; overflow: visible; font-weight: 700; font-size: 1.1rem; line-height: 1.2;"><?php echo htmlspecialchars($product['name']); ?></h3>
                        
                        <div class="ct-product-actions" style="margin-top: 0; display: flex; justify-content: center;">
                            <a href="order_create.php?product_id=<?php echo $product['product_id']; ?>" class="ct-view-product-btn" style="width: 100%; text-align: center;">
                                START CUSTOMIZING
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sintraboard Standees Hero Section (Adidas Style) - Reduced top gap -->
    <section class="ct-hero-section" style="margin-top: -1rem; margin-bottom: 2rem;">
        <div class="ct-hero-img-box">
            <img src="/printflow/public/images/services/Sintraboard Standees.jpg" alt="Sintraboard Standees">
            <div class="ct-hero-content">
                <h2 class="ct-hero-title">Sintraboard<br>Standees</h2>
                <a href="order_sintraboard.php?sintra_type=With+Face+Hole" class="ct-hero-btn">START CUSTOMIZING</a>
            </div>
        </div>
    </section>

    <!-- T-Shirt Customization Grid (Adidas Lookbook Style) -->
    <?php if (!empty($tshirt_products)): ?>
    <section class="ct-tshirt-grid-section">
        <div class="ct-showcase-header">
            <h2 class="ct-section-title" style="text-transform:uppercase; letter-spacing:0.12em; margin-bottom:0;">All shirt customizing/ Personalized</h2>
            <a href="products.php?category=T-Shirt%20Printing" class="text-sm font-bold text-black border-b-2 border-black">View All</a>
        </div>
        
        <div class="ct-tshirt-grid">
            <?php foreach ($tshirt_products as $product): ?>
                <div class="ct-tshirt-card">
                    <?php 
                    $img_path = "/printflow/public/images/products/product_" . $product['product_id'] . ".jpg";
                    ?>
                    <img src="<?php echo $img_path; ?>" alt="T-Shirt Custom">
                    <div class="ct-tshirt-overlay">
                        <a href="order_create.php?product_id=<?php echo $product['product_id']; ?>" class="ct-hero-btn">START CUSTOMIZING</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tarpaulin "JUST FOR YOU" Section (Adidas Style Carousel) -->
    <?php if (!empty($tarpaulin_products)): ?>
    <section class="ct-jfy-section">

        <div class="ct-jfy-header">
            <div class="ct-jfy-title-box">
                <h2 class="ct-jfy-title">JUST FOR YOU</h2>
            </div>
        </div>
        
        <div class="ct-jfy-container">
            <?php foreach ($tarpaulin_products as $product): ?>
                <div class="ct-jfy-card">
                    <a href="order_create.php?product_id=<?php echo $product['product_id']; ?>" class="ct-wishlist-btn" title="Add to Cart">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    </a>
                    <div class="ct-jfy-img-box">
                        <?php 
                        $img_path = "/printflow/public/images/products/product_" . $product['product_id'] . ".jpg";
                        ?>
                        <img src="<?php echo $img_path; ?>" alt="Tarpaulin">
                    </div>
                    <div class="ct-jfy-body">
                        <p class="ct-jfy-name"><?php echo htmlspecialchars($product['name']); ?></p>
                        <p class="ct-jfy-category"><?php echo htmlspecialchars($product['category']); ?></p>
                        <a href="order_create.php?product_id=<?php echo $product['product_id']; ?>" class="text-sm font-bold border-b border-black mt-2 inline-block">Order Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sintraboard Flat Section (Sintra Board Standees Inspired Layout) -->
    <?php if (!empty($feed_products)): ?>
    <section class="ct-feed-section">
        <div class="ct-feed-header-main">
            <h2 class="ct-feed-title-main" style="text-transform:uppercase; letter-spacing:0.12em;">sintraboard flat</h2>
        </div>
        
        <div class="ct-feed-container">
            <?php foreach ($feed_products as $index => $p): 
                $img = !empty($p['product_image']) ? "/printflow/" . $p['product_image'] : '/printflow/public/assets/images/placeholder.jpg';
            ?>
            <div class="ct-feed-card">
                <div class="ct-feed-img-box">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <div class="ct-feed-overlay">
                        <div class="ct-feed-action">
                            <a href="order_sintraboard.php?sintra_type=Flat+Type" class="ct-feed-btn">START CUSTOMIZING</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Glass & Wall Sticker Printing Section -->
    <section class="ct-feed-section" style="margin: 2rem auto; max-width: 1400px; padding: 0 1rem;">
        <div class="ct-feed-header-main">
            <h2 class="ct-feed-title-main" style="text-transform:uppercase; letter-spacing:0.12em;">Glass & Wall Sticker Printing</h2>
        </div>
        
        <div class="ct-feed-container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; max-width: none;">
            <div class="ct-feed-card">
                <div class="ct-feed-img-box" style="aspect-ratio: 16/9;">
                    <img src="/printflow/public/images/products/Glass Stickers  Wall  Frosted Stickers.png" alt="Glass Stickers Wall Frosted Stickers" style="width:100%; height:100%; object-fit:cover;">
                    <div class="ct-feed-overlay">
                        <div class="ct-feed-action">
                            <a href="order_glass_stickers.php" class="ct-feed-btn">START CUSTOMIZING</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ct-feed-card">
                <div class="ct-feed-img-box" style="aspect-ratio: 16/9;">
                    <img src="/printflow/public/images/products/Glass Stickers  Wall  Frosted Stickers2.png" alt="Glass Stickers Wall Frosted Stickers 2" style="width:100%; height:100%; object-fit:cover;">
                    <div class="ct-feed-overlay">
                        <div class="ct-feed-action">
                            <a href="order_glass_stickers.php" class="ct-feed-btn">START CUSTOMIZING</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reflectorized (Subdivision Stickers / Signages) Section -->
    <section class="ct-feed-section" style="margin: 4rem auto; max-width: 1600px; padding: 0 1rem;">
        <div class="ct-feed-header-main">
            <h2 class="ct-feed-title-main" style="text-transform:uppercase; letter-spacing:0.12em;">Reflectorized (Subdivision Stickers / Signages)</h2>
        </div>
        
        <div class="ct-feed-container" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; max-width: none;">
            <div class="ct-feed-card">
                <div class="ct-feed-img-box" style="aspect-ratio: 3/2; background: #fff;">
                    <img src="/printflow/public/images/products/signage.jpg" alt="Signage 1" style="width:100%; height:100%; object-fit:contain;">
                    <div class="ct-feed-overlay">
                        <div class="ct-feed-action">
                            <a href="order_reflectorized.php" class="ct-feed-btn">START CUSTOMIZING</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ct-feed-card">
                <div class="ct-feed-img-box" style="aspect-ratio: 3/2; background: #fff;">
                    <img src="/printflow/public/images/products/signage1.jpg" alt="Signage 2" style="width:100%; height:100%; object-fit:contain;">
                    <div class="ct-feed-overlay">
                        <div class="ct-feed-action">
                            <a href="order_reflectorized.php" class="ct-feed-btn">START CUSTOMIZING</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ct-feed-card">
                <div class="ct-feed-img-box" style="aspect-ratio: 3/2; background: #fff;">
                    <img src="/printflow/public/images/products/signage2.jpg" alt="Signage 3" style="width:100%; height:100%; object-fit:contain;">
                    <div class="ct-feed-overlay">
                        <div class="ct-feed-action">
                            <a href="order_reflectorized.php" class="ct-feed-btn">START CUSTOMIZING</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <div class="container mx-auto px-4" style="max-width:1100px;">
        <!-- Recent Notifications -->
        <div class="card" style="margin-bottom:2rem; padding: 2rem;">
            <div class="flex items-center justify-between mb-6">
                <h2 class="ct-section-title" style="margin-bottom:0;">Recent Notifications</h2>
                <a href="notifications.php" class="text-sm font-bold text-black border-b-2 border-black">See All Notifications</a>
            </div>

            <?php if (empty($dashboard_notifications)): ?>
                <p class="text-gray-500 text-center py-4">No recent notifications.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($dashboard_notifications as $notif): ?>
                        <div class="p-3 rounded-lg border <?php echo !$notif['is_read'] ? 'bg-gray-50 border-black' : 'bg-white border-gray-100'; ?>">
                            <div class="flex justify-between items-center">
                                <p class="text-sm <?php echo !$notif['is_read'] ? 'font-bold text-black' : 'text-gray-700'; ?>">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                                <span class="text-xs text-gray-400"><?php echo format_datetime($notif['created_at']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Orders -->
        <div class="card" style="padding: 2rem;">
            <h2 class="ct-section-title">Recent Orders</h2>

            <?php if (empty($recent_orders)): ?>
                <div class="ct-empty">
                    <div class="ct-empty-icon">📦</div>
                    <p>You haven't placed any orders yet</p>
                    <a href="products.php" class="btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-black">
                                <th class="text-left py-3">Order #</th>
                                <th class="text-left py-3">Date</th>
                                <th class="text-left py-3">Amount</th>
                                <th class="text-left py-3">Payment</th>
                                <th class="text-left py-3">Status</th>
                                <th class="text-left py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr class="border-b transition-colors hover:bg-gray-50">
                                    <td class="py-3 font-bold">#<?php echo $order['order_id']; ?></td>
                                    <td class="py-3"><?php echo format_date($order['order_date']); ?></td>
                                    <td class="py-3 font-black" style="color:#000;"><?php echo format_currency($order['total_amount']); ?></td>
                                    <td class="py-3"><?php echo status_badge($order['payment_status'], 'payment'); ?></td>
                                    <td class="py-3"><?php echo status_badge($order['status'], 'order'); ?></td>
                                    <td class="py-3">
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="font-bold border-b border-black">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 text-center">
                    <a href="orders.php" class="btn-primary">View All Orders</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
