<?php
// Enhanced session test script
session_start();

$current_val = $_SESSION['count'] ?? 0;
echo "Read from session: " . $current_val . "<br>";

$new_val = $current_val + 1;
$_SESSION['count'] = $new_val;
echo "Updated in memory to: " . $new_val . "<br>";

// Force write and check result
$result = session_write_close();
echo "session_write_close() returned: " . ($result ? 'TRUE' : 'FALSE') . "<br>";

// Re-open to verify immediate write
session_start();
echo "Re-read after write: " . ($_SESSION['count'] ?? 'NOT SET') . "<br>";

echo "<a href='test_session.php'>Refresh</a>";
?>
