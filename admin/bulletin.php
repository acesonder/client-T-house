<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Handle bulletin post creation/editing
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $postType = $_POST['post_type'] ?? 'update';
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            $priority = $_POST['priority'] ?? 'normal';
            $expiryDate = $_POST['expiry_date'] ?? null;
            
            if (empty($title) || empty($content)) {
                throw new Exception('Title and content are required');
            }
            
            $db->execute(
                "INSERT INTO bulletin_posts (title, content, post_type, author_id, is_public, priority, expiry_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$title, $content, $postType, $user['user_id'], $isPublic, $priority, $expiryDate]
            );
            
            $message = 'Bulletin post created successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        try {
            $postId = $_POST['post_id'] ?? '';
            
            $db->execute(
                "DELETE FROM bulletin_posts WHERE post_id = ?",
                [$postId]
            );
            
            $message = 'Bulletin post deleted successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Fetch all bulletin posts
$posts = $db->fetchAll(
    "SELECT bp.*, u.username as author_name 
     FROM bulletin_posts bp
     LEFT JOIN users u ON bp.author_id = u.user_id
     ORDER BY bp.publish_date DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin Board Management - <?php echo APP_NAME; ?></title>
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
            <li><a href="/admin/bulletin.php" class="active">📢 Bulletin Board</a></li>
            <li><a href="/admin/reports.php">📈 Reports</a></li>
            <li><a href="/admin/logs.php">📝 Error Logs</a></li>
            <li><a href="/admin/config.php">⚙️ System Config</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Bulletin Board Management</h1>
                <p class="text-muted">Manage announcements and updates</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> mb-3">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #2563eb;"><?php echo count($posts); ?></h3>
                        <p class="text-muted">Total Posts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;">
                            <?php echo count(array_filter($posts, function($p) { return $p['is_public']; })); ?>
                        </h3>
                        <p class="text-muted">Public Posts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;">
                            <?php echo count(array_filter($posts, function($p) { return $p['priority'] === 'urgent'; })); ?>
                        </h3>
                        <p class="text-muted">Urgent Posts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;">
                            <?php echo count(array_filter($posts, function($p) { 
                                return $p['expiry_date'] && strtotime($p['expiry_date']) < time(); 
                            })); ?>
                        </h3>
                        <p class="text-muted">Expired Posts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Post Button -->
        <div class="mb-3">
            <button class="btn btn-primary" onclick="UI.showModal('create-post-modal')">+ Create New Post</button>
        </div>

        <!-- Posts Table -->
        <div class="card">
            <div class="card-header">All Bulletin Posts</div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Author</th>
                                <th>Priority</th>
                                <th>Visibility</th>
                                <th>Published</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($post['title']); ?></strong></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars(ucwords($post['post_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $post['priority'] === 'urgent' ? 'danger' : 
                                            ($post['priority'] === 'high' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo htmlspecialchars(ucwords($post['priority'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $post['is_public'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $post['is_public'] ? 'Public' : 'Private'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($post['publish_date'])); ?></td>
                                <td>
                                    <?php if ($post['expiry_date']): ?>
                                        <?php 
                                        $isExpired = strtotime($post['expiry_date']) < time();
                                        ?>
                                        <span class="<?php echo $isExpired ? 'text-danger' : ''; ?>">
                                            <?php echo date('M d, Y', strtotime($post['expiry_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        Never
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this post?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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

    <!-- Create Post Modal -->
    <div id="create-post-modal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Create Bulletin Post</h3>
                <button class="modal-close" onclick="UI.hideModal('create-post-modal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Content *</label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Post Type</label>
                            <select name="post_type" class="form-control">
                                <option value="update">Update</option>
                                <option value="announcement">Announcement</option>
                                <option value="event">Event</option>
                                <option value="resource">Resource</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Priority</label>
                            <select name="priority" class="form-control">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label>Expiry Date (Optional)</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="is_public" value="1" checked style="margin-right: 0.5rem;">
                            Make this post public (visible on landing page)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="UI.hideModal('create-post-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Post</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
