<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

// Only clients can access this page
if (!$auth->hasRole('client')) {
    header('Location: /public/unauthorized.php');
    exit;
}

$user = $auth->getCurrentUser();
$settings = $auth->getUserSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            🏠 Client Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="/client/dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="/client/messages.php">💬 Messages</a></li>
            <li><a href="/client/resources.php">📚 Resources</a></li>
            <li><a href="/client/documents.php">📄 Documents</a></li>
            <li><a href="/client/assessment.php">📋 Assessments</a></li>
            <li><a href="/client/profile.php">⚙️ Profile Settings</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
        <div style="margin-top: auto; padding-top: 2rem; opacity: 0.7; font-size: 0.9rem;">
            <p>Logged in as:<br><strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] ?? $user['username']); ?>!</h1>
                <p class="text-muted">Your personal support portal</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;" id="message-count">0</h3>
                        <p class="text-muted">Unread Messages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;" id="resource-count">0</h3>
                        <p class="text-muted">Active Resources</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;" id="document-count">0</h3>
                        <p class="text-muted">Documents</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;" id="assessment-status">Pending</h3>
                        <p class="text-muted">Assessment Status</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">Recent Messages</div>
                    <div class="card-body" id="recent-messages">
                        <div class="text-center">
                            <div class="spinner"></div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/client/messages.php">View All Messages →</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">My Resources</div>
                    <div class="card-body" id="recent-resources">
                        <div class="text-center">
                            <div class="spinner"></div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/client/resources.php">View All Resources →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3>📋</h3>
                        <h4>Complete Assessment</h4>
                        <p>Help us understand your needs better</p>
                        <a href="/client/assessment.php" class="btn btn-primary">Start Assessment</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3>💬</h3>
                        <h4>Contact Case Manager</h4>
                        <p>Get support from your dedicated team</p>
                        <a href="/client/messages.php?action=new" class="btn btn-primary">Send Message</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3>📄</h3>
                        <h4>Upload Documents</h4>
                        <p>Keep your important documents safe</p>
                        <a href="/client/documents.php" class="btn btn-primary">Manage Documents</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Information -->
        <div class="card">
            <div class="card-header">Important Information</div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>24/7 Support:</strong> If you need immediate assistance, call 1-800-SHELTER or text "HELP" to 555-0123
                </div>
                <div class="alert alert-success">
                    <strong>Your Privacy:</strong> All your information is confidential and secure. Only authorized staff can access your records.
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        // Apply user theme
        <?php if ($settings): ?>
        ThemeManager.applyTheme(<?php echo json_encode($settings); ?>);
        <?php endif; ?>

        // Load dashboard data
        document.addEventListener('DOMContentLoaded', function() {
            // Load message count
            Ajax.get('/api/messages.php?action=count_unread', function(response) {
                if (response.success) {
                    document.getElementById('message-count').textContent = response.count || 0;
                }
            });

            // Load recent messages
            Ajax.get('/api/messages.php?action=recent&limit=3', function(response) {
                const container = document.getElementById('recent-messages');
                container.innerHTML = '';
                
                if (response.success && response.messages && response.messages.length > 0) {
                    response.messages.forEach(function(msg) {
                        const div = document.createElement('div');
                        div.className = 'mb-2 p-2';
                        div.style.borderLeft = '3px solid #2563eb';
                        div.style.paddingLeft = '1rem';
                        div.innerHTML = `
                            <strong>${Utils.escapeHtml(msg.subject || 'No Subject')}</strong>
                            <p class="text-muted" style="font-size: 0.9rem; margin: 0;">
                                From: ${Utils.escapeHtml(msg.sender_name || 'Staff')}
                            </p>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p class="text-muted text-center">No messages</p>';
                }
            });

            // Load recent resources
            Ajax.get('/api/resources.php?action=recent&limit=3', function(response) {
                const container = document.getElementById('recent-resources');
                container.innerHTML = '';
                
                if (response.success && response.resources && response.resources.length > 0) {
                    response.resources.forEach(function(res) {
                        const div = document.createElement('div');
                        div.className = 'mb-2 p-2';
                        div.style.borderLeft = '3px solid #10b981';
                        div.style.paddingLeft = '1rem';
                        div.innerHTML = `
                            <strong>${Utils.escapeHtml(res.title)}</strong>
                            <span class="badge badge-${res.status === 'available' ? 'success' : 'warning'}" 
                                  style="float: right;">${Utils.escapeHtml(res.status)}</span>
                            <p class="text-muted" style="font-size: 0.9rem; margin: 0;">
                                ${Utils.escapeHtml(res.resource_type)}
                            </p>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p class="text-muted text-center">No resources assigned</p>';
                }
            });
        });
    </script>
</body>
</html>
