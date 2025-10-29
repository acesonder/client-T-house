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

// Handle file upload
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    try {
        $documentType = $_POST['document_type'] ?? 'other';
        $notes = $_POST['notes'] ?? '';
        
        $file = $_FILES['document'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
        
        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size must be less than 10MB');
        }
        
        // Validate file type
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX');
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../assets/uploads/documents/' . $user['user_id'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save file');
        }
        
        // Save to database
        $relativePath = '/assets/uploads/documents/' . $user['user_id'] . '/' . $filename;
        $db->execute(
            "INSERT INTO client_documents (client_id, document_type, document_name, file_path, file_size, mime_type, uploaded_by, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$user['user_id'], $documentType, $file['name'], $relativePath, $file['size'], $file['type'], $user['user_id'], $notes]
        );
        
        $message = 'Document uploaded successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Fetch client documents
$documents = $db->fetchAll(
    "SELECT cd.*, 
     u.username as uploaded_by_name, u.first_name as uploader_first, u.last_name as uploader_last,
     v.username as verified_by_name, v.first_name as verifier_first, v.last_name as verifier_last
     FROM client_documents cd
     LEFT JOIN users u ON cd.uploaded_by = u.user_id
     LEFT JOIN users v ON cd.verified_by = v.user_id
     WHERE cd.client_id = ?
     ORDER BY cd.created_at DESC",
    [$user['user_id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - <?php echo APP_NAME; ?></title>
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
            <li><a href="/client/resources.php">📚 Resources</a></li>
            <li><a href="/client/documents.php" class="active">📄 Documents</a></li>
            <li><a href="/client/assessment.php">📋 Assessments</a></li>
            <li><a href="/client/profile.php">⚙️ Profile Settings</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>My Documents</h1>
                <p class="text-muted">Securely store and manage your important documents</p>
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
                        <h3 style="color: #2563eb;"><?php echo count($documents); ?></h3>
                        <p class="text-muted">Total Documents</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;">
                            <?php echo count(array_filter($documents, function($d) { return $d['is_verified']; })); ?>
                        </h3>
                        <p class="text-muted">Verified</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;">
                            <?php echo count(array_filter($documents, function($d) { return !$d['is_verified']; })); ?>
                        </h3>
                        <p class="text-muted">Pending Verification</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;">
                            <?php 
                            $expiring = count(array_filter($documents, function($d) { 
                                return $d['expiry_date'] && strtotime($d['expiry_date']) < strtotime('+30 days'); 
                            }));
                            echo $expiring;
                            ?>
                        </h3>
                        <p class="text-muted">Expiring Soon</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Button -->
        <div class="mb-3">
            <button class="btn btn-primary" onclick="UI.showModal('upload-modal')">📤 Upload Document</button>
        </div>

        <!-- Documents by Type -->
        <?php 
        $documentTypes = ['id', 'medical', 'legal', 'financial', 'education', 'employment', 'housing', 'other'];
        $documentsByType = [];
        foreach ($documents as $doc) {
            $type = $doc['document_type'];
            if (!isset($documentsByType[$type])) {
                $documentsByType[$type] = [];
            }
            $documentsByType[$type][] = $doc;
        }
        ?>

        <?php if (empty($documents)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <div style="padding: 3rem 0;">
                        <h3 style="opacity: 0.5;">📄</h3>
                        <p class="text-muted">No documents uploaded yet</p>
                        <p class="text-muted">Upload your important documents to keep them safe and accessible</p>
                        <button class="btn btn-primary" onclick="UI.showModal('upload-modal')">Upload Your First Document</button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($documentTypes as $type): ?>
                <?php if (isset($documentsByType[$type]) && !empty($documentsByType[$type])): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <?php echo htmlspecialchars(ucwords($type)); ?> Documents
                        <span class="badge badge-info"><?php echo count($documentsByType[$type]); ?></span>
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Document Name</th>
                                        <th>Size</th>
                                        <th>Uploaded</th>
                                        <th>Status</th>
                                        <th>Expiry</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documentsByType[$type] as $doc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                        <td><?php echo number_format($doc['file_size'] / 1024, 2); ?> KB</td>
                                        <td><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></td>
                                        <td>
                                            <?php if ($doc['is_verified']): ?>
                                                <span class="badge badge-success">✓ Verified</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($doc['expiry_date']): ?>
                                                <?php 
                                                $isExpired = strtotime($doc['expiry_date']) < time();
                                                $isExpiringSoon = strtotime($doc['expiry_date']) < strtotime('+30 days');
                                                ?>
                                                <span class="<?php echo $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : ''); ?>">
                                                    <?php echo date('M d, Y', strtotime($doc['expiry_date'])); ?>
                                                </span>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-info">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Security Notice -->
        <div class="card">
            <div class="card-header">🔒 Security & Privacy</div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>All documents are encrypted and securely stored</li>
                    <li>Only you and authorized staff can access your documents</li>
                    <li>Documents are automatically backed up daily</li>
                    <li>Accepted formats: PDF, JPG, PNG, DOC, DOCX (max 10MB)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Document</h3>
                <button class="modal-close" onclick="UI.hideModal('upload-modal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Document Type *</label>
                        <select name="document_type" class="form-control" required>
                            <option value="id">ID Document</option>
                            <option value="medical">Medical Record</option>
                            <option value="legal">Legal Document</option>
                            <option value="financial">Financial Document</option>
                            <option value="education">Education Document</option>
                            <option value="employment">Employment Document</option>
                            <option value="housing">Housing Document</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Select File *</label>
                        <input type="file" name="document" class="form-control" required 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted">Max size: 10MB. Formats: PDF, JPG, PNG, DOC, DOCX</small>
                    </div>
                    <div class="mb-2">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Add any additional information about this document..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="UI.hideModal('upload-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        <?php if ($settings): ?>
        ThemeManager.applyTheme(<?php echo json_encode($settings); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
