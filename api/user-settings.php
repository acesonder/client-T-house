<?php
/**
 * User Settings API Endpoint
 * Get and update user settings
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get user settings
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
        
        // Check permission (users can only get their own settings unless admin)
        if ($userId != $_SESSION['user_id'] && !$auth->hasPermission('all')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
        
        $settings = $auth->getUserSettings($userId);
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update user settings
        $userId = $_POST['user_id'] ?? $_SESSION['user_id'];
        
        // Check permission
        if ($userId != $_SESSION['user_id'] && !$auth->hasPermission('all')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
        
        $settings = [
            'theme_color' => $_POST['theme_color'] ?? null,
            'navbar_color' => $_POST['navbar_color'] ?? null,
            'button_color' => $_POST['button_color'] ?? null,
            'font_size' => $_POST['font_size'] ?? null,
            'layout_style' => $_POST['layout_style'] ?? null,
            'custom_css' => $_POST['custom_css'] ?? null
        ];
        
        // Remove null values
        $settings = array_filter($settings, function($value) {
            return $value !== null;
        });
        
        $result = $auth->updateUserSettings($userId, $settings);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update settings'
            ]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
