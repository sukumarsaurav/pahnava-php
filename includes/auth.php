<?php
/**
 * Authentication System - User login, registration, and session management
 * 
 * @security Implements secure authentication with rate limiting and session protection
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register new user
     */
    public function register($userData) {
        // Validate input
        $errors = $this->validateRegistrationData($userData);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check rate limiting
        if (!Security::checkRateLimit('register', 3, 3600)) {
            return ['success' => false, 'errors' => ['Too many registration attempts. Please try again later.']];
        }
        
        // Check if email already exists
        if ($this->emailExists($userData['email'])) {
            return ['success' => false, 'errors' => ['Email address already registered']];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Hash password
            $hashedPassword = Security::hashPassword($userData['password']);
            
            // Generate verification token
            $verificationToken = Security::generateToken();
            
            // Insert user
            $query = "INSERT INTO users (first_name, last_name, email, password, phone, verification_token, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $userData['first_name'],
                $userData['last_name'],
                $userData['email'],
                $hashedPassword,
                $userData['phone'],
                $verificationToken
            ];
            
            $this->db->execute($query, $params);
            $userId = $this->db->lastInsertId();
            
            // Create user profile
            $profileQuery = "INSERT INTO user_profiles (user_id, created_at) VALUES (?, NOW())";
            $this->db->execute($profileQuery, [$userId]);
            
            $this->db->commit();
            
            // Send verification email
            $this->sendVerificationEmail($userData['email'], $verificationToken);
            
            // Log activity
            logActivity($userId, 'user_registered', ['email' => $userData['email']]);
            
            return ['success' => true, 'message' => 'Registration successful. Please check your email for verification.'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Registration failed: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $rememberMe = false) {
        // Check rate limiting
        if (!Security::checkRateLimit('login', 5, 900)) {
            Security::logSecurityEvent('login_rate_limit_exceeded', ['email' => $email]);
            return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
        }
        
        // Get user by email
        $user = $this->getUserByEmail($email);
        
        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            Security::logSecurityEvent('login_failed', ['email' => $email]);
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
        
        // Check if account is verified
        if (!$user['is_verified']) {
            return ['success' => false, 'error' => 'Please verify your email address before logging in'];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Your account has been deactivated'];
        }
        
        // Create session
        $this->createUserSession($user, $rememberMe);
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Log activity
        logActivity($user['id'], 'user_login', ['email' => $email]);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'user_logout');
        }
        
        // Destroy session
        session_destroy();
        
        // Clear remember me cookie if exists
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->getUserById($_SESSION['user_id']);
    }
    
    /**
     * Verify email address
     */
    public function verifyEmail($token) {
        $query = "UPDATE users SET is_verified = 1, verification_token = NULL, verified_at = NOW() 
                  WHERE verification_token = ? AND is_verified = 0";
        
        $stmt = $this->db->execute($query, [$token]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Email verified successfully'];
        }
        
        return ['success' => false, 'error' => 'Invalid or expired verification token'];
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            // Don't reveal if email exists
            return ['success' => true, 'message' => 'If the email exists, a reset link has been sent'];
        }
        
        // Generate reset token
        $resetToken = Security::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $query = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?";
        $this->db->execute($query, [$resetToken, $expiresAt, $user['id']]);
        
        // Send reset email
        $this->sendPasswordResetEmail($email, $resetToken);
        
        return ['success' => true, 'message' => 'Password reset link has been sent to your email'];
    }
    
    /**
     * Reset password
     */
    public function resetPassword($token, $newPassword) {
        // Validate password
        if (!Security::validatePassword($newPassword)) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters with uppercase, lowercase, and number'];
        }
        
        // Check token validity
        $query = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()";
        $user = $this->db->fetchRow($query, [$token]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }
        
        // Update password
        $hashedPassword = Security::hashPassword($newPassword);
        $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
        $this->db->execute($updateQuery, [$hashedPassword, $user['id']]);
        
        // Log activity
        logActivity($user['id'], 'password_reset');
        
        return ['success' => true, 'message' => 'Password reset successfully'];
    }



    /**
     * Validate registration data
     */
    private function validateRegistrationData($data) {
        $errors = [];
        
        if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
            $errors[] = 'First name must be at least 2 characters';
        }
        
        if (empty($data['last_name']) || strlen($data['last_name']) < 2) {
            $errors[] = 'Last name must be at least 2 characters';
        }
        
        if (!Security::validateEmail($data['email'])) {
            $errors[] = 'Please enter a valid email address';
        }
        
        if (!Security::validatePassword($data['password'])) {
            $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and number';
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        if (!empty($data['phone']) && !Security::validatePhone($data['phone'])) {
            $errors[] = 'Please enter a valid phone number';
        }
        
        return $errors;
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        $query = "SELECT id FROM users WHERE email = ?";
        $result = $this->db->fetchRow($query, [$email]);
        return !empty($result);
    }
    
    /**
     * Get user by email
     */
    private function getUserByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ?";
        return $this->db->fetchRow($query, [$email]);
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetchRow($query, [$id]);
    }
    
    /**
     * Create user session
     */
    private function createUserSession($user, $rememberMe = false) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['logged_in_at'] = time();
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $rememberToken = Security::generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Store token in database
            $query = "UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?";
            $this->db->execute($query, [$rememberToken, $expiresAt, $user['id']]);
            
            // Set cookie
            setcookie('remember_token', $rememberToken, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        }
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->db->execute($query, [$userId]);
    }
    
    /**
     * Send verification email
     */
    private function sendVerificationEmail($email, $token) {
        $verificationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/verify-email.php?token=" . $token;
        
        $subject = "Verify Your Email - Pahnava";
        $body = "
        <h2>Welcome to Pahnava!</h2>
        <p>Please click the link below to verify your email address:</p>
        <p><a href='{$verificationUrl}'>Verify Email Address</a></p>
        <p>If you didn't create an account, please ignore this email.</p>
        ";
        
        sendEmail($email, $subject, $body, true);
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $token) {
        $resetUrl = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
        
        $subject = "Password Reset - Pahnava";
        $body = "
        <h2>Password Reset Request</h2>
        <p>Click the link below to reset your password:</p>
        <p><a href='{$resetUrl}'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you didn't request this reset, please ignore this email.</p>
        ";
        
        sendEmail($email, $subject, $body, true);
    }
}

// Initialize authentication
$auth = new Auth();
?>
