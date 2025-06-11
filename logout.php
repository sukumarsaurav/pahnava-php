<?php
/**
 * Logout Handler
 * Securely logs out user and cleans up session
 * 
 * @security Secure session destruction and cleanup
 */

session_start();

// Include required files
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Initialize security
Security::init();

// Perform logout
$auth->logout();

// Set success message
setFlashMessage('You have been successfully logged out.', 'success');

// Redirect to home page
redirect('/');
?>
