<?php
// Staff profile - similar to client profile
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->hasRole('client')) {
    header('Location: /client/profile.php');
    exit;
}

// Redirect to client profile implementation
header('Location: /client/profile.php');
exit;
