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

// Handle send message
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send') {
        try {
            $recipientId = $_POST['recipient_id'] ?? '';
            $subject = $_POST['subject'] ?? '';
            $body = $_POST['body'] ?? '';
            
            if (empty($recipientId) || empty($body)) {
                throw new Exception('Recipient and message body are required');
            }
            
            $db->execute(
                "INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES (?, ?, ?, ?)",
                [$user['user_id'], $recipientId, $subject, $body]
            );
            
            $message = 'Message sent successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'mark_read') {
        $messageId = $_POST['message_id'] ?? '';
        $db->execute(
            "UPDATE messages SET is_read = 1, read_at = NOW() WHERE message_id = ? AND recipient_id = ?",
            [$messageId, $user['user_id']]
        );
    }
}

// Fetch messages
$tab = $_GET['tab'] ?? 'inbox';

if ($tab === 'inbox') {
    $messages = $db->fetchAll(
        "SELECT m.*, u.username as sender_name, u.first_name, u.last_name 
         FROM messages m
         LEFT JOIN users u ON m.sender_id = u.user_id
         WHERE m.recipient_id = ?
         ORDER BY m.sent_at DESC",
        [$user['user_id']]
    );
} else {
    $messages = $db->fetchAll(
        "SELECT m.*, u.username as recipient_name, u.first_name, u.last_name 
         FROM messages m
         LEFT JOIN users u ON m.recipient_id = u.user_id
         WHERE m.sender_id = ?
         ORDER BY m.sent_at DESC",
        [$user['user_id']]
    );
}

// Fetch staff members for new message
$staffMembers = $db->fetchAll(
    "SELECT u.user_id, u.username, u.first_name, u.last_name, r.role_name
     FROM users u
     LEFT JOIN roles r ON u.role_id = r.role_id
     WHERE r.role_name IN ('admin', 'staff')
     AND u.status = 'active'
     ORDER BY u.first_name ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">
            🏠 Client Portal
        </div>
        <ul class="sidebar-menu">
            <li><a href="/client/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/client/messages.php" class="active">💬 Messages</a></li>
            <li><a href="/client/resources.php">📚 Resources</a></li>
            <li><a href="/client/documents.php">📄 Documents</a></li>
            <li><a href="/client/assessment.php">📋 Assessments</a></li>
            <li><a href="/client/profile.php">⚙️ Profile Settings</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Messages</h1>
                <p class="text-muted">Communicate with your case manager and support team</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> mb-3">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- New Message Button -->
        <div class="mb-3">
            <button class="btn btn-primary" onclick="UI.showModal('compose-modal')">✉️ New Message</button>
        </div>

        <!-- Tabs -->
        <div class="mb-3" style="border-bottom: 2px solid #e5e7eb;">
            <div style="display: flex; gap: 1rem;">
                <a href="/client/messages.php?tab=inbox" 
                   class="<?php echo $tab === 'inbox' ? 'active' : ''; ?>"
                   style="padding: 0.75rem 1rem; text-decoration: none; color: inherit; border-bottom: 2px solid <?php echo $tab === 'inbox' ? '#2563eb' : 'transparent'; ?>;">
                    📥 Inbox
                </a>
                <a href="/client/messages.php?tab=sent" 
                   class="<?php echo $tab === 'sent' ? 'active' : ''; ?>"
                   style="padding: 0.75rem 1rem; text-decoration: none; color: inherit; border-bottom: 2px solid <?php echo $tab === 'sent' ? '#2563eb' : 'transparent'; ?>;">
                    📤 Sent
                </a>
            </div>
        </div>

        <!-- Messages List -->
        <div class="card">
            <div class="card-header">
                <?php echo $tab === 'inbox' ? 'Inbox' : 'Sent Messages'; ?>
            </div>
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <p class="text-center text-muted">No messages found</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo $tab === 'inbox' ? 'From' : 'To'; ?></th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $msg): ?>
                                <tr style="<?php echo ($tab === 'inbox' && !$msg['is_read']) ? 'font-weight: bold;' : ''; ?>">
                                    <td>
                                        <?php 
                                        if ($tab === 'inbox') {
                                            $name = trim(($msg['first_name'] ?? '') . ' ' . ($msg['last_name'] ?? ''));
                                            echo htmlspecialchars($name ?: $msg['sender_name']);
                                        } else {
                                            $name = trim(($msg['first_name'] ?? '') . ' ' . ($msg['last_name'] ?? ''));
                                            echo htmlspecialchars($name ?: $msg['recipient_name']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($msg['subject'] ?: 'No Subject'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($msg['sent_at'])); ?></td>
                                    <td>
                                        <?php if ($tab === 'inbox'): ?>
                                            <span class="badge badge-<?php echo $msg['is_read'] ? 'secondary' : 'primary'; ?>">
                                                <?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Sent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $msg['message_id']; ?>, <?php echo $tab === 'inbox' ? 'true' : 'false'; ?>)">
                                            View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Compose Message Modal -->
    <div id="compose-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>New Message</h3>
                <button class="modal-close" onclick="UI.hideModal('compose-modal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="send">
                <div class="modal-body">
                    <div class="mb-2">
                        <label>To *</label>
                        <select name="recipient_id" class="form-control" required>
                            <option value="">Select Recipient</option>
                            <?php foreach ($staffMembers as $staff): ?>
                                <option value="<?php echo $staff['user_id']; ?>">
                                    <?php 
                                    $name = trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
                                    echo htmlspecialchars($name ?: $staff['username']);
                                    echo ' (' . htmlspecialchars($staff['role_name']) . ')';
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Message *</label>
                        <textarea name="body" class="form-control" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="UI.hideModal('compose-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Message Modal -->
    <div id="view-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="view-subject">Message</h3>
                <button class="modal-close" onclick="UI.hideModal('view-modal')">&times;</button>
            </div>
            <div class="modal-body" id="view-body">
                <div class="text-center"><div class="spinner"></div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="UI.hideModal('view-modal')">Close</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        <?php if ($settings): ?>
        ThemeManager.applyTheme(<?php echo json_encode($settings); ?>);
        <?php endif; ?>

        function viewMessage(messageId, markAsRead) {
            UI.showModal('view-modal');
            
            // Mark as read if inbox
            if (markAsRead) {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('message_id', messageId);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
            }
            
            // In a full implementation, this would fetch message details via API
            setTimeout(() => {
                const messages = <?php echo json_encode($messages); ?>;
                const message = messages.find(m => m.message_id == messageId);
                
                if (message) {
                    document.getElementById('view-subject').textContent = message.subject || 'No Subject';
                    document.getElementById('view-body').innerHTML = `
                        <div class="mb-2">
                            <strong>From:</strong> ${Utils.escapeHtml(message.sender_name || message.recipient_name)}
                        </div>
                        <div class="mb-2">
                            <strong>Date:</strong> ${new Date(message.sent_at).toLocaleString()}
                        </div>
                        <hr>
                        <div style="white-space: pre-wrap;">${Utils.escapeHtml(message.body)}</div>
                    `;
                }
            }, 100);
        }
    </script>
</body>
</html>
