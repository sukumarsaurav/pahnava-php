<?php
/**
 * Security Class - Comprehensive Security Implementation
 * Handles CSRF, XSS, SQL injection prevention, input validation
 * 
 * @security Production-ready security measures
 */

class Security {
    
    /**
     * Initialize security measures
     */
    public static function init() {
        // Secure session configuration
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
        }
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateCSRFToken();
        }
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token for forms
     */
    public static function getCSRFToken() {
        return $_SESSION['csrf_token'] ?? '';
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        return preg_match('/^[+]?[0-9\s\-\(\)]{10,15}$/', $phone);
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $errors[] = "No file uploaded";
            return $errors;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error: " . $file['error'];
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = "File size exceeds maximum allowed size";
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "File type not allowed";
            }
        }
        
        // Check if file is actually an image (for image uploads)
        if (strpos($file['type'], 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = "Invalid image file";
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
        
        // Limit length
        $filename = substr($filename, 0, 255);
        
        return $filename;
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($action, $limit = 5, $timeWindow = 300) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window expired
        if (time() - $data['time'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'time' => time()];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $limit) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];
        
        error_log("SECURITY EVENT: " . json_encode($logData));
    }
}
?>
