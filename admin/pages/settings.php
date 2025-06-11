<?php
/**
 * Admin Settings Page
 * 
 * @security Admin authentication and permissions required
 */

// Check permissions
if (!$adminAuth->hasPermission('manage_settings')) {
    setAdminFlashMessage('You do not have permission to access this page.', 'danger');
    redirect('admin/');
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!validateAdminCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $tab = Security::sanitizeInput($_POST['tab'] ?? 'general');
        
        try {
            $db->beginTransaction();
            
            switch ($tab) {
                case 'general':
                    $settings = [
                        'site_name' => Security::sanitizeInput($_POST['site_name'] ?? ''),
                        'site_description' => Security::sanitizeInput($_POST['site_description'] ?? ''),
                        'site_keywords' => Security::sanitizeInput($_POST['site_keywords'] ?? ''),
                        'contact_email' => Security::sanitizeInput($_POST['contact_email'] ?? ''),
                        'contact_phone' => Security::sanitizeInput($_POST['contact_phone'] ?? ''),
                        'contact_address' => Security::sanitizeInput($_POST['contact_address'] ?? ''),
                        'currency' => Security::sanitizeInput($_POST['currency'] ?? 'INR'),
                        'timezone' => Security::sanitizeInput($_POST['timezone'] ?? 'Asia/Kolkata'),
                        'date_format' => Security::sanitizeInput($_POST['date_format'] ?? 'Y-m-d'),
                        'time_format' => Security::sanitizeInput($_POST['time_format'] ?? 'H:i:s')
                    ];
                    break;
                    
                case 'email':
                    $settings = [
                        'smtp_host' => Security::sanitizeInput($_POST['smtp_host'] ?? ''),
                        'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
                        'smtp_username' => Security::sanitizeInput($_POST['smtp_username'] ?? ''),
                        'smtp_password' => $_POST['smtp_password'] ?? '',
                        'smtp_encryption' => Security::sanitizeInput($_POST['smtp_encryption'] ?? 'tls'),
                        'from_email' => Security::sanitizeInput($_POST['from_email'] ?? ''),
                        'from_name' => Security::sanitizeInput($_POST['from_name'] ?? '')
                    ];
                    break;
                    
                case 'payment':
                    $settings = [
                        'razorpay_key_id' => Security::sanitizeInput($_POST['razorpay_key_id'] ?? ''),
                        'razorpay_key_secret' => $_POST['razorpay_key_secret'] ?? '',
                        'razorpay_webhook_secret' => $_POST['razorpay_webhook_secret'] ?? '',
                        'payment_methods' => json_encode($_POST['payment_methods'] ?? []),
                        'cod_enabled' => isset($_POST['cod_enabled']) ? 1 : 0,
                        'cod_fee' => (float)($_POST['cod_fee'] ?? 0)
                    ];
                    break;
                    
                case 'shipping':
                    $settings = [
                        'free_shipping_threshold' => (float)($_POST['free_shipping_threshold'] ?? 999),
                        'default_shipping_cost' => (float)($_POST['default_shipping_cost'] ?? 50),
                        'shipping_calculation' => Security::sanitizeInput($_POST['shipping_calculation'] ?? 'flat_rate'),
                        'weight_based_shipping' => isset($_POST['weight_based_shipping']) ? 1 : 0,
                        'shipping_zones' => json_encode($_POST['shipping_zones'] ?? [])
                    ];
                    break;
                    
                case 'tax':
                    $settings = [
                        'tax_enabled' => isset($_POST['tax_enabled']) ? 1 : 0,
                        'tax_rate' => (float)($_POST['tax_rate'] ?? 18),
                        'tax_inclusive' => isset($_POST['tax_inclusive']) ? 1 : 0,
                        'tax_display' => Security::sanitizeInput($_POST['tax_display'] ?? 'inclusive')
                    ];
                    break;
                    
                case 'seo':
                    $settings = [
                        'meta_title' => Security::sanitizeInput($_POST['meta_title'] ?? ''),
                        'meta_description' => Security::sanitizeInput($_POST['meta_description'] ?? ''),
                        'meta_keywords' => Security::sanitizeInput($_POST['meta_keywords'] ?? ''),
                        'google_analytics_id' => Security::sanitizeInput($_POST['google_analytics_id'] ?? ''),
                        'facebook_pixel_id' => Security::sanitizeInput($_POST['facebook_pixel_id'] ?? ''),
                        'robots_txt' => Security::sanitizeInput($_POST['robots_txt'] ?? ''),
                        'sitemap_enabled' => isset($_POST['sitemap_enabled']) ? 1 : 0
                    ];
                    break;
                    
                case 'social':
                    $settings = [
                        'facebook_url' => Security::sanitizeInput($_POST['facebook_url'] ?? ''),
                        'twitter_url' => Security::sanitizeInput($_POST['twitter_url'] ?? ''),
                        'instagram_url' => Security::sanitizeInput($_POST['instagram_url'] ?? ''),
                        'youtube_url' => Security::sanitizeInput($_POST['youtube_url'] ?? ''),
                        'linkedin_url' => Security::sanitizeInput($_POST['linkedin_url'] ?? ''),
                        'social_login_enabled' => isset($_POST['social_login_enabled']) ? 1 : 0,
                        'google_client_id' => Security::sanitizeInput($_POST['google_client_id'] ?? ''),
                        'google_client_secret' => $_POST['google_client_secret'] ?? '',
                        'facebook_app_id' => Security::sanitizeInput($_POST['facebook_app_id'] ?? ''),
                        'facebook_app_secret' => $_POST['facebook_app_secret'] ?? ''
                    ];
                    break;
            }
            
            // Update settings
            foreach ($settings as $key => $value) {
                $query = "INSERT INTO settings (setting_key, setting_value, updated_at) 
                          VALUES (?, ?, NOW()) 
                          ON DUPLICATE KEY UPDATE 
                          setting_value = VALUES(setting_value), 
                          updated_at = VALUES(updated_at)";
                $db->execute($query, [$key, $value]);
            }
            
            $db->commit();
            
            // Log activity
            logAdminActivity('settings_updated', [
                'tab' => $tab,
                'settings_count' => count($settings)
            ]);
            
            setAdminFlashMessage('Settings updated successfully!', 'success');
            redirect("admin/?page=settings&tab=$tab");
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Settings update failed: " . $e->getMessage());
            $errors[] = 'Failed to update settings. Please try again.';
        }
    }
}

// Get current settings
$settingsQuery = "SELECT setting_key, setting_value FROM settings";
$settingsResult = $db->fetchAll($settingsQuery);
$currentSettings = [];
foreach ($settingsResult as $setting) {
    $currentSettings[$setting['setting_key']] = $setting['setting_value'];
}

// Get active tab
$activeTab = Security::sanitizeInput($_GET['tab'] ?? 'general');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Settings</h1>
            <p class="text-muted">Configure your store settings and preferences</p>
        </div>
    </div>
    
    <!-- Display Errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Settings Tabs -->
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div class="nav flex-column nav-pills" id="settingsTab" role="tablist">
                        <button class="nav-link <?php echo $activeTab === 'general' ? 'active' : ''; ?>" 
                                onclick="switchTab('general')">
                            <i class="fas fa-cog me-2"></i>General
                        </button>
                        <button class="nav-link <?php echo $activeTab === 'email' ? 'active' : ''; ?>" 
                                onclick="switchTab('email')">
                            <i class="fas fa-envelope me-2"></i>Email
                        </button>
                        <button class="nav-link <?php echo $activeTab === 'payment' ? 'active' : ''; ?>" 
                                onclick="switchTab('payment')">
                            <i class="fas fa-credit-card me-2"></i>Payment
                        </button>
                        <button class="nav-link <?php echo $activeTab === 'shipping' ? 'active' : ''; ?>" 
                                onclick="switchTab('shipping')">
                            <i class="fas fa-shipping-fast me-2"></i>Shipping
                        </button>
                        <button class="nav-link <?php echo $activeTab === 'tax' ? 'active' : ''; ?>" 
                                onclick="switchTab('tax')">
                            <i class="fas fa-calculator me-2"></i>Tax
                        </button>
                        <button class="nav-link <?php echo $activeTab === 'seo' ? 'active' : ''; ?>" 
                                onclick="switchTab('seo')">
                            <i class="fas fa-search me-2"></i>SEO
                        </button>
                        <button class="nav-link <?php echo $activeTab === 'social' ? 'active' : ''; ?>" 
                                onclick="switchTab('social')">
                            <i class="fas fa-share-alt me-2"></i>Social
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="tab-content" id="settingsTabContent">
                <!-- General Settings -->
                <div class="tab-pane fade <?php echo $activeTab === 'general' ? 'show active' : ''; ?>" id="general">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">General Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                                <input type="hidden" name="tab" value="general">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Site Name</label>
                                        <input type="text" class="form-control" name="site_name" 
                                               value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? 'Pahnava'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Contact Email</label>
                                        <input type="email" class="form-control" name="contact_email" 
                                               value="<?php echo htmlspecialchars($currentSettings['contact_email'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Site Description</label>
                                    <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars($currentSettings['site_description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Site Keywords</label>
                                    <input type="text" class="form-control" name="site_keywords" 
                                           value="<?php echo htmlspecialchars($currentSettings['site_keywords'] ?? ''); ?>"
                                           placeholder="fashion, clothing, ecommerce">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Contact Phone</label>
                                        <input type="text" class="form-control" name="contact_phone" 
                                               value="<?php echo htmlspecialchars($currentSettings['contact_phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Currency</label>
                                        <select class="form-select" name="currency">
                                            <option value="INR" <?php echo ($currentSettings['currency'] ?? 'INR') === 'INR' ? 'selected' : ''; ?>>Indian Rupee (₹)</option>
                                            <option value="USD" <?php echo ($currentSettings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                            <option value="EUR" <?php echo ($currentSettings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Contact Address</label>
                                    <textarea class="form-control" name="contact_address" rows="3"><?php echo htmlspecialchars($currentSettings['contact_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Timezone</label>
                                        <select class="form-select" name="timezone">
                                            <option value="Asia/Kolkata" <?php echo ($currentSettings['timezone'] ?? 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata</option>
                                            <option value="UTC" <?php echo ($currentSettings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo ($currentSettings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date Format</label>
                                        <select class="form-select" name="date_format">
                                            <option value="Y-m-d" <?php echo ($currentSettings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                            <option value="d/m/Y" <?php echo ($currentSettings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                            <option value="m/d/Y" <?php echo ($currentSettings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Save General Settings</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Email Settings -->
                <div class="tab-pane fade <?php echo $activeTab === 'email' ? 'show active' : ''; ?>" id="email">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Email Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                                <input type="hidden" name="tab" value="email">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" name="smtp_host" 
                                               value="<?php echo htmlspecialchars($currentSettings['smtp_host'] ?? ''); ?>"
                                               placeholder="smtp.gmail.com">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="smtp_port" 
                                               value="<?php echo htmlspecialchars($currentSettings['smtp_port'] ?? '587'); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" name="smtp_username" 
                                               value="<?php echo htmlspecialchars($currentSettings['smtp_username'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" name="smtp_password" 
                                               value="<?php echo htmlspecialchars($currentSettings['smtp_password'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Encryption</label>
                                        <select class="form-select" name="smtp_encryption">
                                            <option value="tls" <?php echo ($currentSettings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo ($currentSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo ($currentSettings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">From Email</label>
                                        <input type="email" class="form-control" name="from_email" 
                                               value="<?php echo htmlspecialchars($currentSettings['from_email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">From Name</label>
                                        <input type="text" class="form-control" name="from_name" 
                                               value="<?php echo htmlspecialchars($currentSettings['from_name'] ?? 'Pahnava'); ?>">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="testEmailSettings()">Test Email</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Settings -->
                <div class="tab-pane fade <?php echo $activeTab === 'payment' ? 'show active' : ''; ?>" id="payment">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::getCSRFToken(); ?>">
                                <input type="hidden" name="tab" value="payment">
                                
                                <h6 class="mb-3">Razorpay Configuration</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Razorpay Key ID</label>
                                        <input type="text" class="form-control" name="razorpay_key_id" 
                                               value="<?php echo htmlspecialchars($currentSettings['razorpay_key_id'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Razorpay Key Secret</label>
                                        <input type="password" class="form-control" name="razorpay_key_secret" 
                                               value="<?php echo htmlspecialchars($currentSettings['razorpay_key_secret'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Webhook Secret</label>
                                    <input type="password" class="form-control" name="razorpay_webhook_secret" 
                                           value="<?php echo htmlspecialchars($currentSettings['razorpay_webhook_secret'] ?? ''); ?>">
                                </div>
                                
                                <hr>
                                
                                <h6 class="mb-3">Cash on Delivery</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cod_enabled" 
                                                   <?php echo ($currentSettings['cod_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Enable Cash on Delivery</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">COD Fee</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" name="cod_fee" 
                                                   value="<?php echo htmlspecialchars($currentSettings['cod_fee'] ?? '0'); ?>" 
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Save Payment Settings</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Add other tab content here... -->
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    window.location.href = `?page=settings&tab=${tab}`;
}

function testEmailSettings() {
    makeAjaxRequest('ajax/test-email.php', {
        method: 'POST',
        body: JSON.stringify({})
    })
    .then(data => {
        if (data.success) {
            showNotification('Test email sent successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to send test email', 'danger');
        }
    });
}
</script>

<?php
// Add page-specific scripts
$pageScripts = [];
?>
