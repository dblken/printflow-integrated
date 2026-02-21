<?php
/**
 * Souvenirs - Service Order Form
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/service_order_helper.php';
require_role('Customer');
$customer_id = get_user_id();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $souvenir_type = trim($_POST['souvenir_type'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $custom_print = trim($_POST['custom_print'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if (empty($souvenir_type) || $quantity < 1) {
        $error = 'Please fill in Type and Quantity.';
    } elseif ($custom_print === 'Yes' && (!isset($_FILES['design_file']) || $_FILES['design_file']['error'] !== UPLOAD_ERR_OK)) {
        $error = 'Please upload your design for custom print.';
    } else {
        $fields = ['souvenir_type' => $souvenir_type, 'quantity' => $quantity, 'custom_print' => $custom_print ?: 'No', 'notes' => $notes];
        $files = [];
        if ($custom_print === 'Yes' && isset($_FILES['design_file']) && $_FILES['design_file']['error'] === UPLOAD_ERR_OK) {
            $valid = service_order_validate_file($_FILES['design_file']);
            if (!$valid['ok']) { $error = $valid['error']; } else {
                $files[] = ['file' => $_FILES['design_file'], 'prefix' => 'design'];
            }
        }
        if (!$error) {
            $result = service_order_create('Souvenirs', $customer_id, $fields, $files);
            if ($result['success']) { $_SESSION['order_success_id'] = $result['order_id']; redirect(BASE_URL . '/customer/order_success.php?service=souvenirs'); }
            $error = $result['error'] ?: 'Failed to submit order.';
        }
    }
}
$page_title = 'Order Souvenirs - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width: 640px;">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Souvenirs</h1>
        <?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="souvenir_type" class="input-field" required>
                        <option value="Mug">Mug</option><option value="Keychain">Keychain</option><option value="Shirt">Shirt</option><option value="Tote Bag">Tote Bag</option><option value="Pen">Pen</option><option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" min="1" class="input-field" required value="<?php echo (int)($_POST['quantity'] ?? 1); ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Custom Print?</label>
                    <select name="custom_print" id="custom_print" class="input-field">
                        <option value="No">No</option><option value="Yes">Yes</option>
                    </select>
                </div>
                <div class="mb-4" id="design-wrap" style="display:none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Design (JPG, PNG, PDF - max 5MB)</label>
                    <input type="file" name="design_file" accept=".jpg,.jpeg,.png,.pdf" class="input-field">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="input-field"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-primary w-full">Submit Order</button>
            </form>
        </div>
        <p class="mt-4 text-sm text-gray-500 text-center"><a href="<?php echo BASE_URL; ?>/customer/dashboard.php" class="text-indigo-600 hover:underline">← Back to Dashboard</a></p>
    </div>
</div>
<script>
document.getElementById('custom_print').addEventListener('change', function() {
    document.getElementById('design-wrap').style.display = this.value === 'Yes' ? 'block' : 'none';
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
