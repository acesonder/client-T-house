<?php
/**
 * Bulletin API Endpoint
 * Returns bulletin posts for public display
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

$db = Database::getInstance();

try {
    $publicOnly = isset($_GET['public']) && $_GET['public'] === 'true';
    
    $sql = "SELECT post_id, title, content, post_type, priority, publish_date 
            FROM bulletin_posts 
            WHERE publish_date <= NOW() 
            AND (expiry_date IS NULL OR expiry_date > NOW())";
    
    if ($publicOnly) {
        $sql .= " AND is_public = 1";
    }
    
    $sql .= " ORDER BY priority DESC, publish_date DESC LIMIT 10";
    
    $posts = $db->fetchAll($sql);
    
    echo json_encode([
        'success' => true,
        'posts' => $posts ?: []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching bulletin posts'
    ]);
}
