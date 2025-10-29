<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// If already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    switch ($user['role_name']) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'staff':
            header('Location: /staff/dashboard.php');
            break;
        case 'volunteer':
            header('Location: /staff/dashboard.php');
            break;
        case 'client':
            header('Location: /client/dashboard.php');
            break;
        default:
            header('Location: /public/index.html');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        // Redirect based on role
        $user = $result['user'];
        switch ($user['role_name']) {
            case 'admin':
                header('Location: /admin/dashboard.php');
                break;
            case 'staff':
                header('Location: /staff/dashboard.php');
                break;
            case 'volunteer':
                header('Location: /staff/dashboard.php');
                break;
            case 'client':
                header('Location: /client/dashboard.php');
                break;
            default:
                header('Location: /public/index.html');
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);">
    <div class="card" style="width: 100%; max-width: 450px; margin: 1rem;">
        <div class="card-header text-center">
            <h2 style="margin: 0; color: white;">🏠 Shelter Management</h2>
        </div>
        <div class="card-body">
            <h3 class="text-center mb-3">Login</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus 
                           placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Enter your password">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Login
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p class="text-muted">
                    <a href="/public/index.html">← Back to Home</a>
                </p>
                <p class="text-muted" style="font-size: 0.9rem;">
                    Need help? Contact your case manager or call our 24/7 support line.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
