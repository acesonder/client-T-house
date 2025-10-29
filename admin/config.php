<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Handle config updates
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        try {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'config_') === 0) {
                    $configKey = substr($key, 7); // Remove 'config_' prefix
                    
                    // Check if config exists
                    $existing = $db->fetchOne(
                        "SELECT * FROM system_config WHERE config_key = ?",
                        [$configKey]
                    );
                    
                    if ($existing) {
                        $db->execute(
                            "UPDATE system_config SET config_value = ?, updated_by = ? WHERE config_key = ?",
                            [$value, $user['user_id'], $configKey]
                        );
                    } else {
                        $db->execute(
                            "INSERT INTO system_config (config_key, config_value, updated_by) VALUES (?, ?, ?)",
                            [$configKey, $value, $user['user_id']]
                        );
                    }
                }
            }
            
            $message = 'System configuration updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Fetch all system configs
$configs = $db->fetchAll(
    "SELECT * FROM system_config ORDER BY config_key ASC"
);

// Create config array for easier access
$configMap = [];
foreach ($configs as $config) {
    $configMap[$config['config_key']] = $config['config_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Configuration - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            ⚙️ Admin Panel
        </div>
        <ul class="sidebar-menu">
            <li><a href="/admin/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/admin/users.php">👥 Users</a></li>
            <li><a href="/admin/clients.php">🏠 Clients</a></li>
            <li><a href="/admin/assessments.php">📋 Assessments</a></li>
            <li><a href="/admin/providers.php">🏢 Service Providers</a></li>
            <li><a href="/admin/bulletin.php">📢 Bulletin Board</a></li>
            <li><a href="/admin/reports.php">📈 Reports</a></li>
            <li><a href="/admin/logs.php">📝 Error Logs</a></li>
            <li><a href="/admin/config.php" class="active">⚙️ System Config</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>System Configuration</h1>
                <p class="text-muted">Manage system settings and preferences</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> mb-3">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="update">
            
            <!-- General Settings -->
            <div class="card mb-3">
                <div class="card-header">General Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>System Name</label>
                        <input type="text" name="config_system_name" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['system_name'] ?? APP_NAME); ?>">
                        <small class="text-muted">The name displayed across the application</small>
                    </div>
                    <div class="mb-3">
                        <label>Maintenance Mode</label>
                        <select name="config_maintenance_mode" class="form-control">
                            <option value="0" <?php echo ($configMap['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo ($configMap['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                        <small class="text-muted">When enabled, only admins can access the system</small>
                    </div>
                    <div class="mb-3">
                        <label>Session Timeout (minutes)</label>
                        <input type="number" name="config_session_timeout" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['session_timeout'] ?? '60'); ?>" min="10" max="1440">
                        <small class="text-muted">Automatically log out inactive users after this period</small>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="card mb-3">
                <div class="card-header">Email Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Email From Address</label>
                        <input type="email" name="config_email_from" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['email_from'] ?? 'noreply@shelter.org'); ?>">
                    </div>
                    <div class="mb-3">
                        <label>Email From Name</label>
                        <input type="text" name="config_email_from_name" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['email_from_name'] ?? 'Shelter Management System'); ?>">
                    </div>
                    <div class="mb-3">
                        <label>Enable Email Notifications</label>
                        <select name="config_email_notifications" class="form-control">
                            <option value="0" <?php echo ($configMap['email_notifications'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo ($configMap['email_notifications'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="card mb-3">
                <div class="card-header">Security Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Minimum Password Length</label>
                        <input type="number" name="config_min_password_length" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['min_password_length'] ?? '8'); ?>" min="6" max="32">
                    </div>
                    <div class="mb-3">
                        <label>Max Login Attempts</label>
                        <input type="number" name="config_max_login_attempts" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['max_login_attempts'] ?? '5'); ?>" min="3" max="10">
                        <small class="text-muted">Lock account after this many failed login attempts</small>
                    </div>
                    <div class="mb-3">
                        <label>Require Strong Passwords</label>
                        <select name="config_require_strong_passwords" class="form-control">
                            <option value="0" <?php echo ($configMap['require_strong_passwords'] ?? '1') === '0' ? 'selected' : ''; ?>>No</option>
                            <option value="1" <?php echo ($configMap['require_strong_passwords'] ?? '1') === '1' ? 'selected' : ''; ?>>Yes</option>
                        </select>
                        <small class="text-muted">Require uppercase, lowercase, number, and special character</small>
                    </div>
                </div>
            </div>

            <!-- Assessment Settings -->
            <div class="card mb-3">
                <div class="card-header">Assessment Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Auto-Match Services</label>
                        <select name="config_auto_match_services" class="form-control">
                            <option value="0" <?php echo ($configMap['auto_match_services'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo ($configMap['auto_match_services'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                        <small class="text-muted">Automatically match clients with services based on assessment</small>
                    </div>
                    <div class="mb-3">
                        <label>Assessment Review Period (days)</label>
                        <input type="number" name="config_assessment_review_period" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['assessment_review_period'] ?? '90'); ?>" min="30" max="365">
                        <small class="text-muted">How often assessments should be reviewed and updated</small>
                    </div>
                </div>
            </div>

            <!-- File Upload Settings -->
            <div class="card mb-3">
                <div class="card-header">File Upload Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Max Upload Size (MB)</label>
                        <input type="number" name="config_max_upload_size" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['max_upload_size'] ?? '10'); ?>" min="1" max="50">
                    </div>
                    <div class="mb-3">
                        <label>Allowed File Types</label>
                        <input type="text" name="config_allowed_file_types" class="form-control" 
                               value="<?php echo htmlspecialchars($configMap['allowed_file_types'] ?? 'pdf,jpg,jpeg,png,doc,docx'); ?>">
                        <small class="text-muted">Comma-separated list of allowed file extensions</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Save Configuration</button>
                <a href="/admin/dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>

        <!-- System Information -->
        <div class="card">
            <div class="card-header">System Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                        <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                        <p><strong>Database:</strong> MySQL <?php 
                            try {
                                $version = $db->fetchOne("SELECT VERSION() as version");
                                echo $version['version'] ?? 'Unknown';
                            } catch (Exception $e) {
                                echo 'Unknown';
                            }
                        ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Application Version:</strong> 1.0.0-alpha</p>
                        <p><strong>Last Updated:</strong> <?php 
                            $lastConfig = $db->fetchOne(
                                "SELECT MAX(updated_at) as last_update FROM system_config"
                            );
                            echo $lastConfig['last_update'] ? date('M d, Y H:i:s', strtotime($lastConfig['last_update'])) : 'Never';
                        ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
