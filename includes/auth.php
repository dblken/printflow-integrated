<?php
/**
 * Authentication System
 * PrintFlow - Printing Shop PWA
 *
 * Role redirects: change REDIRECT_BASE if the app is not at /printflow (e.g. on production).
 */

// Base path for redirects (no trailing slash). Change this if app lives at a different path.
if (!defined('AUTH_REDIRECT_BASE')) {
    define('AUTH_REDIRECT_BASE', '/printflow');
}

// Persistent login cookie (Remember Me): name, lifetime in seconds, path
if (!defined('AUTH_REMEMBER_COOKIE')) {
    define('AUTH_REMEMBER_COOKIE', 'printflow_remember');
}
if (!defined('AUTH_REMEMBER_DAYS')) {
    define('AUTH_REMEMBER_DAYS', 30);
}

// Include DB connection first
require_once __DIR__ . '/db.php';

/**
 * Custom Database Session Handler
 */
class DbSessionHandler implements SessionHandlerInterface {
    private $link;

    public function __construct($link) {
        $this->link = $link;
    }

    private function get_link() {
        if (!$this->link || !@$this->link->ping()) {
            $this->link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($this->link->connect_error) {
                $this->log("NEW LINK ERROR: " . $this->link->connect_error);
                return null;
            }
            $this->link->set_charset("utf8mb4");
        }
        return $this->link;
    }

    private function log($message) {
        $log_file = 'C:/xampp/tmp/session_debug.txt';
        $entry = date('Y-m-d H:i:s') . " - " . $message . "\n";
        @file_put_contents($log_file, $entry, FILE_APPEND);
        // Also try standard error log
        error_log("SESSION_DEBUG: " . $message);
    }

    public function open($savePath, $sessionName): bool {
        $this->log("OPEN: $savePath, $sessionName");
        return true;
    }

    public function close(): bool {
        $this->log("CLOSE");
        return true;
    }

    public function read($id): string|false {
        $this->log("READ: $id");
        $link = $this->get_link();
        if (!$link) return '';

        $stmt = $link->prepare("SELECT data FROM sessions WHERE id = ?");
        if (!$stmt) {
            $this->log("READ PREPARE ERROR: " . $link->error);
            return '';
        }
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $record = $result->fetch_assoc();
                $this->log("READ FOUND DATA");
                return $record['data'];
            } else {
                $this->log("READ NO DATA FOUND");
            }
        } else {
            $this->log("READ EXECUTE ERROR: " . $stmt->error);
        }
        return '';
    }

    public function write($id, $data): bool {
        $this->log("WRITE: $id, Data Len: " . strlen($data));
        $link = $this->get_link();
        if (!$link) return false;

        $access = time();
        $stmt = $link->prepare("REPLACE INTO sessions (id, access, data) VALUES (?, ?, ?)");
        if (!$stmt) {
            $this->log("WRITE PREPARE ERROR: " . $link->error);
            return false;
        }
        $stmt->bind_param("sis", $id, $access, $data);
        $res = $stmt->execute();
        if ($res) {
            $this->log("WRITE SUCCESS");
        } else {
            $this->log("WRITE ERROR: " . $stmt->error);
        }
        $stmt->close();
        return $res;
    }

    public function destroy($id): bool {
        $this->log("DESTROY: $id");
        $link = $this->get_link();
        if (!$link) return false;

        $stmt = $link->prepare("DELETE FROM sessions WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("s", $id);
            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }
        return false;
    }

    public function gc($maxlifetime): int|false {
        $this->log("GC");
        $link = $this->get_link();
        if (!$link) return false;

        $old = time() - $maxlifetime;
        $stmt = $link->prepare("DELETE FROM sessions WHERE access < ?");
        if ($stmt) {
            $stmt->bind_param("i", $old);
            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                $stmt->close();
                return $affected;
            }
        }
        return false;
    }
}

// Ensure the database connection isn't closed before session is written
register_shutdown_function('session_write_close');

// Set custom session handler
$handler = new DbSessionHandler($conn);
session_set_save_handler($handler, true);

// Set session lifetime to 30 days (persistent login)
$session_lifetime = 86400 * 30; // 30 days
ini_set('session.gc_maxlifetime', $session_lifetime);

// Ensure session cookie is sent for all paths and persists after browser close
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Persistent login: if no session but remember cookie exists, validate token and restore session
if (!isset($_SESSION['user_id']) && isset($_COOKIE[AUTH_REMEMBER_COOKIE])) {
    $token = $_COOKIE[AUTH_REMEMBER_COOKIE];
    $restored = auth_restore_session_from_token($token);
    if (!$restored) {
        auth_delete_token_by_value($token);
        auth_clear_remember_cookie();
    }
}

// After logout, session can be empty but browser may still send old session id. Regenerate so login form gets a fresh session.
if (!isset($_SESSION['user_id']) && empty($_SESSION['csrf_token'])) {
    session_regenerate_id(true);
}

// db.php already included above

// Try to include functions.php
$functions_path = __DIR__ . '/functions.php';
if (file_exists($functions_path)) {
    require_once $functions_path;
}

// Fallback: Define log_activity if it still doesn't exist to prevent fatal error
if (!function_exists('log_activity')) {
    function log_activity($user_id, $action, $details = '') {
        // Silently fail if function is missing, but don't crash the app
        error_log("Warning: log_activity function missing. Action: $action");
        return false;
    }
}

/**
 * Ensure auth_tokens table exists (create if missing). Called automatically before token use.
 */
function auth_ensure_tokens_table() {
    static $done = false;
    if ($done) return;
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS auth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('Admin','Staff','Customer') NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_token (token_hash),
        KEY idx_user (user_id, user_type),
        KEY idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn && $conn->query($sql)) {
        $done = true;
    }
}

/**
 * Persistent login: create token, store in DB, set cookie. Call after successful login.
 * @param int $user_id
 * @param string $user_type Admin|Staff|Customer
 */
function auth_set_persistent_login($user_id, $user_type) {
    $token = auth_create_persistent_token($user_id, $user_type);
    if ($token) {
        auth_set_remember_cookie($token);
    }
}

/**
 * Generate token, store hash in DB. Returns raw token string for cookie.
 * @return string|null
 */
function auth_create_persistent_token($user_id, $user_type) {
    auth_ensure_tokens_table();
    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $expires = time() + (AUTH_REMEMBER_DAYS * 86400);
    $sql = "INSERT INTO auth_tokens (user_id, user_type, token_hash, expires_at) VALUES (?, ?, ?, ?)";
    $ok = db_execute($sql, 'issi', [$user_id, $user_type, $hash, $expires]);
    return $ok ? $raw : null;
}

/**
 * Set remember-me cookie (HTTPOnly, long expiry, path /)
 */
function auth_set_remember_cookie($token) {
    $expires = time() + (AUTH_REMEMBER_DAYS * 86400);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(AUTH_REMEMBER_COOKIE, $token, $expires, '/', '', $secure, true);
}

/**
 * Clear remember-me cookie
 */
function auth_clear_remember_cookie() {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(AUTH_REMEMBER_COOKIE, '', time() - 3600, '/', '', $secure, true);
}

/**
 * Delete token from DB by raw cookie value (hash it first)
 */
function auth_delete_token_by_value($token) {
    if (strlen($token) < 32) return;
    auth_ensure_tokens_table();
    $hash = hash('sha256', $token);
    db_execute("DELETE FROM auth_tokens WHERE token_hash = ?", 's', [$hash]);
}

/**
 * Validate token and restore session. Returns true if session was restored.
 * Does not restore if user is deactivated.
 */
function auth_restore_session_from_token($token) {
    if (strlen($token) < 32) return false;
    auth_ensure_tokens_table();
    $hash = hash('sha256', $token);
    $rows = db_query("SELECT * FROM auth_tokens WHERE token_hash = ? AND expires_at > ?", 'si', [$hash, time()]);
    if (empty($rows)) return false;
    $row = $rows[0];
    $user_id = (int) $row['user_id'];
    $user_type = $row['user_type'];

    // Check account status: do not restore if deactivated
    if ($user_type === 'Customer') {
        $users = db_query("SELECT customer_id, first_name, last_name, email, status FROM customers WHERE customer_id = ?", 'i', [$user_id]);
        if (empty($users) || ($users[0]['status'] ?? '') !== 'Activated') return false;
        $u = $users[0];
        $_SESSION['user_id'] = (int) $u['customer_id'];
        $_SESSION['user_type'] = 'Customer';
        $_SESSION['user_name'] = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        $_SESSION['user_email'] = $u['email'] ?? '';
    } else {
        $users = db_query("SELECT user_id, first_name, last_name, email, role, status FROM users WHERE user_id = ?", 'i', [$user_id]);
        if (empty($users)) return false;
        $u = $users[0];
        $status = $u['status'] ?? '';
        if ($status !== 'Activated' && $status !== 'Pending') return false;
        $_SESSION['user_id'] = (int) $u['user_id'];
        $_SESSION['user_type'] = $u['role'];
        $_SESSION['user_name'] = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        $_SESSION['user_email'] = $u['email'] ?? '';
        $_SESSION['user_status'] = $status;
        $_SESSION['branch_id'] = (int)($u['branch_id'] ?? 1);
    }
    return true;
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Check if user is Admin
 * @return bool
 */
function is_admin() {
    return is_logged_in() && $_SESSION['user_type'] === 'Admin';
}

/**
 * Check if user is Staff
 * @return bool
 */
function is_staff() {
    return is_logged_in() && $_SESSION['user_type'] === 'Staff';
}

/**
 * Check if user is Customer
 * @return bool
 */
function is_customer() {
    return is_logged_in() && $_SESSION['user_type'] === 'Customer';
}

/**
 * Check if user is Manager
 * Note: This system uses Admin/Staff roles. Manager is not a separate role,
 * so this always returns false. The admin dashboard uses this to redirect
 * managers to their own panel — Admins bypass this check.
 * @return bool
 */
function is_manager() {
    return is_logged_in() && ($_SESSION['user_type'] === 'Manager');
}

/**
 * Check if the current user has any of the specified roles
 * @param string|array $roles
 * @return bool
 */
function has_role($roles) {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['user_type'], $roles);
}

/**
 * Get current user ID
 * @return int|null
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user type
 * @return string|null
 */
function get_user_type() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Get current logged in user data
 * @return array|null
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $user_id = get_user_id();
    $user_type = get_user_type();
    
    if ($user_type === 'Customer') {
        $result = db_query("SELECT * FROM customers WHERE customer_id = ?", 'i', [$user_id]);
    } else {
        $result = db_query("SELECT * FROM users WHERE user_id = ?", 'i', [$user_id]);
    }
    
    return $result[0] ?? null;
}

/**
 * Login user (Admin/Staff)
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'redirect' => string]
 */
function login_user($email, $password) {
    $result = db_query("SELECT * FROM users WHERE email = ? AND status IN ('Activated', 'Pending')", 's', [$email]);
    
    if (empty($result)) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $result[0];
    
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type'] = $user['role']; // 'Admin' or 'Staff'
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_status'] = $user['status']; // 'Activated' or 'Pending'
    $_SESSION['branch_id'] = (int)($user['branch_id'] ?? 1);

    session_regenerate_id(true); // Prevent session fixation
    auth_set_persistent_login((int) $user['user_id'], $user['role']);
    
    // Determine redirect based on role and status
    if ($user['role'] === 'Admin') {
        $redirect = AUTH_REDIRECT_BASE . '/admin/dashboard.php';
    } elseif ($user['status'] === 'Pending') {
        // Pending staff can only see profile to complete their information
        $redirect = AUTH_REDIRECT_BASE . '/staff/profile.php';
    } else {
        $redirect = AUTH_REDIRECT_BASE . '/staff/dashboard.php';
    }
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect
    ];
}

/**
 * Login customer
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'redirect' => string]
 */
function login_customer($email, $password) {
    $result = db_query("SELECT * FROM customers WHERE email = ? AND status = 'Activated'", 's', [$email]);
    
    if (empty($result)) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $customer = $result[0];
    
    if (!password_verify($password, $customer['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $customer['customer_id'];
    $_SESSION['user_type'] = 'Customer';
    $_SESSION['user_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
    $_SESSION['user_email'] = $customer['email'];

    session_regenerate_id(true); // Prevent session fixation
    auth_set_persistent_login((int) $customer['customer_id'], 'Customer');
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'redirect' => AUTH_REDIRECT_BASE . '/customer/dashboard.php'
    ];
}

/**
 * Login or register customer using Google profile (no password). Finds by email or creates new.
 * @param string $email
 * @param string $first_name
 * @param string $last_name
 * @return array ['success' => bool, 'message' => string, 'redirect' => string]
 */
function login_customer_by_google($email, $first_name, $last_name) {
    $email = trim($email);
    $first_name = trim($first_name) ?: 'User';
    $last_name = trim($last_name) ?: '';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email from Google'];
    }
    $existing = db_query("SELECT * FROM customers WHERE email = ? AND status = 'Activated'", 's', [$email]);
    if (!empty($existing)) {
        $customer = $existing[0];
        $_SESSION['user_id'] = $customer['customer_id'];
        $_SESSION['user_type'] = 'Customer';
        $_SESSION['user_name'] = ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '');
        $_SESSION['user_email'] = $customer['email'];
        session_regenerate_id(true);
        auth_set_persistent_login((int) $customer['customer_id'], 'Customer');
        return ['success' => true, 'message' => 'Login successful', 'redirect' => AUTH_REDIRECT_BASE . '/customer/dashboard.php'];
    }
    $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    $sql = "INSERT INTO customers (first_name, middle_name, last_name, dob, gender, email, contact_number, password_hash, status) VALUES (?, '', ?, NULL, NULL, ?, NULL, ?, 'Activated')";
    $cid = db_execute($sql, 'ssss', [$first_name, $last_name, $email, $password_hash]);
    if (!$cid) {
        return ['success' => false, 'message' => 'Could not create account. Please try again.'];
    }
    $_SESSION['user_id'] = $cid;
    $_SESSION['user_type'] = 'Customer';
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    $_SESSION['user_email'] = $email;
    session_regenerate_id(true);
    auth_set_persistent_login((int) $cid, 'Customer');
    return ['success' => true, 'message' => 'Account created', 'redirect' => AUTH_REDIRECT_BASE . '/customer/dashboard.php'];
}

/**
 * Unified login function (detects user type automatically)
 * @param string $email
 * @param string $password
 * @return array
 */
function login($email, $password) {
    // Try customer login first
    $customer_result = login_customer($email, $password);
    if ($customer_result['success']) {
        return $customer_result;
    }
    
    // Try user (Admin/Staff) login
    $user_result = login_user($email, $password);
    if ($user_result['success']) {
        return $user_result;
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}


/**
 * Register a new customer
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function register_customer($data) {
    // Check if email already exists
    $existing = db_query("SELECT customer_id FROM customers WHERE email = ?", 's', [$data['email']]);
    if (!empty($existing)) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Hash password
    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
    
    // Insert customer (use NULL for empty optional date/strings so MySQL accepts them)
    $dob = isset($data['dob']) && $data['dob'] !== '' ? $data['dob'] : null;
    $sql = "INSERT INTO customers (first_name, middle_name, last_name, dob, gender, email, contact_number, password_hash, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Activated')";
    
    $result = db_execute($sql, 'ssssssss', [
        $data['first_name'],
        $data['middle_name'] ?? '',
        $data['last_name'],
        $dob,
        $data['gender'] ?? '',
        $data['email'],
        $data['contact_number'] ?? '',
        $password_hash
    ]);
    
    if ($result) {
        // Auto-login after registration
        $_SESSION['user_id'] = $result;
        $_SESSION['user_type'] = 'Customer';
        $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
        $_SESSION['user_email'] = $data['email'];
        session_regenerate_id(true);
        auth_set_persistent_login((int) $result, 'Customer');
        return ['success' => true, 'message' => 'Registration successful'];
    }
    
    return ['success' => false, 'message' => 'Registration failed. Please try again.'];
}

/**
 * Require authentication (redirect to login if not logged in)
 */
function require_auth() {
    if (!is_logged_in()) {
        header('Location: ' . AUTH_REDIRECT_BASE . '/');
        exit();
    }
}

/**
 * Require specific role (redirect if user doesn't have the role)
 * @param string|array $roles Allowed roles (e.g., 'Admin' or ['Admin', 'Staff'])
 */
function require_role($roles) {
    require_auth();
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    $user_type = get_user_type();
    
    if (!in_array($user_type, $roles)) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>');
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token HTML input
 * @return string
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
