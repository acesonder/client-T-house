<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Fetch all assessments with client info
$assessments = $db->fetchAll(
    "SELECT ia.*, 
     u.username, u.first_name, u.last_name, u.email,
     assessor.username as assessor_name
     FROM intake_assessments ia
     LEFT JOIN users u ON ia.client_id = u.user_id
     LEFT JOIN users assessor ON ia.assessor_id = assessor.user_id
     ORDER BY ia.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Management - <?php echo APP_NAME; ?></title>
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
            <li><a href="/admin/assessments.php" class="active">📋 Assessments</a></li>
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
                <h1>Assessment Management</h1>
                <p class="text-muted">Review and manage client assessments</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;"><?php echo count($assessments); ?></h3>
                        <p class="text-muted">Total Assessments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;">
                            <?php echo count(array_filter($assessments, function($a) { return $a['status'] === 'pending'; })); ?>
                        </h3>
                        <p class="text-muted">Pending Review</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;">
                            <?php echo count(array_filter($assessments, function($a) { return $a['status'] === 'completed'; })); ?>
                        </h3>
                        <p class="text-muted">Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;">
                            <?php echo count(array_filter($assessments, function($a) { return $a['risk_level'] === 'critical' || $a['risk_level'] === 'high'; })); ?>
                        </h3>
                        <p class="text-muted">High Risk Cases</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <select id="status-filter" class="form-control" onchange="filterAssessments()">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="reviewed">Reviewed</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <select id="risk-filter" class="form-control" onchange="filterAssessments()">
                            <option value="">All Risk Levels</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <input type="text" id="search-input" class="form-control" placeholder="Search clients..." 
                               onkeyup="filterAssessments()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessments Table -->
        <div class="card">
            <div class="card-header">All Assessments</div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table" id="assessments-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Assessment Date</th>
                                <th>Assessor</th>
                                <th>Risk Level</th>
                                <th>Priority Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assessments as $assessment): ?>
                            <tr data-status="<?php echo htmlspecialchars($assessment['status']); ?>" 
                                data-risk="<?php echo htmlspecialchars($assessment['risk_level']); ?>"
                                data-search="<?php echo htmlspecialchars(strtolower($assessment['first_name'] . ' ' . $assessment['last_name'] . ' ' . $assessment['username'])); ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars(($assessment['first_name'] ?? '') . ' ' . ($assessment['last_name'] ?? '')); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($assessment['username']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($assessment['assessment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($assessment['assessor_name'] ?? 'Self-Assessment'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $assessment['risk_level'] === 'critical' ? 'danger' : 
                                            ($assessment['risk_level'] === 'high' ? 'warning' : 
                                            ($assessment['risk_level'] === 'medium' ? 'info' : 'success')); 
                                    ?>">
                                        <?php echo htmlspecialchars(strtoupper($assessment['risk_level'])); ?>
                                    </span>
                                </td>
                                <td class="text-center"><?php echo $assessment['priority_score']; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $assessment['status'] === 'completed' ? 'success' : 
                                            ($assessment['status'] === 'pending' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo htmlspecialchars($assessment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewAssessment(<?php echo $assessment['assessment_id']; ?>)">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Assessment Detail Modal -->
    <div id="assessment-modal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Assessment Details</h3>
                <button class="modal-close" onclick="UI.hideModal('assessment-modal')">&times;</button>
            </div>
            <div class="modal-body" id="assessment-details">
                <div class="text-center"><div class="spinner"></div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="UI.hideModal('assessment-modal')">Close</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function filterAssessments() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const riskFilter = document.getElementById('risk-filter').value;
            const rows = document.querySelectorAll('#assessments-table tbody tr');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                const risk = row.getAttribute('data-risk');
                
                const matchesSearch = searchData.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesRisk = !riskFilter || risk === riskFilter;
                
                row.style.display = (matchesSearch && matchesStatus && matchesRisk) ? '' : 'none';
            });
        }

        function viewAssessment(assessmentId) {
            UI.showModal('assessment-modal');
            const detailsContainer = document.getElementById('assessment-details');
            detailsContainer.innerHTML = '<div class="text-center"><div class="spinner"></div></div>';
            
            // This would fetch assessment details via API
            setTimeout(() => {
                detailsContainer.innerHTML = `
                    <div class="alert alert-info">
                        <strong>Note:</strong> Full assessment details would be loaded here via API.
                        Assessment ID: ${assessmentId}
                    </div>
                `;
            }, 500);
        }
    </script>
</body>
</html>
