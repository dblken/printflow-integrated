<?php
/**
 * Design Image Migration Runner
 * PrintFlow - Run once to add LONGBLOB columns
 * Access via: http://localhost/printflow/run_design_migration.php
 * DELETE this file after running!
 */

// Only allow from localhost for security
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Access denied.');
}

require_once __DIR__ . '/includes/db.php';

$results = [];
$errors  = [];

$statements = [
    // order_items: add BLOB columns
    "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS design_image LONGBLOB DEFAULT NULL",
    "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS design_image_mime VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE order_items ADD COLUMN IF NOT EXISTS design_image_name VARCHAR(255) DEFAULT NULL",

    // service_order_files: add BLOB columns, make file_path nullable
    "ALTER TABLE service_order_files ADD COLUMN IF NOT EXISTS file_data LONGBLOB DEFAULT NULL",
    "ALTER TABLE service_order_files ADD COLUMN IF NOT EXISTS mime_type VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE service_order_files MODIFY COLUMN file_path VARCHAR(255) DEFAULT NULL",
];

foreach ($statements as $sql) {
    if ($conn->query($sql)) {
        $results[] = "✅ OK: " . substr($sql, 0, 80);
    } else {
        $errors[] = "❌ FAILED: " . substr($sql, 0, 80) . " — " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PrintFlow - Design Image Migration</title>
    <style>
        body { font-family: monospace; padding: 2rem; background:#f9fafb; }
        h1   { color:#1f2937; }
        .ok  { color:#15803d; background:#f0fdf4; padding:8px 12px; border-radius:6px; margin:4px 0; }
        .err { color:#b91c1c; background:#fef2f2; padding:8px 12px; border-radius:6px; margin:4px 0; }
        .warn{ background:#fef9c3; border:1px solid #fde047; padding:16px; border-radius:8px; margin-top:2rem; }
    </style>
</head>
<body>
<h1>PrintFlow — Design Image DB Migration</h1>

<?php foreach ($results as $r): ?>
    <div class="ok"><?php echo htmlspecialchars($r); ?></div>
<?php endforeach; ?>
<?php foreach ($errors as $e): ?>
    <div class="err"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<?php if (empty($errors)): ?>
    <div class="warn">
        <strong>⚠️ Migration complete.</strong><br>
        <strong>IMPORTANT: Delete this file immediately!</strong><br>
        Remove <code>c:\xampp\htdocs\printflow\run_design_migration.php</code> for security.
    </div>
<?php else: ?>
    <p style="color:#b91c1c;">Some statements failed. Check the errors above.</p>
<?php endif; ?>
</body>
</html>
