<?php
/**
 * Customer Products Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM products WHERE status = 'Activated'";
$params = [];
$types = '';

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

// Pagination settings
$items_per_page = 12;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Count total items for pagination
$count_sql = "SELECT COUNT(*) as total FROM products WHERE status = 'Activated'";
$count_params = [];
$count_types = '';

if (!empty($category)) {
    $count_sql .= " AND category = ?";
    $count_params[] = $category;
    $count_types .= 's';
}

if (!empty($search)) {
    $count_sql .= " AND (name LIKE ? OR description LIKE ?)";
    $count_params[] = '%' . $search . '%';
    $count_params[] = '%' . $search . '%';
    $count_types .= 'ss';
}

$total_result = db_query($count_sql, $count_types, $count_params);
$total_items = $total_result[0]['total'] ?? 0;
$total_pages = ceil($total_items / $items_per_page);

$sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= 'ii';

$products = db_query($sql, $types, $params);

$page_title = 'Products - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width:1100px;">
        <h1 class="ct-page-title">Browse Products</h1>

        <!-- Filters -->
        <div class="ct-filter" style="display:flex; justify-content:space-between; align-items:end; flex-wrap:wrap; gap:1rem;">
            <form method="GET" style="display:flex; gap:1rem; align-items:end; flex-wrap:wrap;">
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.4rem;">Search</label>
                    <input type="text" name="search" class="input-field" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-primary" style="height:fit-content;">Apply</button>
            </form>
            
            <a href="cart.php" class="btn-secondary" style="background:#fff; border:1px solid #d1d5db; padding:0.5rem 1rem; border-radius:6px; display:flex; align-items:center; gap:6px; text-decoration:none; color:#374151;">
                🛒 View Cart <?php echo !empty($_SESSION['cart']) ? '<span style="background:#ef4444; color:white; font-size:0.7rem; padding:1px 6px; border-radius:10px;">'.count($_SESSION['cart']).'</span>' : ''; ?>
            </a>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="ct-empty">
                <div class="ct-empty-icon">📦</div>
                <p>No products found</p>
            </div>
        <?php else: ?>
            <div class="ct-product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="ct-product-card">
                        <div class="ct-product-img">
                            <div class="ct-product-img-inner">
                                <?php 
                                $img_path = "../public/images/products/product_" . $product['product_id'];
                                $display_img = "";
                                if (file_exists($img_path . ".jpg")) {
                                    $display_img = "/printflow/public/images/products/product_" . $product['product_id'] . ".jpg";
                                } elseif (file_exists($img_path . ".png")) {
                                    $display_img = "/printflow/public/images/products/product_" . $product['product_id'] . ".png";
                                }
                                
                                if ($display_img): ?>
                                    <img src="<?php echo $display_img; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:0.5rem;">
                                <?php else: ?>
                                    <span>📦</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ct-product-body" <?php echo ($product['category'] === 'Decals & Stickers') ? 'style="text-align: center;"' : ''; ?>>
                            <?php if ($product['category'] !== 'Decals & Stickers'): ?>
                                <span class="ct-product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                            <?php endif; ?>
                            
                            <h3 class="ct-product-name" <?php echo ($product['category'] === 'Decals & Stickers') ? 'style="margin-bottom: 1.5rem; height: auto; font-weight: 700; font-size: 1.1rem;"' : ''; ?>><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <?php if ($product['category'] !== 'Decals & Stickers'): ?>
                                <p class="ct-product-price"><?php echo format_currency($product['price']); ?></p>
                            <?php endif; ?>

                            <div class="ct-product-actions" <?php echo ($product['category'] === 'Decals & Stickers') ? 'style="display: flex; justify-content: center;"' : ''; ?>>
                                <?php if ($product['category'] !== 'Decals & Stickers'): ?>
                                    <div>
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                            <span class="ct-product-stock in-stock">✓ In Stock</span>
                                        <?php else: ?>
                                            <span class="ct-product-stock out-stock">✕ Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="order_create.php?product_id=<?php echo $product['product_id']; ?>" class="ct-view-product-btn" <?php echo ($product['category'] === 'Decals & Stickers') ? 'style="width: 100%; text-align: center;"' : 'style="flex: 1; text-align: center;"'; ?>>
                                    START CUSTOMIZING
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                <?php echo get_pagination_links($current_page, $total_pages, ['category' => $category, 'search' => $search]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
