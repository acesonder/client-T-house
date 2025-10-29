<?php
// Staff resources management
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->hasRole('client')) {
    header('Location: /client/resources.php');
    exit;
}

// Redirect to client resources with staff permissions
header('Location: /client/resources.php');
exit;
