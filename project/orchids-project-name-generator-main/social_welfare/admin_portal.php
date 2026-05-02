<?php
/**
 * Admin Portal - Social Welfare Scheme Management System
 *
 * This page is a single dashboard view for admins:
 * - View and approve/reject all applications
 * - Add / update schemes
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();

$page_title = 'Admin Portal';

$tab = sanitize($_GET['tab'] ?? '');
if (!in_array($tab, ['applications', 'schemes'], true)) {
    // When embedded pages change query params (like "?action=add"), the "tab" param may disappear.
    // Use "action" as a hint that we're in schemes mode.
    $tab = isset($_GET['action']) ? 'schemes' : 'applications';
}

// Render header once (managed pages will not include header/footer in embed mode).
include 'includes/header.php';
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            Admin Portal
            <small>Applications and Schemes management</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="admin_portal.php?tab=applications" class="btn <?= $tab === 'applications' ? 'btn-glass' : 'btn-glass-outline' ?>">
                <i class="fas fa-tasks me-2"></i>Applications
            </a>
            <a href="admin_portal.php?tab=schemes" class="btn <?= $tab === 'schemes' ? 'btn-glass' : 'btn-glass-outline' ?>">
                <i class="fas fa-th-list me-2"></i>Schemes
            </a>
        </div>
    </div>

    <?php
    // Embed existing admin pages (skip their header/footer).
    $_GET['embed'] = 1;
    if ($tab === 'schemes') {
        include 'manage_schemes.php';
    } else {
        include 'manage_applications.php';
    }
    ?>
</div>

<?php include 'includes/footer.php'; ?>

