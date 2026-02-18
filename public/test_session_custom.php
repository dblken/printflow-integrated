<?php
// Custom session path test
$path = __DIR__ . '/../sessions';
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}
session_save_path($path);
session_start();

$count = $_SESSION['count'] ?? 0;
$count++;
$_SESSION['count'] = $count;

echo "Session Path: " . session_save_path() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Count: " . $count . "<br>";
echo "Refresh this page. If count increases, the fix is to use this directory.<br>";
?>
