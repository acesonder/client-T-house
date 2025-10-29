<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->hasRole('client')) {
    header('Location: /public/unauthorized.php');
    exit;
}

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$settings = $auth->getUserSettings();

// Handle settings update
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        try {
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            $db->execute(
                "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?",
                [$firstName, $lastName, $email, $phone, $user['user_id']]
            );
            
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            
            // Refresh user data
            $user = $auth->getCurrentUser();
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'update_password') {
        try {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }
            
            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters');
            }
            
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->execute(
                "UPDATE users SET password_hash = ? WHERE user_id = ?",
                [$newHash, $user['user_id']]
            );
            
            $message = 'Password updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'update_theme') {
        try {
            $themeColor = $_POST['theme_color'] ?? '#2563eb';
            $navbarColor = $_POST['navbar_color'] ?? '#1e40af';
            $buttonColor = $_POST['button_color'] ?? '#3b82f6';
            $fontSize = $_POST['font_size'] ?? 'medium';
            $layoutStyle = $_POST['layout_style'] ?? 'comfortable';
            
            // Check if settings exist
            $existingSettings = $db->fetchOne(
                "SELECT * FROM user_settings WHERE user_id = ?",
                [$user['user_id']]
            );
            
            if ($existingSettings) {
                $db->execute(
                    "UPDATE user_settings SET theme_color = ?, navbar_color = ?, button_color = ?, font_size = ?, layout_style = ? WHERE user_id = ?",
                    [$themeColor, $navbarColor, $buttonColor, $fontSize, $layoutStyle, $user['user_id']]
                );
            } else {
                $db->execute(
                    "INSERT INTO user_settings (user_id, theme_color, navbar_color, button_color, font_size, layout_style) VALUES (?, ?, ?, ?, ?, ?)",
                    [$user['user_id'], $themeColor, $navbarColor, $buttonColor, $fontSize, $layoutStyle]
                );
            }
            
            $message = 'Theme settings updated successfully!';
            $messageType = 'success';
            
            // Refresh settings
            $settings = $auth->getUserSettings();
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            🏠 Client Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="/client/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/client/messages.php">💬 Messages</a></li>
            <li><a href="/client/resources.php">📚 Resources</a></li>
            <li><a href="/client/documents.php">📄 Documents</a></li>
            <li><a href="/client/assessment.php">📋 Assessments</a></li>
            <li><a href="/client/profile.php" class="active">⚙️ Profile Settings</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Profile Settings</h1>
                <p class="text-muted">Manage your account and preferences</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> mb-3">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="card mb-3">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Phone</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label>Username</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card mb-3">
            <div class="card-header">Change Password</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_password">
                    <div class="mb-2">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="mb-2">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>

        <!-- Theme Settings -->
        <div class="card mb-3">
            <div class="card-header">Theme & Appearance</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_theme">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>Primary Color</label>
                            <input type="color" name="theme_color" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['theme_color'] ?? '#2563eb'); ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Navbar Color</label>
                            <input type="color" name="navbar_color" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['navbar_color'] ?? '#1e40af'); ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label>Button Color</label>
                            <input type="color" name="button_color" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['button_color'] ?? '#3b82f6'); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Font Size</label>
                            <select name="font_size" class="form-control">
                                <option value="small" <?php echo ($settings['font_size'] ?? 'medium') === 'small' ? 'selected' : ''; ?>>Small</option>
                                <option value="medium" <?php echo ($settings['font_size'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="large" <?php echo ($settings['font_size'] ?? 'medium') === 'large' ? 'selected' : ''; ?>>Large</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Layout Style</label>
                            <select name="layout_style" class="form-control">
                                <option value="compact" <?php echo ($settings['layout_style'] ?? 'comfortable') === 'compact' ? 'selected' : ''; ?>>Compact</option>
                                <option value="comfortable" <?php echo ($settings['layout_style'] ?? 'comfortable') === 'comfortable' ? 'selected' : ''; ?>>Comfortable</option>
                                <option value="spacious" <?php echo ($settings['layout_style'] ?? 'comfortable') === 'spacious' ? 'selected' : ''; ?>>Spacious</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Theme</button>
                    <button type="button" class="btn btn-outline" onclick="resetTheme()">Reset to Default</button>
                </form>
            </div>
        </div>

        <!-- Account Information -->
        <div class="card">
            <div class="card-header">Account Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <p><strong>Account Created:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Last Login:</strong> 
                            <?php echo $user['last_login'] ? date('F d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </p>
                    </div>
                    <div class="col-md-6 mb-2">
                        <p><strong>Account Status:</strong> 
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars(ucwords($user['status'])); ?>
                            </span>
                        </p>
                        <p><strong>User ID:</strong> <?php echo htmlspecialchars($user['user_id']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        <?php if ($settings): ?>
        ThemeManager.applyTheme(<?php echo json_encode($settings); ?>);
        <?php endif; ?>

        function resetTheme() {
            if (confirm('Reset theme to default settings?')) {
                document.querySelector('input[name="theme_color"]').value = '#2563eb';
                document.querySelector('input[name="navbar_color"]').value = '#1e40af';
                document.querySelector('input[name="button_color"]').value = '#3b82f6';
                document.querySelector('select[name="font_size"]').value = 'medium';
                document.querySelector('select[name="layout_style"]').value = 'comfortable';
            }
        }
    </script>
</body>
</html>
