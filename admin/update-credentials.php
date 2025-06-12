<?php
/**
 * One-Time Admin Credentials Update
 * 
 * @security This file should be deleted after use
 * @warning Only use this for initial setup or emergency access
 */

session_start();

// Security: Check if this file should be disabled
$lockFile = 'credentials-updated.lock';
if (file_exists($lockFile)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - Pahnava Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">🔒 Access Denied</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <strong>This credentials update tool has been disabled.</strong>
                            </div>
                            <p>The admin credentials have already been updated and this tool has been locked for security.</p>
                            <p><strong>To use this tool again:</strong></p>
                            <ol>
                                <li>Delete the file: <code>admin/credentials-updated.lock</code></li>
                                <li>Refresh this page</li>
                            </ol>
                            <div class="mt-4">
                                <a href="index.php" class="btn btn-primary">Go to Admin Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Include required files
require_once '../config/database.php';
require_once '../includes/security.php';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $currentUsername = trim($_POST['current_username'] ?? '');
    $newUsername = trim($_POST['new_username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $newEmail = trim($_POST['new_email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $securityCode = $_POST['security_code'] ?? '';
    
    // Security code validation (simple protection)
    $expectedCode = date('Ymd'); // Today's date as security code
    if ($securityCode !== $expectedCode) {
        $errors[] = 'Invalid security code. Use today\'s date in YYYYMMDD format.';
    }
    
    // Validate input
    if (empty($currentUsername)) {
        $errors[] = 'Current username is required.';
    }
    
    if (empty($newUsername)) {
        $errors[] = 'New username is required.';
    } elseif (strlen($newUsername) < 3) {
        $errors[] = 'New username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $newUsername)) {
        $errors[] = 'New username can only contain letters, numbers, and underscores.';
    }
    
    if (empty($newPassword)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }
    
    if (empty($newEmail)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if (empty($firstName)) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Last name is required.';
    }
    
    // Check if current admin exists
    if (empty($errors)) {
        try {
            $currentAdminQuery = "SELECT * FROM admin_users WHERE username = ?";
            $currentAdmin = $db->fetchRow($currentAdminQuery, [$currentUsername]);
            
            if (!$currentAdmin) {
                $errors[] = 'Current admin user not found.';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Check if new username already exists (if different from current)
    if (empty($errors) && $newUsername !== $currentUsername) {
        try {
            $existingUserQuery = "SELECT id FROM admin_users WHERE username = ? AND id != ?";
            $existingUser = $db->fetchRow($existingUserQuery, [$newUsername, $currentAdmin['id']]);
            
            if ($existingUser) {
                $errors[] = 'New username already exists.';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Update admin credentials if no errors
    if (empty($errors)) {
        try {
            // Check if admin_users table exists
            $tableCheck = $db->fetchRow("SHOW TABLES LIKE 'admin_users'");
            if (!$tableCheck) {
                $errors[] = 'Admin users table does not exist. Please run setup first.';
            } else {
                $db->beginTransaction();

                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]);

                // Update admin user
                $updateQuery = "UPDATE admin_users SET
                               username = ?,
                               email = ?,
                               password = ?,
                               first_name = ?,
                               last_name = ?,
                               failed_attempts = 0,
                               locked_until = NULL,
                               updated_at = NOW()
                               WHERE id = ?";

                $stmt = $db->execute($updateQuery, [
                    $newUsername,
                    $newEmail,
                    $hashedPassword,
                    $firstName,
                    $lastName,
                    $currentAdmin['id']
                ]);

                $rowsAffected = $db->rowCount();

                if ($rowsAffected > 0) {
                    $db->commit();

                    // Create lock file to prevent reuse
                    file_put_contents($lockFile, date('Y-m-d H:i:s') . " - Credentials updated successfully\n");

                    $success = 'Admin credentials updated successfully!';

                    // Log the update
                    error_log("Admin credentials updated: $currentUsername -> $newUsername at " . date('Y-m-d H:i:s'));
                } else {
                    $db->rollback();
                    $errors[] = 'No rows were updated. Please check if the current username exists.';
                }
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Credentials update error: " . $e->getMessage());
            $errors[] = 'Failed to update credentials: ' . $e->getMessage();
        }
    }
}

// Get current admin info for display
$currentAdmins = [];
try {
    $adminsQuery = "SELECT username, email, first_name, last_name, role FROM admin_users ORDER BY id";
    $currentAdmins = $db->fetchAll($adminsQuery);
} catch (Exception $e) {
    // Ignore error for display purposes
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Admin Credentials - Pahnava</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .security-warning {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .credentials-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Security Warning -->
                <div class="security-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h3>⚠️ SECURITY WARNING</h3>
                    <p class="mb-0">This is a one-time use tool for updating admin credentials.</p>
                    <p class="mb-0"><strong>DELETE THIS FILE AFTER USE!</strong></p>
                </div>
                
                <!-- Current Admins Info -->
                <?php if (!empty($currentAdmins)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Current Admin Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentAdmins as $admin): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($admin['role']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Update Form -->
                <div class="credentials-form">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-cog fa-3x text-primary mb-3"></i>
                        <h2>Update Admin Credentials</h2>
                        <p class="text-muted">Change username, password, and profile information</p>
                    </div>
                    
                    <!-- Display Errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display Success -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></h6>
                            <hr>
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-success">
                                    <i class="fas fa-sign-in-alt me-2"></i>Go to Admin Login
                                </a>
                                <button class="btn btn-danger" onclick="deleteThisFile()">
                                    <i class="fas fa-trash me-2"></i>Delete This File (Recommended)
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                    
                    <!-- Update Form -->
                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Security Code -->
                        <div class="mb-3">
                            <label for="security_code" class="form-label">
                                <i class="fas fa-shield-alt me-2"></i>Security Code *
                            </label>
                            <input type="text" class="form-control" id="security_code" name="security_code" 
                                   placeholder="Enter today's date (YYYYMMDD)" required>
                            <div class="form-text">
                                Enter today's date in YYYYMMDD format (e.g., <?php echo date('Ymd'); ?>)
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Current Username -->
                        <div class="mb-3">
                            <label for="current_username" class="form-label">Current Username *</label>
                            <input type="text" class="form-control" id="current_username" name="current_username" 
                                   value="<?php echo isset($_POST['current_username']) ? htmlspecialchars($_POST['current_username']) : 'admin'; ?>" 
                                   required>
                        </div>
                        
                        <!-- New Username -->
                        <div class="mb-3">
                            <label for="new_username" class="form-label">New Username *</label>
                            <input type="text" class="form-control" id="new_username" name="new_username" 
                                   value="<?php echo isset($_POST['new_username']) ? htmlspecialchars($_POST['new_username']) : ''; ?>" 
                                   pattern="[a-zA-Z0-9_]+" minlength="3" required>
                            <div class="form-text">3+ characters, letters, numbers, and underscores only</div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="new_email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="new_email" name="new_email" 
                                   value="<?php echo isset($_POST['new_email']) ? htmlspecialchars($_POST['new_email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <!-- Name -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <!-- Password -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimum 8 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="8" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Update Admin Credentials
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Admin Login
                            </a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                
                <!-- Instructions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Important Instructions</h6>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>Security Code:</strong> Enter today's date in YYYYMMDD format</li>
                            <li><strong>Username:</strong> Choose a unique, secure username</li>
                            <li><strong>Password:</strong> Use a strong password with at least 8 characters</li>
                            <li><strong>After Update:</strong> Delete this file immediately for security</li>
                            <li><strong>File Location:</strong> <code>admin/update-credentials.php</code></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function deleteThisFile() {
            if (confirm('Are you sure you want to delete this credentials update file?\n\nThis action cannot be undone and you will not be able to use this tool again unless you re-upload the file.')) {
                alert('Please manually delete the file: admin/update-credentials.php\n\nThis cannot be done automatically for security reasons.');
            }
        }
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                const forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
