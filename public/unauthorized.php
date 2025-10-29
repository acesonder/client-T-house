<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - <?php echo defined('APP_NAME') ? APP_NAME : 'Shelter Management'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div class="card text-center" style="max-width: 500px;">
        <div class="card-body">
            <h1 style="font-size: 4rem; margin-bottom: 1rem;">🚫</h1>
            <h2>Access Denied</h2>
            <p class="text-muted">You don't have permission to access this page.</p>
            <div class="mt-3">
                <a href="javascript:history.back()" class="btn btn-outline">← Go Back</a>
                <a href="/public/index.html" class="btn btn-primary">Go to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
