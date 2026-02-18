    <?php
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Is Writable: " . (is_writable(session_save_path()) ? 'Yes' : 'No') . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Cookies: " . print_r($_COOKIE, true) . "<br>";
phpinfo(INFO_SESSION);
?>
