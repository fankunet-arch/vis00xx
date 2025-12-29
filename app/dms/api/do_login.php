<?php
/**
 * DMS Archive System - Login API
 * Process user login credentials
 */

defined('DMS_ENTRY') or exit;

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dms_json_response(false, null, 'Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get input
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    dms_json_response(false, null, 'Username and password are required', 'MISSING_CREDENTIALS', 400);
}

// Attempt login
$result = dms_attempt_login($username, $password);

if ($result['success']) {
    // Redirect to original page or doc_list
    $redirect = $_SESSION['dms_redirect_after_login'] ?? '';
    unset($_SESSION['dms_redirect_after_login']);

    // For AJAX requests, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        dms_json_response(true, [
            'redirect' => $redirect ?: 'index.php?action=doc_list'
        ], 'Login successful');
    }

    // For form submissions, redirect directly
    header('Location: ' . ($redirect ?: 'index.php?action=doc_list'));
    exit;

} else {
    // Login failed
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        dms_json_response(false, null, $result['message'], 'LOGIN_FAILED', 401);
    }

    // For form submissions, redirect back to login with error
    $_SESSION['login_error'] = $result['message'];
    header('Location: index.php?action=login');
    exit;
}
