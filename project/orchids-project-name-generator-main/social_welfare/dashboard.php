<?php
/**
 * User Dashboard - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireUser();

$page_title = 'My Dashboard';
$uid = $_SESSION['user_id'];

// Mark notifications read if requested
if (isset($_GET['ajax_mark_read'])) {
    db_query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);
    echo json_encode(['ok' => true]);
    exit;
}

// Fetch user profile
$user = db_fetch("SELECT * FROM users WHERE id=?", [$uid]);

// Profile completeness
$fields = ['name','email','phone','dob','gender','address','aadhar','annual_income','category','profile_photo'];
$filled = 0;
foreach ($fields as $f) if (!empty($user[$f])) $filled++;
$profile_pct = (int)(($filled / count($fields)) * 100);

// User stats
$total_apps = db_fetch("SELECT COUNT(*) as total FROM applications WHERE user_id=?", [$uid])['total'];
$approved = db_fetch("SELECT COUNT(*) as cnt FROM applications WHERE user_id=? AND status='approved'", [$uid])['cnt'];
$pending = db_fetch("SELECT COUNT(*) as cnt FROM applications WHERE user_id=? AND status='pending'", [$uid])['cnt'];

// Recent applications
$recent_apps = db_query("
    SELECT a.*, s.title, s.category
    FROM applications a
    JOIN schemes s ON a.scheme_id = s.id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
", [$uid]);

// Available schemes count
$total_schemes = db_fetch("SELECT COUNT(*) as cnt FROM schemes WHERE status='active'")['cnt'];

// Unapplied active schemes
$available_schemes = db_query("
    SELECT *
    FROM schemes
    WHERE status='active' AND id NOT IN (SELECT scheme_id FROM applications WHERE user_id=?)
    ORDER BY created_at DESC
    LIMIT 5
", [$uid]);

include 'includes/header.php';
?>

<div class="fade-in">
    <!-- Welcome Banner -->
    <div class="glass-card p-4 mb-4" style="background:linear-gradient(135deg,rgba(106,17,203,0.3),rgba(37,117,252,0.3));">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h2 style="color:#fff;font-weight:700;margin-bottom:4px;">
                    Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋
                </h2>
                <p style="color:rgba(255,255,255,0.6);margin:0;font-size:14px;">
                    <i class="fas fa-calendar me-1"></i><?= date('l, d F Y') ?>
                </p>
            </div>
            <a href="schemes.php" class="btn btn-glass">
                <i class="fas fa-search me-2"></i>Browse Schemes
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3 fade-in-up delay-1">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#6a11cb,#2575fc);">
                    <i class="fas fa-file-alt text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $total_apps ?>"><?= $total_apps ?></div>
                <div class="card-label">Total Applications</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-2">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#1e7e34,#28a745);">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $approved ?>"><?= $approved ?></div>
                <div class="card-label">Approved</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-3">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#b8860b,#ffc107);">
                    <i class="fas fa-clock text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $pending ?>"><?= $pending ?></div>
                <div class="card-label">Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-4">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#0077b6,#00b4d8);">
                    <i class="fas fa-th-list text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $total_schemes ?>"><?= $total_schemes ?></div>
                <div class="card-label">Active Schemes</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Applications -->
        <div class="col-lg-8">
            <div class="glass-card p-0">
                <div class="d-flex align-items-center justify-content-between p-4 pb-0">
                    <h6 style="color:#fff;font-weight:700;font-size:16px;margin:0;">
                        <i class="fas fa-history me-2"></i>Recent Applications
                    </h6>
                    <a href="track_application.php" style="font-size:13px;color:#60a5fa;text-decoration:none;">
                        View All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="p-4">
                    <?php if ($recent_apps->num_rows > 0): ?>
                    <div class="responsive-table">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>App ID</th>
                                    <th>Scheme</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($app = $recent_apps->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="App ID">
                                        <span style="font-family:monospace;font-size:13px;color:#a78bfa;">
                                            <?= htmlspecialchars($app['application_id']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Scheme">
                                        <div style="font-weight:500;"><?= htmlspecialchars($app['title']) ?></div>
                                        <div style="font-size:11px;color:rgba(255,255,255,0.4);"><?= htmlspecialchars($app['category']) ?></div>
                                    </td>
                                    <td data-label="Date" style="font-size:13px;color:rgba(255,255,255,0.6);">
                                        <?= date('d M Y', strtotime($app['applied_at'])) ?>
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge status-<?= $app['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt" style="font-size:40px;color:rgba(255,255,255,0.2);margin-bottom:12px;display:block;"></i>
                        <p style="color:rgba(255,255,255,0.5);margin:0;font-size:14px;">No applications yet.</p>
                        <a href="schemes.php" class="btn btn-glass mt-3">
                            <i class="fas fa-search me-2"></i>Browse Schemes
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Available Schemes -->
            <div class="glass-card p-0 mt-4">
                <div class="d-flex align-items-center justify-content-between p-4 pb-0">
                    <h6 style="color:#fff;font-weight:700;font-size:16px;margin:0;">
                        <i class="fas fa-star me-2"></i>Available Schemes to Apply
                    </h6>
                    <a href="schemes.php" style="font-size:13px;color:#60a5fa;text-decoration:none;">
                        View All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="p-4">
                    <?php if ($available_schemes->num_rows > 0): ?>
                    <div class="responsive-table">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Scheme</th>
                                    <th>Category</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($s = $available_schemes->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Scheme">
                                        <div style="font-weight:500;"><?= htmlspecialchars($s['title']) ?></div>
                                    </td>
                                    <td data-label="Category" style="font-size:13px;color:rgba(255,255,255,0.7);">
                                        <?= htmlspecialchars($s['category']) ?>
                                    </td>
                                    <td data-label="Action">
                                        <a href="apply_scheme.php?id=<?= $s['id'] ?>" class="btn btn-glass" style="padding:5px 12px;font-size:12px;">
                                            <i class="fas fa-paper-plane me-1"></i>Apply
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle" style="font-size:30px;color:rgba(255,255,255,0.2);margin-bottom:12px;display:block;"></i>
                        <p style="color:rgba(255,255,255,0.5);margin:0;font-size:14px;">No new schemes available to apply.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Summary -->
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <div class="text-center mb-4">
                    <div class="profile-avatar mx-auto mb-3" style="width:70px;height:70px;font-size:28px;border-radius:50%;background:linear-gradient(135deg,#6a11cb,#2575fc);">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <h6 style="color:#fff;font-weight:700;margin-bottom:2px;"><?= htmlspecialchars($user['name']) ?></h6>
                    <p style="color:rgba(255,255,255,0.5);font-size:13px;margin:0;"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="status-badge status-approved mt-2">
                        <?= htmlspecialchars($user['category']) ?>
                    </span>
                </div>

                <!-- Profile Completeness -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:13px;color:rgba(255,255,255,0.7);">Profile Completeness</span>
                        <span id="profileProgressLabel" style="font-size:13px;color:#fff;font-weight:600;">0%</span>
                    </div>
                    <div class="progress-glass">
                        <div class="progress-bar profile-progress-bar" data-progress="<?= $profile_pct ?>" style="width:0%;"></div>
                    </div>
                    <?php if ($profile_pct < 80): ?>
                    <p style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:4px;">
                        <i class="fas fa-info-circle me-1"></i>Complete your profile to improve eligibility chances
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Profile Details -->
                <div style="font-size:13px;">
                    <?php $details = [
                        ['fas fa-phone', 'Phone', $user['phone']],
                        ['fas fa-calendar', 'DOB', $user['dob'] ? date('d M Y', strtotime($user['dob'])) : 'Not set'],
                        ['fas fa-rupee-sign', 'Income', $user['annual_income'] ? '₹' . number_format($user['annual_income']) : 'Not set'],
                    ]; ?>
                    <?php foreach ($details as [$icon, $label, $val]): ?>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.07);">
                        <span style="color:rgba(255,255,255,0.5);"><i class="<?= $icon ?> me-2"></i><?= $label ?></span>
                        <span style="color:#fff;"><?= htmlspecialchars($val ?: 'Not set') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <a href="profile.php" class="btn btn-glass-outline w-100 mt-3">
                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
