<?php
/**
 * Admin Products Management Page
 * PrintFlow - Printing Shop PWA  
 * Full CRUD for products
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Admin');

$current_user = get_logged_in_user();

$error = '';
$success = '';

// Handle product creation/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['create_product'])) {
        $name = sanitize($_POST['name'] ?? '');
        $sku = sanitize($_POST['sku'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $status = $_POST['status'] ?? 'Activated';
        $category = $_POST['category'] ?? '';
        
        $result = db_execute("INSERT INTO products (name, sku, description, price, stock_quantity, status, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            'sssdis s', [$name, $sku, $description, $price, $stock_quantity, $status, $category]);
        
        if ($result) {
            $success = 'Product created successfully!';
            log_activity($current_user['user_id'], 'Create Product', "Product: $name (SKU: $sku)");
        } else {
            $error = 'Failed to create product. The SKU might already exist.';
        }
    } elseif (isset($_POST['update_product'])) {
        $product_id = (int)$_POST['product_id'];
        $name = sanitize($_POST['name']);
        $price = (float)$_POST['price'];
        $stock_quantity = (int)$_POST['stock_quantity'];
        $status = $_POST['status'];
        $category = $_POST['category'];
        
        $result = db_execute("UPDATE products SET name = ?, price = ?, stock_quantity = ?, status = ?, category = ?, updated_at = NOW() WHERE product_id = ?",
            'sdis si', [$name, $price, $stock_quantity, $status, $category, $product_id]);
        
        if ($result) {
            $success = 'Product updated successfully!';
            log_activity($current_user['user_id'], 'Update Product', "Product ID: $product_id");
        } else {
            $error = 'Failed to update product.';
        }
    } elseif (isset($_POST['delete_product'])) {
        $product_id = (int)$_POST['product_id'];
        $result = db_execute("UPDATE products SET status = 'Deactivated' WHERE product_id = ?", 'i', [$product_id]);
        
        if ($result) {
            $success = 'Product deactivated successfully!';
            log_activity($current_user['user_id'], 'Deactivate Product', "Product ID: $product_id");
        } else {
            $error = 'Failed to deactivate product.';
        }
    } elseif (isset($_POST['activate_product'])) {
        $product_id = (int)$_POST['product_id'];
        $result = db_execute("UPDATE products SET status = 'Activated' WHERE product_id = ?", 'i', [$product_id]);
        
        if ($result) {
            $success = 'Product activated successfully!';
            log_activity($current_user['user_id'], 'Activate Product', "Product ID: $product_id");
        } else {
            $error = 'Failed to activate product.';
        }
    }
}

// Get all products and split by status
$products = db_query("SELECT * FROM products ORDER BY created_at DESC");
$active_products = array_filter($products, function($p) { return $p['status'] === 'Activated'; });
$deactivated_products = array_filter($products, function($p) { return $p['status'] === 'Deactivated'; });

$page_title = 'Products Management - Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/printflow/public/assets/css/output.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <?php include __DIR__ . '/../includes/admin_style.php'; ?>
</head>
<body>

<div class="dashboard-container" x-data="{ 
    showModal: false, 
    mode: 'create', 
    activeTab: 'active',
    product: {
        product_id: '',
        name: '',
        sku: '',
        price: '',
        stock_quantity: 0,
        description: '',
        status: 'Activated',
        category: ''
    },
    openModal(mode, data = null) {
        this.mode = mode;
        if (mode === 'edit' && data) {
            this.product = { ...data };
        } else {
            this.product = {
                product_id: '',
                name: '',
                sku: '',
                price: '',
                stock_quantity: 0,
                description: '',
                status: 'Activated',
                category: ''
            };
        }
        this.showModal = true;
    }
}">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <h1 class="page-title">Products Management</h1>
            <button 
                @click="openModal('create')"
                class="btn-primary"
            >
                + Add New Product
            </button>
        </header>

        <main>
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6">
                <button 
                    @click="activeTab = 'active'" 
                    class="px-6 py-3 text-sm font-medium transition-colors border-b-2"
                    :class="activeTab === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    Activated Products (<?php echo count($active_products); ?>)
                </button>
                <button 
                    @click="activeTab = 'deactivated'" 
                    class="px-6 py-3 text-sm font-medium transition-colors border-b-2"
                    :class="activeTab === 'deactivated' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >
                    Deactivated Products (<?php echo count($deactivated_products); ?>)
                </button>
            </div>

            <!-- Active Products Table -->
            <div class="card" x-show="activeTab === 'active'">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2">
                                <th class="text-left py-3">ID</th>
                                <th class="text-left py-3">SKU</th>
                                <th class="text-left py-3">Name</th>
                                <th class="text-left py-3">Price</th>
                                <th class="text-left py-3">Stock</th>
                                <th class="text-right py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($active_products)): ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-500 italic">No activated products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($active_products as $product): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3"><?php echo $product['product_id']; ?></td>
                                        <td class="py-3 font-mono text-xs"><?php echo htmlspecialchars($product['sku']); ?></td>
                                        <td class="py-3 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 font-semibold"><?php echo format_currency($product['price']); ?></td>
                                        <td class="py-3">
                                            <?php if ($product['stock_quantity'] < 10): ?>
                                                <span class="text-red-600 font-bold"><?php echo $product['stock_quantity']; ?></span>
                                            <?php else: ?>
                                                <span><?php echo $product['stock_quantity']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-right space-x-2">
                                            <button 
                                                @click="openModal('edit', <?php echo htmlspecialchars(json_encode($product)); ?>)"
                                                class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                                            >
                                                Edit
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Deactivate this product?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <button type="submit" name="delete_product" class="text-red-600 hover:text-red-700 text-sm font-medium">Deactivate</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Deactivated Products Table -->
            <div class="card" x-show="activeTab === 'deactivated'" style="display: none;">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2">
                                <th class="text-left py-3">ID</th>
                                <th class="text-left py-3">SKU</th>
                                <th class="text-left py-3">Name</th>
                                <th class="text-right py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deactivated_products)): ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-500 italic">No deactivated products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deactivated_products as $product): ?>
                                    <tr class="border-b hover:bg-gray-50 text-gray-500">
                                        <td class="py-3"><?php echo $product['product_id']; ?></td>
                                        <td class="py-3 font-mono text-xs"><?php echo htmlspecialchars($product['sku']); ?></td>
                                        <td class="py-3 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-3 text-right space-x-2">
                                            <button 
                                                @click="openModal('edit', <?php echo htmlspecialchars(json_encode($product)); ?>)"
                                                class="text-gray-400 hover:text-indigo-600 text-sm font-medium"
                                            >
                                                Edit
                                            </button>
                                            <form method="POST" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <button type="submit" name="activate_product" class="text-green-600 hover:text-green-700 text-sm font-medium">Activate</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Product Modal -->
    <div x-show="showModal"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100]"
         style="display: none;"
         x-transition
         @keydown.escape.window="showModal = false">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-2xl w-full mx-4" @click.away="showModal = false">
            <h3 class="text-2xl font-bold mb-6" x-text="mode === 'create' ? 'Add New Product' : 'Edit Product'"></h3>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" :name="mode === 'create' ? 'create_product' : 'update_product'" value="1">
                <input type="hidden" name="product_id" x-model="product.product_id">
                
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Product Name *</label>
                        <input type="text" name="name" x-model="product.name" class="input-field" required placeholder="e.g. Glossy Tarpaulin">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">SKU *</label>
                        <input type="text" name="sku" x-model="product.sku" class="input-field" required :readonly="mode === 'edit'" placeholder="e.g. TARP-001">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Price *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-400">₱</span>
                            <input type="number" step="0.01" name="price" x-model="product.price" class="input-field pl-8" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Service Type (Category) *</label>
                        <select name="category" x-model="product.category" class="input-field" required>
                            <option value="">Select Service</option>
                            <option value="Tarpaulin Printing">Tarpaulin Printing</option>
                            <option value="T-Shirt Printing">T-Shirt Printing</option>
                            <option value="Stickers">Stickers (Decals)</option>
                            <option value="Glass/Wall/Frosted">Glass/Wall/Frosted</option>
                            <option value="Transparent Stickers">Transparent Stickers</option>
                            <option value="Layout Service">Layout Service</option>
                            <option value="Reflectorized">Reflectorized</option>
                            <option value="Stickers on Sintraboard">Stickers on Sintraboard</option>
                            <option value="Sintraboard Standees">Sintraboard Standees</option>
                            <option value="Souvenirs">Souvenirs</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-2">Description</label>
                    <textarea name="description" x-model="product.description" rows="3" class="input-field" placeholder="Brief product description..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Stock Quantity *</label>
                        <input type="number" name="stock_quantity" x-model="product.stock_quantity" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Status *</label>
                        <select name="status" x-model="product.status" class="input-field">
                            <option value="Activated">Activated</option>
                            <option value="Deactivated">Deactivated</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1 py-3 text-base" x-text="mode === 'create' ? 'Create Product' : 'Update Product'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
