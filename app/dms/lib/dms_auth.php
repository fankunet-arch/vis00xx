<?php
/**
 * DMS Archive System - Authentication & Authorization Functions
 * All functions prefixed with 'dms_'
 */

defined('DMS_ENTRY') or exit;

/**
 * Check if user is logged in
 * @return bool
 */
function dms_is_logged_in(): bool {
    return !empty($_SESSION['dms_user_id']) && !empty($_SESSION['dms_username']);
}

/**
 * Get current user ID
 * @return int|null
 */
function dms_get_current_user_id(): ?int {
    return $_SESSION['dms_user_id'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function dms_get_current_user(): ?array {
    if (!dms_is_logged_in()) {
        return null;
    }

    // Check if cached in session
    if (!empty($_SESSION['dms_user_data'])) {
        return $_SESSION['dms_user_data'];
    }

    // Load from database
    $user = dms_db_get_user_by_id($_SESSION['dms_user_id']);
    if ($user) {
        $_SESSION['dms_user_data'] = $user;
        return $user;
    }

    return null;
}

/**
 * Get current user role
 * @return string|null (admin, editor, viewer)
 */
function dms_get_current_role(): ?string {
    $user = dms_get_current_user();
    return $user['role'] ?? null;
}

/**
 * Check if current user has specific role
 * @param string|array $roles
 * @return bool
 */
function dms_has_role($roles): bool {
    $current_role = dms_get_current_role();
    if (!$current_role) {
        return false;
    }

    $roles = (array)$roles;
    return in_array($current_role, $roles, true);
}

/**
 * Check if current user can perform action
 * @param string $permission (upload, edit, delete, manage_categories)
 * @return bool
 */
function dms_can(string $permission): bool {
    $role = dms_get_current_role();
    if (!$role) {
        return false;
    }

    // Admin can do everything
    if ($role === 'admin') {
        return true;
    }

    // Editor permissions
    if ($role === 'editor') {
        return in_array($permission, ['upload', 'edit', 'download', 'preview'], true);
    }

    // Viewer permissions
    if ($role === 'viewer') {
        return in_array($permission, ['download', 'preview'], true);
    }

    return false;
}

/**
 * Require user to be logged in (redirect to login if not)
 */
function dms_require_login(): void {
    if (!dms_is_logged_in()) {
        $_SESSION['dms_redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        dms_redirect('login');
    }
}

/**
 * Require specific role (403 if not authorized)
 * @param string|array $roles
 */
function dms_require_role($roles): void {
    dms_require_login();

    if (!dms_has_role($roles)) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>You do not have permission to access this resource.</p>');
    }
}

/**
 * Require specific permission (403 if not authorized)
 * @param string $permission
 */
function dms_require_permission(string $permission): void {
    dms_require_login();

    if (!dms_can($permission)) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>You do not have permission to perform this action.</p>');
    }
}

/**
 * Attempt to log in user
 * @param string $username
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function dms_attempt_login(string $username, string $password): array {
    // Get user
    $user = dms_db_get_user_by_username($username);

    if (!$user) {
        // Log failed attempt
        dms_db_audit_log(null, 'login_failed', 'user', null, null, null, [
            'username' => $username,
            'reason' => 'user_not_found'
        ]);

        return [
            'success' => false,
            'message' => 'Invalid username or password',
            'user' => null
        ];
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Log failed attempt
        dms_db_audit_log($user['user_id'], 'login_failed', 'user', $user['user_id'], null, null, [
            'reason' => 'invalid_password'
        ]);

        return [
            'success' => false,
            'message' => 'Invalid username or password',
            'user' => null
        ];
    }

    // Check if account is active
    if (empty($user['is_active'])) {
        dms_db_audit_log($user['user_id'], 'login_failed', 'user', $user['user_id'], null, null, [
            'reason' => 'account_inactive'
        ]);

        return [
            'success' => false,
            'message' => 'Account is inactive',
            'user' => null
        ];
    }

    // Success - create session
    session_regenerate_id(true);
    $_SESSION['dms_user_id'] = $user['user_id'];
    $_SESSION['dms_username'] = $user['username'];
    $_SESSION['dms_role'] = $user['role'];
    $_SESSION['dms_user_data'] = $user;

    // Log successful login
    dms_db_audit_log($user['user_id'], 'login', 'user', $user['user_id']);

    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ];
}

/**
 * Log out current user
 */
function dms_logout(): void {
    if (dms_is_logged_in()) {
        $user_id = dms_get_current_user_id();

        // Log logout
        dms_db_audit_log($user_id, 'logout', 'user', $user_id);

        // Clear session
        $_SESSION = [];
        session_destroy();
        session_start();
    }
}

/**
 * Hash password using bcrypt
 * @param string $password
 * @return string
 */
function dms_hash_password(string $password): string {
    global $DMS_CONFIG;
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => $DMS_CONFIG['bcrypt_cost']]);
}

/**
 * Generate CSRF token
 * @return string
 */
function dms_generate_csrf_token(): string {
    if (empty($_SESSION['dms_csrf_token'])) {
        $_SESSION['dms_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['dms_csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function dms_verify_csrf_token(string $token): bool {
    if (empty($_SESSION['dms_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['dms_csrf_token'], $token);
}

/**
 * Require CSRF token (die if invalid)
 */
function dms_require_csrf_token(): void {
    global $DMS_CONFIG;
    $token = $_POST[$DMS_CONFIG['csrf_token_name']] ?? $_GET[$DMS_CONFIG['csrf_token_name']] ?? '';

    if (!dms_verify_csrf_token($token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
}
