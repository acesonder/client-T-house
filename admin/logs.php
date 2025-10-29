<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Fetch error logs with filters
$severity = $_GET['severity'] ?? '';
$resolved = $_GET['resolved'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

$query = "SELECT el.*, u.username as user_name 
          FROM error_logs el
          LEFT JOIN users u ON el.user_id = u.user_id
          WHERE 1=1";
$params = [];

if ($severity) {
    $query .= " AND el.severity = ?";
    $params[] = $severity;
}

if ($resolved !== '') {
    $query .= " AND el.is_resolved = ?";
    $params[] = $resolved;
}

$query .= " ORDER BY el.created_at DESC LIMIT ?";
$params[] = $limit;

$logs = $db->fetchAll($query, $params);

// Handle mark as resolved
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'resolve') {
        $logId = $_POST['log_id'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        $db->execute(
            "UPDATE error_logs SET is_resolved = 1, resolved_by = ?, resolved_at = NOW(), resolution_notes = ? WHERE log_id = ?",
            [$user['user_id'], $notes, $logId]
        );
        
        header('Location: /admin/logs.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Logs - <?php echo APP_NAME; ?></title>
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
            <li><a href="/admin/logs.php" class="active">📝 Error Logs</a></li>
            <li><a href="/admin/config.php">⚙️ System Config</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Error Logs</h1>
                <p class="text-muted">System error monitoring and troubleshooting</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;"><?php echo count($logs); ?></h3>
                        <p class="text-muted">Total Logs (Showing)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;">
                            <?php echo count(array_filter($logs, function($l) { return $l['severity'] === 'critical'; })); ?>
                        </h3>
                        <p class="text-muted">Critical Errors</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;">
                            <?php echo count(array_filter($logs, function($l) { return !$l['is_resolved']; })); ?>
                        </h3>
                        <p class="text-muted">Unresolved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;">
                            <?php echo count(array_filter($logs, function($l) { return $l['is_resolved']; })); ?>
                        </h3>
                        <p class="text-muted">Resolved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-3 mb-2">
                        <select name="severity" class="form-control">
                            <option value="">All Severities</option>
                            <option value="debug" <?php echo $severity === 'debug' ? 'selected' : ''; ?>>Debug</option>
                            <option value="info" <?php echo $severity === 'info' ? 'selected' : ''; ?>>Info</option>
                            <option value="warning" <?php echo $severity === 'warning' ? 'selected' : ''; ?>>Warning</option>
                            <option value="error" <?php echo $severity === 'error' ? 'selected' : ''; ?>>Error</option>
                            <option value="critical" <?php echo $severity === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="resolved" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="0" <?php echo $resolved === '0' ? 'selected' : ''; ?>>Unresolved</option>
                            <option value="1" <?php echo $resolved === '1' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="limit" class="form-control">
                            <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50 Results</option>
                            <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100 Results</option>
                            <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200 Results</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Error Logs -->
        <div class="card">
            <div class="card-header">Error Log Entries</div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-center text-muted">No error logs found</p>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <div class="mb-3 p-3" style="border-left: 4px solid <?php 
                        echo $log['severity'] === 'critical' ? '#ef4444' : 
                            ($log['severity'] === 'error' ? '#f59e0b' : 
                            ($log['severity'] === 'warning' ? '#eab308' : '#3b82f6')); 
                    ?>; background: #f8fafc; border-radius: 4px;">
                        <div class="d-flex justify-between align-center mb-2">
                            <div>
                                <span class="badge badge-<?php 
                                    echo $log['severity'] === 'critical' ? 'danger' : 
                                        ($log['severity'] === 'error' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo htmlspecialchars(strtoupper($log['severity'])); ?>
                                </span>
                                <span class="badge badge-secondary">
                                    <?php echo htmlspecialchars(strtoupper($log['error_type'])); ?>
                                </span>
                                <?php if ($log['is_resolved']): ?>
                                    <span class="badge badge-success">Resolved</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></small>
                        </div>
                        <div class="mb-2">
                            <strong>Error:</strong> <?php echo htmlspecialchars($log['error_message']); ?>
                        </div>
                        <?php if ($log['file_path']): ?>
                        <div class="mb-1">
                            <small class="text-muted">
                                File: <?php echo htmlspecialchars($log['file_path']); ?>
                                <?php if ($log['line_number']): ?>
                                    (Line <?php echo $log['line_number']; ?>)
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                        <?php if ($log['user_name']): ?>
                        <div class="mb-1">
                            <small class="text-muted">User: <?php echo htmlspecialchars($log['user_name']); ?></small>
                        </div>
                        <?php endif; ?>
                        <?php if ($log['resolution_notes']): ?>
                        <div class="mt-2 p-2" style="background: #e0f2fe; border-radius: 4px;">
                            <strong>Resolution:</strong> <?php echo htmlspecialchars($log['resolution_notes']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!$log['is_resolved']): ?>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-success" onclick="showResolveModal(<?php echo $log['log_id']; ?>)">
                                Mark as Resolved
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Resolve Modal -->
    <div id="resolve-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Mark Error as Resolved</h3>
                <button class="modal-close" onclick="UI.hideModal('resolve-modal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="log_id" id="resolve-log-id">
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Resolution Notes</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Describe how this error was resolved..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="UI.hideModal('resolve-modal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Resolved</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function showResolveModal(logId) {
            document.getElementById('resolve-log-id').value = logId;
            UI.showModal('resolve-modal');
        }
    </script>
</body>
</html>
