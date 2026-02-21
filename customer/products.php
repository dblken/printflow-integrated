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
            <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:1rem;">
                <?php foreach ($products as $product): ?>
                    <div class="ct-product-card floating-card">
                        <div class="ct-product-img">
                            <span>📦</span>
                        </div>
                        <div class="ct-product-body">
                            <h3 class="ct-product-name" style="font-size: 0.9rem;"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="ct-product-desc" style="display: none;"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>

                            <p class="ct-product-price" style="font-size: 1rem; margin-bottom: 0.5rem;"><?php echo format_currency($product['price']); ?></p>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <span class="ct-product-stock in-stock" style="font-size: 0.7rem;">✓ In Stock</span>
                            <?php else: ?>
                                <span class="ct-product-stock out-stock" style="font-size: 0.7rem;">✕ Out of Stock</span>
                            <?php endif; ?>

                            <div class="ct-product-actions" style="margin-top:1rem; display:flex; flex-direction:column; gap:0.5rem;">
                                <a href="order_create.php?product_id=<?php echo $product['product_id']; ?>&buy_now=1" class="btn-primary" style="width:100%; justify-content:center; padding:0.5rem; font-size: 0.8rem;">BUY NOW</a>
                                <div style="display:flex; gap:0.5rem; align-items:center; justify-content:space-between;">
                                    <a href="order_create.php?product_id=<?php echo $product['product_id']; ?>" class="btn-secondary" style="width: 100%; background:#fff; border:1px solid #d1d5db; padding:0.4rem; font-size:0.75rem; border-radius:6px; text-decoration:none; color:#374151; text-align: center;">ADD TO CART</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <style>
                @media (max-width: 1100px) {
                    div[style*="grid-template-columns:repeat(5, 1fr)"] {
                        grid-template-columns: repeat(3, 1fr) !important;
                    }
                }
                @media (max-width: 768px) {
                    div[style*="grid-template-columns:repeat(5, 1fr)"] {
                        grid-template-columns: repeat(2, 1fr) !important;
                    }
                }
                @media (max-width: 480px) {
                    div[style*="grid-template-columns:repeat(5, 1fr)"] {
                        grid-template-columns: repeat(1, 1fr) !important;
                    }
                }
                .floating-card {
                    transition: all 0.3s ease;
                    border: 1px solid #f3f4f6;
                    border-radius: 12px;
                    overflow: hidden;
                    background: white;
                }
                .floating-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                }
            </style>

            <!-- Pagination -->
            <div class="mt-8">
                <?php echo get_pagination_links($current_page, $total_pages, ['category' => $category, 'search' => $search]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
