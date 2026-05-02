<?php
/**
 * Header / Sidebar Navigation Include
 * Used by all authenticated pages
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

$notif_count = 0;
if (isset($_SESSION['user_id'])) {
    $notif_count = getUnreadNotifications($conn, $_SESSION['user_id']);
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | ' : '' ?>Social Welfare System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Glassmorphism CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1' ? 'dark-mode' : '' ?>">

<div class="bg-animated"></div>

<!-- Sidebar -->
<nav class="sidebar glass-sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-hands-helping"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name">SocialWelfare</span>
            <span class="brand-tagline">Management System</span>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <?php if (isAdmin()): ?>
    <!-- Admin Navigation -->
    <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'admin_portal.php' ? 'active' : '' ?>" href="admin_portal.php?tab=applications">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'manage_schemes.php' ? 'active' : '' ?>" href="manage_schemes.php">
                <i class="fas fa-list-alt"></i>
                <span>Manage Schemes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'manage_applications.php' ? 'active' : '' ?>" href="manage_applications.php">
                <i class="fas fa-file-alt"></i>
                <span>Applications</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'admin_users.php' ? 'active' : '' ?>" href="admin_users.php">
                <i class="fas fa-user-shield"></i>
                <span>Admin Users</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>" href="profile.php">
                <i class="fas fa-user-cog"></i>
                <span>Profile</span>
            </a>
        </li>
    </ul>
    <?php else: ?>
    <!-- User Navigation -->
    <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'schemes.php' ? 'active' : '' ?>" href="schemes.php">
                <i class="fas fa-th-list"></i>
                <span>Browse Schemes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'track_application.php' ? 'active' : '' ?>" href="track_application.php">
                <i class="fas fa-search-location"></i>
                <span>Track Application</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>" href="profile.php">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <div class="sidebar-bottom">
        <a href="logout.php" class="nav-link logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>

<!-- Main Content Wrapper -->
<div class="main-wrapper" id="mainWrapper">

    <!-- Top Navbar -->
    <nav class="top-navbar glass-nav">
        <div class="d-flex align-items-center gap-3">
            <button class="btn sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <span class="page-title-nav"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Dark mode toggle -->
            <button class="btn dark-toggle" id="darkToggle" title="Toggle Dark Mode">
                <i class="fas fa-moon" id="darkIcon"></i>
            </button>

            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn notif-btn position-relative" data-bs-toggle="dropdown" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($notif_count > 0): ?>
                    <span class="notif-badge"><?= $notif_count ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end glass-dropdown notif-dropdown">
                    <div class="notif-header">
                        <strong>Notifications</strong>
                        <?php if ($notif_count > 0): ?>
                        <a href="?mark_read=1" class="mark-all-read">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        $uid = $_SESSION['user_id'];
                        $notifs = db_query("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5", [$uid]);
                        if (!empty($notifs)) {
                            foreach ($notifs as $n):
                    ?>
                    <div class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>">
                        <i class="fas fa-info-circle me-2"></i>
                        <span><?= htmlspecialchars($n['message']) ?></span>
                        <div class="notif-time"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></div>
                    </div>
                    <?php endforeach; } else { ?>
                    <div class="notif-empty"><i class="fas fa-bell-slash me-2"></i>No notifications</div>
                    <?php }} ?>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <button class="btn profile-btn d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <div class="profile-avatar">
                        <?php
                        $avatar_char = isset($_SESSION['name']) ? strtoupper(substr($_SESSION['name'], 0, 1)) : 'U';
                        echo $avatar_char;
                        ?>
                    </div>
                    <span class="profile-name d-none d-md-inline"><?= isset($_SESSION['name']) ? htmlspecialchars(explode(' ', $_SESSION['name'])[0]) : 'User' ?></span>
                    <i class="fas fa-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end glass-dropdown">
                    <li class="dropdown-header">
                        <strong><?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : '' ?></strong>
                        <div class="small text-muted"><?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '' ?></div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content starts below -->
    <div class="page-content">
