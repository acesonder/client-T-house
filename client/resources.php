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

// Fetch client resources
$resources = $db->fetchAll(
    "SELECT cr.*, u.username as provided_by_name, u.first_name, u.last_name
     FROM client_resources cr
     LEFT JOIN users u ON cr.provided_by = u.user_id
     WHERE cr.client_id = ?
     ORDER BY cr.created_at DESC",
    [$user['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - <?php echo APP_NAME; ?></title>
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
            <li><a href="/client/resources.php" class="active">📚 Resources</a></li>
            <li><a href="/client/documents.php">📄 Documents</a></li>
            <li><a href="/client/assessment.php">📋 Assessments</a></li>
            <li><a href="/client/profile.php">⚙️ Profile Settings</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>My Resources</h1>
                <p class="text-muted">Access your assigned resources and services</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;"><?php echo count($resources); ?></h3>
                        <p class="text-muted">Total Resources</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;">
                            <?php echo count(array_filter($resources, function($r) { return $r['status'] === 'available'; })); ?>
                        </h3>
                        <p class="text-muted">Available</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;">
                            <?php echo count(array_filter($resources, function($r) { return $r['status'] === 'pending'; })); ?>
                        </h3>
                        <p class="text-muted">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;">
                            <?php echo count(array_filter($resources, function($r) { return $r['priority'] === 'urgent'; })); ?>
                        </h3>
                        <p class="text-muted">Urgent</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <select id="status-filter" class="form-control" onchange="filterResources()">
                            <option value="">All Statuses</option>
                            <option value="available">Available</option>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <select id="type-filter" class="form-control" onchange="filterResources()">
                            <option value="">All Types</option>
                            <option value="housing">Housing</option>
                            <option value="employment">Employment</option>
                            <option value="education">Education</option>
                            <option value="healthcare">Healthcare</option>
                            <option value="legal">Legal</option>
                            <option value="financial">Financial</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <input type="text" id="search-input" class="form-control" placeholder="Search resources..." 
                               onkeyup="filterResources()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Resources Grid -->
        <?php if (empty($resources)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <div style="padding: 3rem 0;">
                        <h3 style="opacity: 0.5;">📚</h3>
                        <p class="text-muted">No resources assigned yet</p>
                        <p class="text-muted">Your case manager will assign resources based on your assessment</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div id="resources-container">
                <?php foreach ($resources as $resource): ?>
                <div class="card mb-3" 
                     data-status="<?php echo htmlspecialchars($resource['status']); ?>"
                     data-type="<?php echo htmlspecialchars($resource['resource_type'] ?? 'other'); ?>"
                     data-search="<?php echo htmlspecialchars(strtolower($resource['title'] . ' ' . $resource['description'])); ?>">
                    <div class="card-body">
                        <div class="d-flex justify-between align-center mb-2">
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($resource['title']); ?></h3>
                            <div>
                                <span class="badge badge-<?php 
                                    echo $resource['status'] === 'available' ? 'success' : 
                                        ($resource['status'] === 'pending' ? 'warning' : 
                                        ($resource['status'] === 'assigned' ? 'info' : 'secondary')); 
                                ?>">
                                    <?php echo htmlspecialchars(ucwords($resource['status'])); ?>
                                </span>
                                <?php if ($resource['priority'] === 'urgent' || $resource['priority'] === 'high'): ?>
                                <span class="badge badge-<?php echo $resource['priority'] === 'urgent' ? 'danger' : 'warning'; ?>">
                                    <?php echo htmlspecialchars(ucwords($resource['priority'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($resource['resource_type']): ?>
                        <div class="mb-2">
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $resource['resource_type']))); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($resource['description']): ?>
                        <p class="mb-2"><?php echo htmlspecialchars($resource['description']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($resource['resource_url']): ?>
                        <div class="mb-2">
                            <a href="<?php echo htmlspecialchars($resource['resource_url']); ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-primary">
                                View Resource
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                            <small class="text-muted">
                                <?php if ($resource['provided_by']): ?>
                                    Provided by: <?php 
                                    $name = trim(($resource['first_name'] ?? '') . ' ' . ($resource['last_name'] ?? ''));
                                    echo htmlspecialchars($name ?: $resource['provided_by_name']);
                                    ?> • 
                                <?php endif; ?>
                                Added: <?php echo date('M d, Y', strtotime($resource['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Help Information -->
        <div class="card">
            <div class="card-header">Need Help?</div>
            <div class="card-body">
                <p>If you need additional resources or have questions about any of the resources listed above, please:</p>
                <ul>
                    <li>Contact your case manager through the <a href="/client/messages.php">Messages</a> page</li>
                    <li>Update your needs in the <a href="/client/assessment.php">Assessment</a> section</li>
                    <li>Call our 24/7 support line: 1-800-SHELTER</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        <?php if ($settings): ?>
        ThemeManager.applyTheme(<?php echo json_encode($settings); ?>);
        <?php endif; ?>

        function filterResources() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const typeFilter = document.getElementById('type-filter').value;
            const cards = document.querySelectorAll('#resources-container .card');
            
            cards.forEach(card => {
                const searchData = card.getAttribute('data-search');
                const status = card.getAttribute('data-status');
                const type = card.getAttribute('data-type');
                
                const matchesSearch = searchData.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesType = !typeFilter || type === typeFilter;
                
                card.style.display = (matchesSearch && matchesStatus && matchesType) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
