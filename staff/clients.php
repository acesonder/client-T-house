<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->hasRole('client')) {
    header('Location: /client/dashboard.php');
    exit;
}

$db = Database::getInstance();
$user = $auth->getCurrentUser();
$settings = $auth->getUserSettings();

// Get all clients
$clients = $db->fetchAll(
    "SELECT u.*, 
     (SELECT COUNT(*) FROM intake_assessments WHERE client_id = u.user_id) as assessment_count,
     (SELECT COUNT(*) FROM messages WHERE recipient_id = u.user_id AND is_read = 0) as unread_messages
     FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE r.role_name = 'client'
     ORDER BY u.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            👥 Staff Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="/staff/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/staff/clients.php" class="active">🏠 My Clients</a></li>
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
                <h1>Client Management</h1>
                <p class="text-muted">View and manage client information</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Search -->
        <div class="card mb-3">
            <div class="card-body">
                <input type="text" id="search-input" class="form-control" placeholder="Search clients..." 
                       onkeyup="filterClients()">
            </div>
        </div>

        <!-- Clients Table -->
        <div class="card">
            <div class="card-header">All Clients (<?php echo count($clients); ?>)</div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table" id="clients-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Assessments</th>
                                <th>Unread Msgs</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr data-search="<?php echo htmlspecialchars(strtolower($client['first_name'] . ' ' . $client['last_name'] . ' ' . $client['username'] . ' ' . $client['email'])); ?>">
                                <td><?php echo htmlspecialchars(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($client['username']); ?></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $client['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($client['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center"><?php echo $client['assessment_count']; ?></td>
                                <td class="text-center">
                                    <?php if ($client['unread_messages'] > 0): ?>
                                        <span class="badge badge-warning"><?php echo $client['unread_messages']; ?></span>
                                    <?php else: ?>
                                        0
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        <?php if ($settings): ?>
        ThemeManager.applyTheme(<?php echo json_encode($settings); ?>);
        <?php endif; ?>

        function filterClients() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#clients-table tbody tr');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                row.style.display = searchData.includes(searchTerm) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
