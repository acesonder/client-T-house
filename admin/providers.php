<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requirePermission('all');

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Handle provider creation/editing
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            $providerName = $_POST['provider_name'] ?? '';
            $serviceType = $_POST['service_type'] ?? '';
            $contactPerson = $_POST['contact_person'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $address = $_POST['address'] ?? '';
            $capacity = $_POST['capacity'] ?? null;
            
            if (empty($providerName) || empty($serviceType)) {
                throw new Exception('Provider name and service type are required');
            }
            
            $db->execute(
                "INSERT INTO service_providers (provider_name, service_type, contact_person, phone, email, address, capacity, current_availability, is_active) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [$providerName, $serviceType, $contactPerson, $phone, $email, $address, $capacity, $capacity]
            );
            
            $message = 'Service provider added successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'toggle_status') {
        try {
            $providerId = $_POST['provider_id'] ?? '';
            $isActive = $_POST['is_active'] ?? '';
            
            $db->execute(
                "UPDATE service_providers SET is_active = ? WHERE provider_id = ?",
                [$isActive, $providerId]
            );
            
            $message = 'Provider status updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Fetch all service providers
$providers = $db->fetchAll(
    "SELECT * FROM service_providers ORDER BY provider_name ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Provider Management - <?php echo APP_NAME; ?></title>
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
            <li><a href="/admin/providers.php" class="active">🏢 Service Providers</a></li>
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
                <h1>Service Provider Management</h1>
                <p class="text-muted">Manage external service providers and resources</p>
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
                        <h3 style="color: #2563eb;"><?php echo count($providers); ?></h3>
                        <p class="text-muted">Total Providers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #10b981;">
                            <?php echo count(array_filter($providers, function($p) { return $p['is_active']; })); ?>
                        </h3>
                        <p class="text-muted">Active Providers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #f59e0b;">
                            <?php 
                            $totalCapacity = array_sum(array_column($providers, 'capacity'));
                            echo $totalCapacity ?: 'N/A';
                            ?>
                        </h3>
                        <p class="text-muted">Total Capacity</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: #ef4444;">
                            <?php 
                            $available = array_sum(array_column($providers, 'current_availability'));
                            echo $available ?: 'N/A';
                            ?>
                        </h3>
                        <p class="text-muted">Available Slots</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Provider Button -->
        <div class="mb-3">
            <button class="btn btn-primary" onclick="UI.showModal('create-provider-modal')">+ Add Service Provider</button>
        </div>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <select id="service-filter" class="form-control" onchange="filterProviders()">
                            <option value="">All Service Types</option>
                            <option value="housing">Housing</option>
                            <option value="detox">Detox</option>
                            <option value="rehab">Rehabilitation</option>
                            <option value="mental_health">Mental Health</option>
                            <option value="legal">Legal Services</option>
                            <option value="id_services">ID Services</option>
                            <option value="children_services">Children Services</option>
                            <option value="medical">Medical</option>
                            <option value="employment">Employment</option>
                            <option value="education">Education</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" id="search-input" class="form-control" placeholder="Search providers..." 
                               onkeyup="filterProviders()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Providers Table -->
        <div class="card">
            <div class="card-header">All Service Providers</div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table" id="providers-table">
                        <thead>
                            <tr>
                                <th>Provider Name</th>
                                <th>Service Type</th>
                                <th>Contact</th>
                                <th>Phone</th>
                                <th>Capacity</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $provider): ?>
                            <tr data-service="<?php echo htmlspecialchars($provider['service_type']); ?>"
                                data-search="<?php echo htmlspecialchars(strtolower($provider['provider_name'] . ' ' . $provider['contact_person'])); ?>">
                                <td><strong><?php echo htmlspecialchars($provider['provider_name']); ?></strong></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $provider['service_type']))); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($provider['contact_person'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($provider['phone'] ?? 'N/A'); ?></td>
                                <td class="text-center"><?php echo $provider['capacity'] ?? 'N/A'; ?></td>
                                <td class="text-center">
                                    <?php if ($provider['current_availability'] !== null): ?>
                                        <span class="badge badge-<?php echo $provider['current_availability'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $provider['current_availability']; ?>
                                        </span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $provider['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $provider['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="provider_id" value="<?php echo $provider['provider_id']; ?>">
                                        <?php if ($provider['is_active']): ?>
                                        <button type="submit" name="is_active" value="0" class="btn btn-sm btn-warning">Deactivate</button>
                                        <?php else: ?>
                                        <button type="submit" name="is_active" value="1" class="btn btn-sm btn-success">Activate</button>
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

    <!-- Create Provider Modal -->
    <div id="create-provider-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Add Service Provider</h3>
                <button class="modal-close" onclick="UI.hideModal('create-provider-modal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Provider Name *</label>
                        <input type="text" name="provider_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Service Type *</label>
                        <select name="service_type" class="form-control" required>
                            <option value="">Select Service Type</option>
                            <option value="housing">Housing</option>
                            <option value="detox">Detox</option>
                            <option value="rehab">Rehabilitation</option>
                            <option value="mental_health">Mental Health</option>
                            <option value="legal">Legal Services</option>
                            <option value="id_services">ID Services</option>
                            <option value="children_services">Children Services</option>
                            <option value="medical">Medical</option>
                            <option value="employment">Employment</option>
                            <option value="education">Education</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Capacity</label>
                        <input type="number" name="capacity" class="form-control" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="UI.hideModal('create-provider-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Provider</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function filterProviders() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const serviceFilter = document.getElementById('service-filter').value;
            const rows = document.querySelectorAll('#providers-table tbody tr');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const service = row.getAttribute('data-service');
                
                const matchesSearch = searchData.includes(searchTerm);
                const matchesService = !serviceFilter || service === serviceFilter;
                
                row.style.display = (matchesSearch && matchesService) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
