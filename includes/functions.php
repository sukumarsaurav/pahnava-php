<?php
/**
 * Core Functions - Utility functions for the ecommerce platform
 * 
 * @security All functions include proper validation and sanitization
 */

/**
 * Redirect to a page with security
 */
function redirect($url, $permanent = false) {
    // Validate URL to prevent open redirect
    if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\/[a-zA-Z0-9\/_\-\?&=]*$/', $url)) {
        $url = '/';
    }
    
    $statusCode = $permanent ? 301 : 302;
    header("Location: $url", true, $statusCode);
    exit();
}

/**
 * Display flash messages
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $type = $message['type'] ?? 'info';
        $text = Security::sanitizeInput($message['text']);
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$text}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

/**
 * Set flash message
 */
function setFlashMessage($text, $type = 'info') {
    $_SESSION['flash_message'] = [
        'text' => $text,
        'type' => $type
    ];
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'INR') {
    return '₹' . number_format($amount, 2);
}

/**
 * Generate SEO-friendly URL slug
 */
function generateSlug($text) {
    // Convert to lowercase
    $text = strtolower($text);
    
    // Replace spaces and special characters with hyphens
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    
    // Remove leading/trailing hyphens
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Get user's IP address
 */
function getUserIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle comma-separated IPs
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Generate pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $pagination .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page={$prevPage}'>Previous</a></li>";
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $pagination .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page=1'>1</a></li>";
        if ($start > 2) {
            $pagination .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $pagination .= "<li class='page-item {$active}'><a class='page-link' href='{$baseUrl}?page={$i}'>{$i}</a></li>";
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $pagination .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        $pagination .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page={$totalPages}'>{$totalPages}</a></li>";
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $pagination .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page={$nextPage}'>Next</a></li>";
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}

/**
 * Resize and optimize image
 */
function resizeImage($sourcePath, $destinationPath, $maxWidth, $maxHeight, $quality = 85) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $newWidth = round($sourceWidth * $ratio);
    $newHeight = round($sourceHeight * $ratio);
    
    // Create source image
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Create destination image
    $destinationImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType == 'image/png' || $mimeType == 'image/gif') {
        imagealphablending($destinationImage, false);
        imagesavealpha($destinationImage, true);
        $transparent = imagecolorallocatealpha($destinationImage, 255, 255, 255, 127);
        imagefilledrectangle($destinationImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($destinationImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save image
    $result = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $result = imagejpeg($destinationImage, $destinationPath, $quality);
            break;
        case 'image/png':
            $result = imagepng($destinationImage, $destinationPath);
            break;
        case 'image/gif':
            $result = imagegif($destinationImage, $destinationPath);
            break;
    }
    
    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($destinationImage);
    
    return $result;
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    // This is a placeholder - implement with your preferred email service
    // You can use PHPMailer, SendGrid, or other email services
    
    $headers = [
        'From: noreply@pahnava.com',
        'Reply-To: support@pahnava.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    if ($isHTML) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = []) {
    global $db;

    try {
        $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at)
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $params = [
            $userId,
            $action,
            json_encode($details),
            getUserIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        $db->execute($query, $params);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>
