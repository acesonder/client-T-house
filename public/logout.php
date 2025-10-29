<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->logout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <meta http-equiv="refresh" content="3;url=/public/index.html">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div class="card text-center" style="max-width: 400px;">
        <div class="card-body">
            <h2>Logged Out Successfully</h2>
            <p class="text-muted">You have been logged out. Redirecting to home page...</p>
            <a href="/public/index.html" class="btn btn-primary">Go to Home</a>
        </div>
    </div>
</body>
</html>
