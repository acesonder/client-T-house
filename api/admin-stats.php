<?php
/**
 * Admin Stats API
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
    // Get total clients
    $totalClients = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'client')");
    
    // Get active staff
    $activeStaff = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role_id IN (SELECT role_id FROM roles WHERE role_name IN ('staff', 'volunteer')) AND status = 'active'");
    
    // Get pending assessments
    $pendingAssessments = $db->fetchOne("SELECT COUNT(*) as count FROM intake_assessments WHERE status = 'pending'");
    
    // Get critical cases
    $criticalCases = $db->fetchOne("SELECT COUNT(*) as count FROM intake_assessments WHERE risk_level = 'critical'");
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_clients' => $totalClients['count'] ?? 0,
            'active_staff' => $activeStaff['count'] ?? 0,
            'pending_assessments' => $pendingAssessments['count'] ?? 0,
            'critical_cases' => $criticalCases['count'] ?? 0
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching stats']);
}
