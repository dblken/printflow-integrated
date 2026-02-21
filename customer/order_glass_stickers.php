<?php
/**
 * Glass / Wall / Frosted Stickers - Service Order Form
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/service_order_helper.php';
require_role('Customer');
$customer_id = get_user_id();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $width = trim($_POST['width'] ?? ''); $height = trim($_POST['height'] ?? '');
    $installation = trim($_POST['installation'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if (empty($width) || empty($height)) {
        $error = 'Please fill in Width and Height.';
    } elseif (!isset($_FILES['layout_file']) || $_FILES['layout_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload your layout.';
    } else {
        $valid = service_order_validate_file($_FILES['layout_file']);
        if (!$valid['ok']) { $error = $valid['error']; } else {
            $fields = ['width' => $width, 'height' => $height, 'installation_needed' => $installation ?: 'No', 'notes' => $notes];
            $result = service_order_create('Glass / Wall / Frosted Stickers', $customer_id, $fields, [['file' => $_FILES['layout_file'], 'prefix' => 'layout']]);
            if ($result['success']) { $_SESSION['order_success_id'] = $result['order_id']; redirect(BASE_URL . '/customer/order_success.php?service=glass'); }
            $error = $result['error'] ?: 'Failed to submit order.';
        }
    }
}
$page_title = 'Order Glass/Wall/Frosted Stickers - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen py-8">
    <div class="container mx-auto px-4" style="max-width: 640px;">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Glass / Wall / Frosted Stickers</h1>
        <?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Width *</label>
                        <input type="text" name="width" class="input-field" required placeholder="e.g. 24 inches" value="<?php echo htmlspecialchars($_POST['width'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Height *</label>
                        <input type="text" name="height" class="input-field" required placeholder="e.g. 36 inches" value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Installation Needed?</label>
                    <select name="installation" class="input-field">
                        <option value="No">No</option><option value="Yes">Yes</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Layout * (JPG, PNG, PDF - max 5MB)</label>
                    <input type="file" name="layout_file" accept=".jpg,.jpeg,.png,.pdf" class="input-field" required>
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
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
