<?php
/**
 * Admin Logout Handler
 * 
 * @security Secure logout with session cleanup
 */

session_start();

// Include required files
require_once '../config/database.php';
require_once '../includes/security.php';
require_once 'includes/admin-auth.php';

// Initialize admin auth
$adminAuth = new AdminAuth($db);

// Log logout activity
if (isset($_SESSION['admin_id'])) {
    Security::logSecurityEvent('admin_logout', [
        'admin_id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'unknown'
    ]);
}

// Perform logout
$adminAuth->logout();

// Redirect to login page
header('Location: ?page=login');
exit;
?>
