<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Diagnosis V2</h1>";

$auth_path = __DIR__ . '/../includes/auth.php';
if (file_exists($auth_path)) {
    require_once $auth_path;
    echo "auth.php loaded.<br>";
} else {
    echo "auth.php MISSING.<br>";
}

echo "<h3>State</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . " (2=Active)<br>";
echo "Cookies: <pre>" . print_r($_COOKIE, true) . "</pre>";

echo "<h3>Persistence</h3>";
$count = $_SESSION['diag_v2'] ?? 0;
echo "Current Count: <strong>$count</strong><br>";
$_SESSION['diag_v2'] = $count + 1;

echo "<h3>DB Check</h3>";
require_once __DIR__ . '/../includes/db.php';
$res = $conn->query("SELECT COUNT(*) as cnt FROM sessions");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Sessions in DB: " . $row['cnt'] . "<br>";
} else {
    echo "DB Error: " . $conn->error . "<br>";
}

echo "<br><a href='diag.php'>REFRESH</a>";
echo "<br><a href='check_db_sessions.php'>VIEW DB SESSIONS</a>";
?>
