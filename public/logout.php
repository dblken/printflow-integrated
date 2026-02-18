<?php
/**
 * Logout handler (clean URL: /printflow/logout/). Redirects to home.
 * Destroys session, deletes persistent login token from DB, clears remember cookie.
 */
require_once __DIR__ . '/../includes/auth.php';

// Delete persistent login token and clear cookie (before destroying session)
if (!empty($_COOKIE[AUTH_REMEMBER_COOKIE])) {
    auth_delete_token_by_value($_COOKIE[AUTH_REMEMBER_COOKIE]);
    auth_clear_remember_cookie();
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    $path = isset($params["path"]) && $params["path"] !== '' ? $params["path"] : '/';
    setcookie(session_name(), '', time() - 42000,
        $path,
        $params["domain"] ?? '',
        $params["secure"] ?? false,
        $params["httponly"] ?? true
    );
}
session_destroy();
header("Location: " . (defined('AUTH_REDIRECT_BASE') ? AUTH_REDIRECT_BASE . '/public/' : '/printflow/public/'));
exit();
