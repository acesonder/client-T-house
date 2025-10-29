<?php
// Staff messaging - similar to client messages but for staff
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->hasRole('client')) {
    header('Location: /client/messages.php');
    exit;
}

// Redirect to client messages implementation with staff context
header('Location: /client/messages.php');
exit;
