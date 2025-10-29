<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->hasRole('client')) {
    header('Location: /client/dashboard.php');
} elseif ($auth->hasRole('admin')) {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /staff/dashboard.php');
}
exit;
