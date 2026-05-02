<?php
require_once __DIR__ . '/db.php';

function is_logged_in(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: dashboard_user.php');
        exit;
    }
}

function require_user(): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'user') {
        header('Location: dashboard_admin.php');
        exit;
    }
}
?>
