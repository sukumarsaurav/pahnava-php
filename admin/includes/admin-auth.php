<?php
/**
 * Admin Authentication Class
 * 
 * @security Handles admin login, logout, and session management
 */

class AdminAuth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Admin login
     */
    public function login($username, $password, $rememberMe = false) {
        // Rate limiting
        if (!Security::checkRateLimit('admin_login', 5, 900)) {
            return [
                'success' => false,
                'error' => 'Too many login attempts. Please try again in 15 minutes.'
            ];
        }
        
        // Get admin user
        $query = "SELECT * FROM admin_users WHERE username = ? AND is_active = 1";
        $admin = $this->db->fetchRow($query, [$username]);
        
        if (!$admin || !password_verify($password, $admin['password'])) {
            // Log failed attempt
            Security::logSecurityEvent('admin_login_failed', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'error' => 'Invalid username or password.'
            ];
        }
        
        // Check if account is locked
        if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            return [
                'success' => false,
                'error' => 'Account is temporarily locked. Please try again later.'
            ];
        }
        
        // Successful login
        $this->createSession($admin, $rememberMe);
        
        // Update last login
        $query = "UPDATE admin_users SET 
                  last_login = NOW(), 
                  failed_attempts = 0, 
                  locked_until = NULL 
                  WHERE id = ?";
        $this->db->execute($query, [$admin['id']]);
        
        // Log successful login
        Security::logSecurityEvent('admin_login_success', [
            'admin_id' => $admin['id'],
            'username' => $admin['username']
        ]);
        
        return [
            'success' => true,
            'message' => 'Login successful'
        ];
    }
    
    /**
     * Create admin session
     */
    private function createSession($admin, $rememberMe = false) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $query = "INSERT INTO admin_remember_tokens (admin_id, token, expires_at) VALUES (?, ?, ?)";
            $this->db->execute($query, [$admin['id'], hash('sha256', $token), date('Y-m-d H:i:s', $expires)]);
            
            // Set cookie
            setcookie('admin_remember', $token, $expires, '/admin/', '', true, true);
        }
    }
    
    /**
     * Check if admin is logged in
     */
    public function isLoggedIn() {
        // Check session
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            // Check session timeout (2 hours)
            if (time() - $_SESSION['admin_login_time'] > 7200) {
                $this->logout();
                return false;
            }
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['admin_remember'])) {
            return $this->validateRememberToken($_COOKIE['admin_remember']);
        }
        
        return false;
    }
    
    /**
     * Validate remember me token
     */
    private function validateRememberToken($token) {
        $hashedToken = hash('sha256', $token);
        
        $query = "SELECT art.*, au.* FROM admin_remember_tokens art
                  JOIN admin_users au ON art.admin_id = au.id
                  WHERE art.token = ? AND art.expires_at > NOW() AND au.is_active = 1";
        
        $result = $this->db->fetchRow($query, [$hashedToken]);
        
        if ($result) {
            // Create new session
            $this->createSession($result);
            return true;
        }
        
        // Invalid token, remove cookie
        setcookie('admin_remember', '', time() - 3600, '/admin/');
        return false;
    }
    
    /**
     * Admin logout
     */
    public function logout() {
        // Remove remember token if exists
        if (isset($_SESSION['admin_id'])) {
            $query = "DELETE FROM admin_remember_tokens WHERE admin_id = ?";
            $this->db->execute($query, [$_SESSION['admin_id']]);
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Remove remember me cookie
        setcookie('admin_remember', '', time() - 3600, '/admin/');
        
        // Start new session
        session_start();
    }
    
    /**
     * Get current admin user
     */
    public function getCurrentAdmin() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $query = "SELECT * FROM admin_users WHERE id = ?";
        return $this->db->fetchRow($query, [$_SESSION['admin_id']]);
    }
    
    /**
     * Check admin permission
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $admin = $this->getCurrentAdmin();
        
        // Super admin has all permissions
        if ($admin['role'] === 'super_admin') {
            return true;
        }
        
        // Check specific permissions based on role
        $rolePermissions = [
            'admin' => [
                'view_dashboard', 'manage_products', 'manage_categories', 'manage_orders',
                'manage_customers', 'view_reports', 'manage_coupons', 'manage_brands',
                'manage_reviews', 'manage_inventory'
            ],
            'manager' => [
                'view_dashboard', 'manage_products', 'manage_categories', 'manage_orders',
                'view_reports', 'manage_inventory'
            ],
            'staff' => [
                'view_dashboard', 'manage_orders', 'manage_inventory'
            ]
        ];
        
        return in_array($permission, $rolePermissions[$admin['role']] ?? []);
    }
    
    /**
     * Update admin password
     */
    public function updatePassword($currentPassword, $newPassword) {
        $admin = $this->getCurrentAdmin();
        
        if (!password_verify($currentPassword, $admin['password'])) {
            return [
                'success' => false,
                'error' => 'Current password is incorrect.'
            ];
        }
        
        if (!Security::validatePassword($newPassword)) {
            return [
                'success' => false,
                'error' => 'Password must be at least 8 characters with uppercase, lowercase, and number.'
            ];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        $query = "UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?";
        $this->db->execute($query, [$hashedPassword, $admin['id']]);
        
        // Log password change
        Security::logSecurityEvent('admin_password_changed', [
            'admin_id' => $admin['id']
        ]);
        
        return [
            'success' => true,
            'message' => 'Password updated successfully.'
        ];
    }
}
?>
