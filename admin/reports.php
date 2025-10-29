<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Gather statistics for reports
$stats = [
    'total_clients' => $db->fetchOne("SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'client'")['count'] ?? 0,
    'active_clients' => $db->fetchOne("SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'client' AND u.status = 'active'")['count'] ?? 0,
    'total_assessments' => $db->fetchOne("SELECT COUNT(*) as count FROM intake_assessments")['count'] ?? 0,
    'pending_assessments' => $db->fetchOne("SELECT COUNT(*) as count FROM intake_assessments WHERE status = 'pending'")['count'] ?? 0,
    'high_risk_cases' => $db->fetchOne("SELECT COUNT(*) as count FROM intake_assessments WHERE risk_level IN ('high', 'critical')")['count'] ?? 0,
    'total_messages' => $db->fetchOne("SELECT COUNT(*) as count FROM messages")['count'] ?? 0,
    'total_providers' => $db->fetchOne("SELECT COUNT(*) as count FROM service_providers WHERE is_active = 1")['count'] ?? 0,
];

// Get monthly client registration trend (last 6 months)
$monthlyTrend = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
     FROM users u 
     JOIN roles r ON u.role_id = r.role_id 
     WHERE r.role_name = 'client' 
     AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month ASC"
);

// Get assessment status breakdown
$assessmentStatus = $db->fetchAll(
    "SELECT status, COUNT(*) as count 
     FROM intake_assessments 
     GROUP BY status"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo APP_NAME; ?></title>
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
            <li><a href="/admin/reports.php" class="active">📈 Reports</a></li>
            <li><a href="/admin/logs.php">📝 Error Logs</a></li>
            <li><a href="/admin/config.php">⚙️ System Config</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Reports & Analytics</h1>
                <p class="text-muted">System performance and outcome metrics</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;"><?php echo $stats['total_clients']; ?></h3>
                        <p class="text-muted">Total Clients</p>
                        <small class="text-success">
                            <?php echo $stats['active_clients']; ?> active
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;"><?php echo $stats['total_assessments']; ?></h3>
                        <p class="text-muted">Total Assessments</p>
                        <small class="text-warning">
                            <?php echo $stats['pending_assessments']; ?> pending
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;"><?php echo $stats['high_risk_cases']; ?></h3>
                        <p class="text-muted">High Risk Cases</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;"><?php echo $stats['total_providers']; ?></h3>
                        <p class="text-muted">Active Providers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-3">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">Client Registration Trend (6 Months)</div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>New Clients</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthlyTrend as $trend): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($trend['month'] . '-01')); ?></td>
                                        <td>
                                            <div class="d-flex align-center">
                                                <div style="background: #3b82f6; height: 20px; width: <?php echo min(100, $trend['count'] * 10); ?>%; margin-right: 10px;"></div>
                                                <?php echo $trend['count']; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">Assessment Status Breakdown</div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalAssessments = $stats['total_assessments'];
                                    foreach ($assessmentStatus as $status): 
                                        $percentage = $totalAssessments > 0 ? round(($status['count'] / $totalAssessments) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $status['status'] === 'completed' ? 'success' : 
                                                    ($status['status'] === 'pending' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo htmlspecialchars(ucwords($status['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $status['count']; ?></td>
                                        <td>
                                            <div class="d-flex align-center">
                                                <div style="background: #10b981; height: 20px; width: <?php echo $percentage; ?>%; margin-right: 10px;"></div>
                                                <?php echo $percentage; ?>%
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Export Options -->
        <div class="card">
            <div class="card-header">Export Reports</div>
            <div class="card-body">
                <p class="text-muted mb-3">Generate and download custom reports</p>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4>📊 Client Report</h4>
                                <p class="text-muted">Comprehensive client list with demographics</p>
                                <button class="btn btn-primary" onclick="alert('Export functionality would generate CSV/PDF report')">
                                    Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4>📋 Assessment Report</h4>
                                <p class="text-muted">Assessment outcomes and statistics</p>
                                <button class="btn btn-primary" onclick="alert('Export functionality would generate CSV/PDF report')">
                                    Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4>🏢 Provider Report</h4>
                                <p class="text-muted">Service provider directory and capacity</p>
                                <button class="btn btn-primary" onclick="alert('Export functionality would generate CSV/PDF report')">
                                    Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
