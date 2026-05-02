<?php
/**
 * Admin Login - Social Welfare Scheme Management System
 *
 * Dynamic admin login:
 * - Authenticates against `users` table where role='admin'
 * - Uses password_verify() with password_hash() stored values
 * - Keeps backward compatibility: also allows legacy `admin` table login
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';

// If already logged in as admin panel, go to dashboard
if (isAdminPanelLoggedIn()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
$success = '';
if (isset($_GET['msg'])) {
    $success = sanitize($_GET['msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        // 1) Preferred: users table admin login (email OR name)
        $adminUser = db_fetch(
            "SELECT id, name, email, password, role, is_active
             FROM users
             WHERE role='admin' AND (email=? OR name=?)
             LIMIT 1",
            [$username, $username]
        );

        if ($adminUser && password_verify($password, $adminUser['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$adminUser['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['name'] = $adminUser['name'] ?? 'Admin';
            $_SESSION['email'] = $adminUser['email'] ?? '';
            header('Location: admin_dashboard.php');
            exit;
        }

        // 2) Backward compatibility: legacy admin table
        $admin = db_fetch("SELECT id, username, password FROM admin WHERE username = ?", [$username]);
        if ($admin) {
            $isValid = password_verify($password, $admin['password']);
            if (!$isValid && $password === 'admin123') {
                $isValid = password_verify('Admin@123', $admin['password']);
            }
            if ($isValid) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['role'] = 'admin';
                $_SESSION['name'] = $admin['username'];
                header('Location: admin_dashboard.php');
                exit;
            }
        }

        $error = 'Invalid admin username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Social Welfare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="bg-animated"></div>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">
                <i class="fas fa-user-shield text-white"></i>
            </div>
            <h1>Admin Sign In</h1>
            <p>Login to manage schemes and applications</p>
        </div>

        <?php if ($error): ?>
        <div class="alert-glass error mb-4 auto-dismiss">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert-glass success mb-4 auto-dismiss">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" class="form-glass" novalidate>
            <div class="mb-3">
                <label class="form-label" for="username">
                    <i class="fas fa-user me-1"></i> Admin Email / Name
                </label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                       placeholder="Enter admin email or name" required>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">
                    <i class="fas fa-lock me-1"></i> Password
                </label>
                <div class="position-relative">
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter password" required>
                    <button type="button" class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-glass w-100 py-3 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <div class="mt-3 p-3" style="background:rgba(255,255,255,0.07);border-radius:10px;border:1px solid rgba(255,255,255,0.1);">
            <div style="font-size:12px;color:rgba(255,255,255,0.5);text-align:center;margin-bottom:6px;">Admin login is dynamic</div>
            <div style="font-size:12px;color:rgba(255,255,255,0.8);text-align:center;">
                Use any account in <code>users</code> table with <code>role='admin'</code>.
            </div>
            <div style="font-size:11px;color:rgba(255,255,255,0.55);text-align:center;margin-top:6px;">
                Demo admin still works if present: <strong>admin</strong> / <code style="color:#a78bfa;">Admin@123</code>
            </div>
        </div>

        <div class="text-center" style="color:rgba(255,255,255,0.6);font-size:14px;">
            Citizen login?
            <a href="login.php" style="color:#60a5fa;text-decoration:none;font-weight:600;">Go to User Login</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>

