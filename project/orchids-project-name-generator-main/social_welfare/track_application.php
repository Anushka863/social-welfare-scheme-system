<?php
/**
 * Track Application - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireUser();

$page_title = 'Track Application';
$uid = $_SESSION['user_id'];

if (isset($_GET['ajax_mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
}

// Search by application ID
$search_id = sanitize($_GET['id'] ?? '');
$searched_app = null;

if ($search_id) {
    $stmt = $conn->prepare("
        SELECT a.*, s.title, s.category, s.benefits
        FROM applications a
        JOIN schemes s ON a.scheme_id = s.id
        WHERE a.application_id = ? AND a.user_id = ?
    ");
    $stmt->bind_param("si", $search_id, $uid);
    $stmt->execute();
    $searched_app = $stmt->get_result()->fetch_assoc();
}

// Fetch all applications for this user
$stmt = $conn->prepare("
    SELECT a.*, s.title, s.category
    FROM applications a
    JOIN schemes s ON a.scheme_id = s.id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$all_apps = $stmt->get_result();

// Status icons & timeline labels
$status_steps = ['pending', 'under_review', 'approved'];
$status_labels = [
    'pending' => ['label' => 'Application Submitted', 'icon' => 'fas fa-paper-plane', 'color' => '#ffc107'],
    'under_review' => ['label' => 'Under Review', 'icon' => 'fas fa-search', 'color' => '#17a2b8'],
    'approved' => ['label' => 'Approved', 'icon' => 'fas fa-check-circle', 'color' => '#28a745'],
    'rejected' => ['label' => 'Rejected', 'icon' => 'fas fa-times-circle', 'color' => '#dc3545'],
];

include 'includes/header.php';
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            Track Application
            <small>Monitor the status of your welfare scheme applications</small>
        </div>
    </div>

    <!-- Search by App ID -->
    <div class="filter-bar mb-4">
        <form method="GET" class="d-flex gap-3 w-100 flex-wrap">
            <div class="search-wrap flex-grow-1">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" name="id"
                    placeholder="Search by Application ID (e.g. SW2026-0001)"
                    value="<?= htmlspecialchars($search_id) ?>">
            </div>
            <button type="submit" class="btn btn-glass">
                <i class="fas fa-search me-2"></i>Track
            </button>
        </form>
    </div>

    <!-- Searched Application Detail -->
    <?php if ($search_id && $searched_app): ?>
    <div class="glass-card p-4 mb-4 fade-in-up" style="border-color:rgba(37,117,252,0.3);">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <div class="app-id-box mb-2">
                    <i class="fas fa-id-card"></i>
                    <?= htmlspecialchars($searched_app['application_id']) ?>
                </div>
                <h5 style="color:#fff;font-weight:700;margin:0;"><?= htmlspecialchars($searched_app['title']) ?></h5>
                <span style="font-size:13px;color:rgba(255,255,255,0.5);"><?= htmlspecialchars($searched_app['category']) ?></span>
            </div>
            <span class="status-badge status-<?= $searched_app['status'] ?>" style="font-size:14px;padding:8px 20px;">
                <?= ucfirst(str_replace('_', ' ', $searched_app['status'])) ?>
            </span>
        </div>

        <!-- Timeline -->
        <div class="row g-4">
            <div class="col-md-7">
                <h6 style="color:rgba(255,255,255,0.5);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Application Timeline</h6>
                <div class="timeline">
                    <?php
                    $current_status = $searched_app['status'];
                    $timeline_steps = $current_status === 'rejected'
                        ? ['pending', 'rejected']
                        : ['pending', 'under_review', 'approved'];

                    $reached = false;
                    foreach ($timeline_steps as $step):
                        $info = $status_labels[$step];
                        $is_current = $step === $current_status;
                        $is_past = !$reached && !$is_current;
                        if ($is_current) $reached = true;
                    ?>
                    <div class="timeline-item <?= $is_current ? ($step === 'rejected' ? 'rejected' : 'active') : ($is_past ? 'active' : '') ?>">
                        <div class="timeline-label" style="color:<?= ($is_current || $is_past) ? '#fff' : 'rgba(255,255,255,0.4)' ?>;">
                            <i class="<?= $info['icon'] ?> me-2" style="color:<?= ($is_current || $is_past) ? $info['color'] : 'rgba(255,255,255,0.2)' ?>;"></i>
                            <?= $info['label'] ?>
                        </div>
                        <?php if ($is_current): ?>
                        <div class="timeline-date"><?= date('d M Y, h:i A', strtotime($searched_app['updated_at'])) ?></div>
                        <?php if ($searched_app['remarks']): ?>
                        <div class="timeline-desc"><?= htmlspecialchars($searched_app['remarks']) ?></div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-5">
                <h6 style="color:rgba(255,255,255,0.5);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;">Application Info</h6>
                <?php $info_rows = [
                    ['fas fa-calendar-plus', 'Applied On', date('d M Y, h:i A', strtotime($searched_app['applied_at']))],
                    ['fas fa-sync', 'Last Updated', date('d M Y, h:i A', strtotime($searched_app['updated_at']))],
                    ['fas fa-tag', 'Category', $searched_app['category']],
                ]; ?>
                <?php foreach ($info_rows as [$icon, $label, $val]): ?>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.07);font-size:14px;">
                    <span style="color:rgba(255,255,255,0.5);"><i class="<?= $icon ?> me-2"></i><?= $label ?></span>
                    <span style="color:#fff;"><?= htmlspecialchars($val) ?></span>
                </div>
                <?php endforeach; ?>

                <?php if ($searched_app['remarks']): ?>
                <div class="mt-3 p-3" style="background:rgba(255,255,255,0.07);border-radius:10px;">
                    <div style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;">Admin Remarks</div>
                    <div style="font-size:13px;color:rgba(255,255,255,0.8);"><?= htmlspecialchars($searched_app['remarks']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php elseif ($search_id): ?>
    <div class="alert-glass error mb-4">
        <i class="fas fa-times-circle"></i>
        <span>No application found with ID <strong><?= htmlspecialchars($search_id) ?></strong> under your account.</span>
    </div>
    <?php endif; ?>

    <!-- All Applications Table -->
    <div class="glass-card p-0">
        <div class="p-4 pb-0">
            <h6 style="color:#fff;font-weight:700;font-size:16px;">
                <i class="fas fa-list me-2"></i>All My Applications
            </h6>
        </div>
        <div class="p-4">
            <?php if ($all_apps->num_rows > 0): ?>
            <div class="responsive-table">
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Scheme</th>
                            <th>Category</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $all_apps->fetch_assoc()): ?>
                        <tr>
                            <td data-label="App ID">
                                <span style="font-family:monospace;color:#a78bfa;font-size:13px;">
                                    <?= htmlspecialchars($app['application_id']) ?>
                                </span>
                            </td>
                            <td data-label="Scheme" style="font-weight:500;"><?= htmlspecialchars($app['title']) ?></td>
                            <td data-label="Category">
                                <span style="font-size:12px;background:rgba(255,255,255,0.1);padding:3px 10px;border-radius:10px;">
                                    <?= htmlspecialchars($app['category']) ?>
                                </span>
                            </td>
                            <td data-label="Applied On" style="color:rgba(255,255,255,0.6);font-size:13px;">
                                <?= date('d M Y', strtotime($app['applied_at'])) ?>
                            </td>
                            <td data-label="Status">
                                <span class="status-badge status-<?= $app['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                                </span>
                            </td>
                            <td data-label="Action">
                                <a href="?id=<?= urlencode($app['application_id']) ?>" class="btn btn-glass-outline" style="padding:5px 14px;font-size:12px;">
                                    <i class="fas fa-eye me-1"></i>Track
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-search" style="font-size:40px;color:rgba(255,255,255,0.15);display:block;margin-bottom:12px;"></i>
                <p style="color:rgba(255,255,255,0.4);margin:0;">No applications submitted yet.</p>
                <a href="schemes.php" class="btn btn-glass mt-3">
                    <i class="fas fa-th-list me-2"></i>Browse Schemes
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
