<?php
/**
 * Authentication Helper - Session & Role Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require user to be logged in (any role)
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php?msg=Please+login+to+continue');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: dashboard.php?error=Access+denied');
        exit;
    }
}

/**
 * Require regular user role
 */
function requireUser() {
    requireLogin();
    if ($_SESSION['role'] !== 'user') {
        header('Location: admin_portal.php');
        exit;
    }
}

/**
 * Check if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
