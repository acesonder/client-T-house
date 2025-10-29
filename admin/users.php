<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Handle user creation/editing
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $roleId = $_POST['role_id'] ?? '';
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            if (empty($username) || empty($email) || empty($password) || empty($roleId)) {
                throw new Exception('All required fields must be filled');
            }
            
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $db->execute(
                "INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, phone, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
                [$username, $email, $passwordHash, $roleId, $firstName, $lastName, $phone]
            );
            
            $message = 'User created successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'update_status') {
        try {
            $userId = $_POST['user_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            $db->execute(
                "UPDATE users SET status = ? WHERE user_id = ?",
                [$status, $userId]
            );
            
            $message = 'User status updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Fetch all users
$users = $db->fetchAll(
    "SELECT u.*, r.role_name 
     FROM users u 
     LEFT JOIN roles r ON u.role_id = r.role_id 
     ORDER BY u.created_at DESC"
);

// Fetch all roles
$roles = $db->fetchAll("SELECT * FROM roles ORDER BY role_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            ⚙️ Admin Panel
        </div>
        <ul class="sidebar-menu">
            <li><a href="/admin/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/admin/users.php" class="active">👥 Users</a></li>
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
                <h1>User Management</h1>
                <p class="text-muted">Manage system users and permissions</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> mb-3">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Create User Button -->
        <div class="mb-3">
            <button class="btn btn-primary" onclick="UI.showModal('create-user-modal')">+ Create New User</button>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">All Users</div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($u['role_name']); ?></span></td>
                                <td>
                                    <span class="badge badge-<?php echo $u['status'] === 'active' ? 'success' : ($u['status'] === 'suspended' ? 'danger' : 'secondary'); ?>">
                                        <?php echo htmlspecialchars($u['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $u['last_login'] ? date('M d, Y', strtotime($u['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                        <?php if ($u['status'] === 'active'): ?>
                                        <button type="submit" name="status" value="suspended" class="btn btn-sm btn-warning" 
                                                onclick="return confirm('Suspend this user?')">Suspend</button>
                                        <?php else: ?>
                                        <button type="submit" name="status" value="active" class="btn btn-sm btn-success">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="create-user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New User</h3>
                <button class="modal-close" onclick="UI.hideModal('create-user-modal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-2">
                        <label>Role *</label>
                        <select name="role_id" class="form-control" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>">
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="UI.hideModal('create-user-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
