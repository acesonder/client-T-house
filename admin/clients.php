<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Fetch all clients (users with client role)
$clients = $db->fetchAll(
    "SELECT u.*, r.role_name,
     (SELECT COUNT(*) FROM intake_assessments WHERE client_id = u.user_id) as assessment_count,
     (SELECT COUNT(*) FROM messages WHERE recipient_id = u.user_id AND is_read = 0) as unread_messages,
     (SELECT status FROM intake_assessments WHERE client_id = u.user_id ORDER BY created_at DESC LIMIT 1) as latest_assessment_status
     FROM users u 
     LEFT JOIN roles r ON u.role_id = r.role_id 
     WHERE r.role_name = 'client'
     ORDER BY u.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - <?php echo APP_NAME; ?></title>
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
            <li><a href="/admin/clients.php" class="active">🏠 Clients</a></li>
            <li><a href="/admin/assessments.php">📋 Assessments</a></li>
            <li><a href="/admin/providers.php">🏢 Service Providers</a></li>
            <li><a href="/admin/bulletin.php">📢 Bulletin Board</a></li>
            <li><a href="/admin/reports.php">📈 Reports</a></li>
            <li><a href="/admin/logs.php">📝 Error Logs</a></li>
            <li><a href="/admin/config.php">⚙️ System Config</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Client Management</h1>
                <p class="text-muted">View and manage all clients</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;"><?php echo count($clients); ?></h3>
                        <p class="text-muted">Total Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;">
                            <?php echo count(array_filter($clients, function($c) { return $c['status'] === 'active'; })); ?>
                        </h3>
                        <p class="text-muted">Active Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;">
                            <?php echo count(array_filter($clients, function($c) { return $c['latest_assessment_status'] === 'pending'; })); ?>
                        </h3>
                        <p class="text-muted">Pending Assessments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;">
                            <?php echo array_sum(array_column($clients, 'unread_messages')); ?>
                        </h3>
                        <p class="text-muted">Unread Messages</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" id="search-input" class="form-control" placeholder="Search by name, username, or email..." 
                               onkeyup="filterClients()">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select id="status-filter" class="form-control" onchange="filterClients()">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="/admin/users.php?action=new&role=client" class="btn btn-primary" style="width: 100%;">+ Add Client</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="card">
            <div class="card-header">All Clients</div>
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
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr data-status="<?php echo htmlspecialchars($client['status']); ?>" 
                                data-search="<?php echo htmlspecialchars(strtolower($client['first_name'] . ' ' . $client['last_name'] . ' ' . $client['username'] . ' ' . $client['email'])); ?>">
                                <td><?php echo htmlspecialchars(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($client['username']); ?></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $client['status'] === 'active' ? 'success' : ($client['status'] === 'suspended' ? 'danger' : 'secondary'); ?>">
                                        <?php echo htmlspecialchars($client['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($client['assessment_count'] > 0): ?>
                                        <span class="badge badge-info"><?php echo $client['assessment_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($client['unread_messages'] > 0): ?>
                                        <span class="badge badge-warning"><?php echo $client['unread_messages']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                <td>
                                    <a href="/admin/assessments.php?client_id=<?php echo $client['user_id']; ?>" 
                                       class="btn btn-sm btn-info">View</a>
                                </td>
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
        function filterClients() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const rows = document.querySelectorAll('#clients-table tbody tr');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                
                const matchesSearch = searchData.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
