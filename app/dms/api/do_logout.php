<?php
/**
 * DMS Archive System - Logout API
 * Terminate user session
 */

defined('DMS_ENTRY') or exit;

// Log out current user
dms_logout();

// Redirect to login page
header('Location: index.php?action=login');
exit;
