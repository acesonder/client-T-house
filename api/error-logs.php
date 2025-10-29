<?php
/**
 * Error Logs API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->hasPermission('all')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$db = Database::getInstance();

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $limit = min($limit, 100); // Max 100
    
    $logs = $db->fetchAll(
        "SELECT * FROM error_logs ORDER BY created_at DESC LIMIT ?",
        [$limit]
    );
    
    echo json_encode([
        'success' => true,
        'logs' => $logs ?: []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching logs']);
}
