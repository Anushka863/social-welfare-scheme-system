<?php
/**
 * Admin Logout - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';

// Clear only admin-related session keys (do not disturb a normal user session if present)
unset($_SESSION['admin_id'], $_SESSION['admin_username']);

// If this session isn't a user login, end it fully
if (!isset($_SESSION['user_id'])) {
    $_SESSION = [];
    if (session_status() !== PHP_SESSION_NONE) {
        session_destroy();
    }

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

header('Location: admin_login.php?msg=You+have+been+logged+out');
exit;

