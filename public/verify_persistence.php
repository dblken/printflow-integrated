<?php
require_once __DIR__ . '/../includes/auth.php';

echo "<h1>Verification</h1>";

// 1. Check Session Cookie Params
$params = session_get_cookie_params();
echo "<h2>Session Cookie Configuration</h2>";
echo "Lifetime: " . $params['lifetime'] . " (Expected: 2592000 for 30 days)<br>";
if ($params['lifetime'] == 2592000) {
    echo "<span style='color:green'>✓ Session persistence verified!</span><br>";
} else {
    echo "<span style='color:red'>✗ Session persistence mismatch.</span><br>";
}

// 2. Test Order Cancellation Logic
echo "<h2>Cancellation Logic Test</h2>";
$res = db_query("SELECT order_id FROM orders WHERE status = 'Pending' LIMIT 1");
if (!empty($res)) {
    $oid = $res[0]['order_id'];
    echo "Found pending order #$oid for test.<br>";
    // We won't actually cancel it here to avoid messing up real data, 
    // but we can check if the columns exist.
    $cols = db_query("SHOW COLUMNS FROM orders WHERE Field IN ('cancellation_reason', 'cancellation_details')");
    if (count($cols) == 2) {
        echo "<span style='color:green'>✓ Database columns verified!</span><br>";
    } else {
        echo "<span style='color:red'>✗ Database columns missing.</span><br>";
    }
} else {
    echo "No pending orders found to verify.<br>";
}
