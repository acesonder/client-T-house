<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requirePermission('all');

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            ⚙️ Admin Panel
        </div>
        <ul class="sidebar-menu">
            <li><a href="/admin/dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="/admin/users.php">👥 Users</a></li>
            <li><a href="/admin/clients.php">🏠 Clients</a></li>
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
                <h1>Admin Dashboard</h1>
                <p class="text-muted">System Overview & Management</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- System Stats -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;" id="total-clients">0</h3>
                        <p class="text-muted">Total Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;" id="active-staff">0</h3>
                        <p class="text-muted">Active Staff</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;" id="pending-assessments">0</h3>
                        <p class="text-muted">Pending Assessments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;" id="critical-cases">0</h3>
                        <p class="text-muted">Critical Cases</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header">Quick Actions</div>
            <div class="card-body">
                <div class="d-flex gap-2" style="flex-wrap: wrap;">
                    <a href="/admin/users.php?action=new" class="btn btn-primary">+ Add User</a>
                    <a href="/admin/clients.php?action=new" class="btn btn-success">+ Add Client</a>
                    <a href="/admin/providers.php?action=new" class="btn btn-info">+ Add Provider</a>
                    <a href="/admin/bulletin.php?action=new" class="btn btn-warning">+ Post Bulletin</a>
                    <a href="/admin/reports.php" class="btn btn-outline">📊 Generate Report</a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Error Logs -->
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">Recent Error Logs</div>
                    <div class="card-body" id="recent-errors">
                        <div class="text-center"><div class="spinner"></div></div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/admin/logs.php">View All Logs →</a>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">System Health</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Database Status:</strong>
                            <span class="badge badge-success" id="db-status">Connected</span>
                        </div>
                        <div class="mb-2">
                            <strong>Error Logging:</strong>
                            <span class="badge badge-success" id="log-status">Active</span>
                        </div>
                        <div class="mb-2">
                            <strong>Session Management:</strong>
                            <span class="badge badge-success" id="session-status">Active</span>
                        </div>
                        <div class="mb-2">
                            <strong>Maintenance Mode:</strong>
                            <span class="badge badge-info" id="maintenance-status">Disabled</span>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/admin/config.php">System Configuration →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">Recent Client Activity</div>
            <div class="card-body">
                <div class="table" style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Activity</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="spinner"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load stats
            Ajax.get('/api/admin-stats.php', function(response) {
                if (response.success) {
                    document.getElementById('total-clients').textContent = response.stats.total_clients || 0;
                    document.getElementById('active-staff').textContent = response.stats.active_staff || 0;
                    document.getElementById('pending-assessments').textContent = response.stats.pending_assessments || 0;
                    document.getElementById('critical-cases').textContent = response.stats.critical_cases || 0;
                }
            });

            // Load recent errors
            Ajax.get('/api/error-logs.php?limit=5', function(response) {
                const container = document.getElementById('recent-errors');
                container.innerHTML = '';
                
                if (response.success && response.logs && response.logs.length > 0) {
                    response.logs.forEach(function(log) {
                        const div = document.createElement('div');
                        div.className = 'mb-2 p-2';
                        div.style.borderLeft = '3px solid ' + (log.severity === 'critical' ? '#ef4444' : '#f59e0b');
                        div.innerHTML = `
                            <strong>[${Utils.escapeHtml(log.severity.toUpperCase())}]</strong> 
                            ${Utils.escapeHtml(log.error_message)}
                            <p class="text-muted" style="font-size: 0.85rem; margin: 0;">
                                ${Utils.formatDate(log.created_at)}
                            </p>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p class="text-muted text-center">No recent errors</p>';
                }
            });
        });
    </script>
</body>
</html>
