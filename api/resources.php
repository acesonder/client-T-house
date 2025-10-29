<?php
/**
 * Resources API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

try {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'recent':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            // Only clients see their own resources, staff/admin see all
            if ($auth->hasRole('client')) {
                $resources = $db->fetchAll(
                    "SELECT * FROM client_resources WHERE client_id = ? ORDER BY created_at DESC LIMIT ?",
                    [$userId, $limit]
                );
            } else {
                $resources = $db->fetchAll(
                    "SELECT cr.*, u.username as client_name 
                     FROM client_resources cr 
                     LEFT JOIN users u ON cr.client_id = u.user_id 
                     ORDER BY cr.created_at DESC LIMIT ?",
                    [$limit]
                );
            }
            
            echo json_encode(['success' => true, 'resources' => $resources ?: []]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching resources']);
}
