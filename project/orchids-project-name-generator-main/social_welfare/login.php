<?php
/**
 * Login Page - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_portal.php' : 'dashboard.php'));
    exit;
}

$error = '';
$success = '';

if (isset($_GET['msg'])) $success = sanitize($_GET['msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $login_role = sanitize($_POST['login_role'] ?? 'user');

    if (empty($email) || empty($password) || !in_array($login_role, ['user', 'admin'], true)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = db_fetch("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_active']) {
                $error = 'Your account has been disabled. Contact admin.';
            } elseif ($user['role'] !== $login_role) {
                $error = 'Selected login type does not match this account.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: ' . ($user['role'] === 'admin' ? 'admin_portal.php' : 'dashboard.php'));
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Social Welfare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="bg-animated"></div>

<div class="auth-page">
    <div class="d-flex justify-content-between align-items-center mb-4" style="width:100%;max-width:480px;margin:0 auto;padding:0 15px;">
        <a href="index.php" class="btn btn-glass-outline btn-sm">
            <i class="fas fa-home me-1"></i>Back to Home
        </a>
        <a href="register.php" class="btn btn-glass-outline btn-sm">
            <i class="fas fa-user-plus me-1"></i>Register
        </a>
    </div>
    <div class="auth-card">
        <!-- Logo -->
        <div class="auth-logo">
            <div class="logo-icon">
                <i class="fas fa-hands-helping text-white"></i>
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to your account to continue</p>
        </div>

        <!-- Alerts -->
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

        <!-- Login Form -->
        <form method="POST" id="loginForm" class="form-glass" novalidate>
            <div class="mb-3">
                <label class="form-label" for="login_role">
                    <i class="fas fa-user-tag me-1"></i> Login As
                </label>
                <select class="form-select" id="login_role" name="login_role" required>
                    <option value="user" <?= (($_POST['login_role'] ?? 'user') === 'user') ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= (($_POST['login_role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="email">
                    <i class="fas fa-envelope me-1"></i> Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email"
                    placeholder="your@email.com"
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                    required>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">
                    <i class="fas fa-lock me-1"></i> Password
                </label>
                <div class="position-relative">
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Enter your password" required>
                    <button type="button" class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-glass w-100 py-3 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>

        <div class="text-center" style="color:rgba(255,255,255,0.6);font-size:14px;">
            Don't have an account?
            <a href="register.php" style="color:#60a5fa;text-decoration:none;font-weight:600;">Register here</a>
        </div>

        <!-- Demo credentials info -->
        <div class="mt-4 p-3" style="background:rgba(255,255,255,0.07);border-radius:10px;border:1px solid rgba(255,255,255,0.1);">
            <div style="font-size:12px;color:rgba(255,255,255,0.5);text-align:center;margin-bottom:8px;">Demo Credentials</div>
            <div style="font-size:12px;color:rgba(255,255,255,0.7);">
                <strong>Admin:</strong> admin@socialwelfare.gov / <code style="color:#a78bfa;">Admin@123</code><br>
                <strong>User:</strong> ramesh@example.com / <code style="color:#a78bfa;">User@1234</code>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
