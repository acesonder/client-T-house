<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requireLogin();

// Redirect if not staff or volunteer
if ($auth->hasRole('client')) {
    header('Location: /client/dashboard.php');
    exit;
} elseif ($auth->hasRole('admin')) {
    header('Location: /admin/dashboard.php');
    exit;
}

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$settings = $auth->getUserSettings();

// Get staff statistics
$stats = [];
$stats['total_clients'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'client' AND u.status = 'active'"
)['count'] ?? 0;

$stats['pending_assessments'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM intake_assessments WHERE status = 'pending'"
)['count'] ?? 0;

$stats['unread_messages'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0",
    [$user['user_id']]
)['count'] ?? 0;

$stats['active_resources'] = $db->fetchOne(
    "SELECT COUNT(*) as count FROM client_resources WHERE status IN ('available', 'pending')"
)['count'] ?? 0;

// Get recent activity
$recentClients = $db->fetchAll(
    "SELECT u.user_id, u.username, u.first_name, u.last_name, u.created_at
     FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE r.role_name = 'client'
     ORDER BY u.created_at DESC
     LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            👥 Staff Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="/staff/dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="/staff/clients.php">🏠 My Clients</a></li>
            <li><a href="/staff/assessments.php">📋 Assessments</a></li>
            <li><a href="/staff/messages.php">💬 Messages</a></li>
            <li><a href="/staff/resources.php">📚 Resources</a></li>
            <li><a href="/staff/tasks.php">✓ Tasks</a></li>
            <li><a href="/staff/profile.php">⚙️ Profile</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] ?? $user['username']); ?>!</h1>
                <p class="text-muted">Staff & Volunteer Dashboard</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;"><?php echo $stats['total_clients']; ?></h3>
                        <p class="text-muted">Active Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;"><?php echo $stats['pending_assessments']; ?></h3>
                        <p class="text-muted">Pending Assessments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;"><?php echo $stats['unread_messages']; ?></h3>
                        <p class="text-muted">Unread Messages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;"><?php echo $stats['active_resources']; ?></h3>
                        <p class="text-muted">Active Resources</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <div class="d-flex gap-2" style="flex-wrap: wrap;">
                    <a href="/staff/clients.php" class="btn btn-primary">View Clients</a>
                    <a href="/staff/assessments.php" class="btn btn-success">Review Assessments</a>
                    <a href="/staff/messages.php" class="btn btn-info">Check Messages</a>
                    <a href="/staff/resources.php" class="btn btn-warning">Manage Resources</a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Clients -->
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">Recently Registered Clients</div>
                    <div class="card-body">
                        <?php if (empty($recentClients)): ?>
                            <p class="text-muted text-center">No recent clients</p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Registered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentClients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($client['username']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/staff/clients.php">View All Clients →</a>
                    </div>
                </div>
            </div>

            <!-- Important Information -->
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">Important Information</div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Staff Resources:</strong> Access training materials and protocols in the Resources section
                        </div>
                        <div class="alert alert-success">
                            <strong>Communication:</strong> Always use the internal messaging system for client communication
                        </div>
                        <div class="alert alert-warning">
                            <strong>Privacy:</strong> Remember to maintain client confidentiality at all times
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks & Reminders -->
        <div class="card">
            <div class="card-header">Today's Tasks & Reminders</div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>📋 Note:</strong> Task management features will display your assigned tasks and reminders here.
                </div>
                <ul>
                    <li>Review pending client assessments</li>
                    <li>Respond to client messages</li>
                    <li>Update case notes</li>
                    <li>Follow up with service providers</li>
                </ul>
                <a href="/staff/tasks.php" class="btn btn-primary">Manage Tasks</a>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        <?php if ($settings): ?>
        ThemeManager.applyTheme(<?php echo json_encode($settings); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
