<?php
/**
 * Messages API
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
        case 'count_unread':
            $result = $db->fetchOne(
                "SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0",
                [$userId]
            );
            echo json_encode(['success' => true, 'count' => $result['count'] ?? 0]);
            break;
            
        case 'recent':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $messages = $db->fetchAll(
                "SELECT m.*, u.username as sender_name, u.first_name, u.last_name 
                 FROM messages m 
                 LEFT JOIN users u ON m.sender_id = u.user_id 
                 WHERE m.recipient_id = ? 
                 ORDER BY m.sent_at DESC LIMIT ?",
                [$userId, $limit]
            );
            echo json_encode(['success' => true, 'messages' => $messages ?: []]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching messages']);
}
