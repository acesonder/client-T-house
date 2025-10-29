<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->hasRole('client')) {
    header('Location: /client/dashboard.php');
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
    <title>Tasks - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">👥 Staff Portal</div>
        <ul class="sidebar-menu">
            <li><a href="/staff/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/staff/clients.php">🏠 My Clients</a></li>
            <li><a href="/staff/assessments.php">📋 Assessments</a></li>
            <li><a href="/staff/messages.php">💬 Messages</a></li>
            <li><a href="/staff/resources.php">📚 Resources</a></li>
            <li><a href="/staff/tasks.php" class="active">✓ Tasks</a></li>
            <li><a href="/staff/profile.php">⚙️ Profile</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Task Management</h1>
        <div class="card">
            <div class="card-body">
                <p>Task management features coming soon...</p>
            </div>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
</body>
</html>
