<?php
/**
 * Admin Dashboard - Social Welfare Scheme Management System
 * Enhanced with full management capabilities
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();

$page_title = 'Admin Dashboard';
$uid = $_SESSION['user_id'];

if (isset($_GET['ajax_mark_read'])) {
    db_query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);
    echo json_encode(['ok'=>true]); exit;
}

// ---- Handle status update ----
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = (int)$_POST['app_id'];
    $new_status = sanitize($_POST['status']);
    $remarks = sanitize($_POST['remarks'] ?? '');

    $allowed = ['pending','under_review','approved','rejected'];
    if (in_array($new_status, $allowed)) {
        $result = db_query("UPDATE applications SET status=?, remarks=? WHERE id=?", [$new_status, $remarks, $app_id]);
        if ($result) {
            // Notify user
            $app_info = db_fetch("SELECT a.user_id, a.application_id, s.title FROM applications a JOIN schemes s ON a.scheme_id=s.id WHERE a.id=?", [$app_id]);

            if ($app_info) {
                $status_label = ucfirst(str_replace('_', ' ', $new_status));
                $msg = "Your application {$app_info['application_id']} for \"{$app_info['title']}\" status has been updated to: {$status_label}.";
                if ($remarks) $msg .= " Remarks: $remarks";
                db_query("INSERT INTO notifications (user_id, message) VALUES (?, ?)", [$app_info['user_id'], $msg]);
            }

            $success = 'Application status updated successfully.';
        } else {
            $error = 'Failed to update status.';
        }
    }
}

// ---- Analytics Data ----
$total_users = db_fetch("SELECT COUNT(*) as cnt FROM users WHERE role='user'")['cnt'];
$total_schemes = db_fetch("SELECT COUNT(*) as cnt FROM schemes WHERE status='active'")['cnt'];
$total_apps = db_fetch("SELECT COUNT(*) as cnt FROM applications")['cnt'];
$approved_apps = db_fetch("SELECT COUNT(*) as cnt FROM applications WHERE status='approved'")['cnt'];
$pending_apps = db_fetch("SELECT COUNT(*) as cnt FROM applications WHERE status='pending'")['cnt'];
$rejected_apps = db_fetch("SELECT COUNT(*) as cnt FROM applications WHERE status='rejected'")['cnt'];
$under_review = db_fetch("SELECT COUNT(*) as cnt FROM applications WHERE status='under_review'")['cnt'];

$approval_rate = $total_apps > 0 ? round(($approved_apps / $total_apps) * 100) : 0;

// Recent applications
$applications = db_query("
    SELECT a.*, u.name AS user_name, s.title AS scheme_title
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN schemes s ON a.scheme_id = s.id
    ORDER BY a.applied_at DESC
    LIMIT 10
");
while ($row = db_fetch($result)) {
    $applications[] = $row;
}

// Recent users
$result = db_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 3");
$recent_users = [];
while ($row = db_fetch($result)) {
    $recent_users[] = $row;
}

// Category distribution
$categories = db_query("
    SELECT s.category, COUNT(a.id) as cnt
    FROM applications a
    JOIN schemes s ON a.scheme_id = s.id
    GROUP BY s.category
    ORDER BY cnt DESC
");

// Get recent schemes
$recent_schemes = db_query("SELECT * FROM schemes ORDER BY created_at DESC LIMIT 3");

include 'includes/header.php';
?>

<div class="fade-in">
    <!-- Welcome Banner -->
    <div class="glass-card p-4 mb-4" style="background:linear-gradient(135deg,rgba(0,74,173,0.4),rgba(106,17,203,0.4));">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h2 style="color:#fff;font-weight:700;margin-bottom:4px;">
                    Admin Dashboard <i class="fas fa-shield-alt ms-2" style="font-size:20px;opacity:0.7;"></i>
                </h2>
                <p style="color:rgba(255,255,255,0.55);margin:0;font-size:14px;">
                    <i class="fas fa-calendar me-1"></i><?= date('l, d F Y') ?> &nbsp;·&nbsp;
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['name']) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="#applications" class="btn btn-glass">
                    <i class="fas fa-tasks me-2"></i>Applications
                </a>
                <a href="#schemes" class="btn btn-glass-outline">
                    <i class="fas fa-th-list me-2"></i>Schemes
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
    <div class="alert-glass success mb-4 auto-dismiss">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-glass error mb-4 auto-dismiss">
        <i class="fas fa-times-circle"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- Analytics Cards Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3 fade-in-up delay-1">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#004aad,#2575fc);">
                    <i class="fas fa-users text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $total_users ?>"><?= $total_users ?></div>
                <div class="card-label">Registered Users</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-2">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#6a11cb,#a78bfa);">
                    <i class="fas fa-list-alt text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $total_schemes ?>"><?= $total_schemes ?></div>
                <div class="card-label">Active Schemes</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-3">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#0077b6,#00b4d8);">
                    <i class="fas fa-file-alt text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $total_apps ?>"><?= $total_apps ?></div>
                <div class="card-label">Total Applications</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-4">
            <div class="analytics-card">
                <div class="card-icon" style="background:linear-gradient(135deg,#1e7e34,#28a745);">
                    <i class="fas fa-percent text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $approval_rate ?>" data-suffix="%"><?= $approval_rate ?>%</div>
                <div class="card-label">Approval Rate</div>
            </div>
        </div>
    </div>

    <!-- Analytics Cards Row 2 -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3 fade-in-up delay-1">
            <div class="analytics-card" style="border-color:rgba(255,193,7,0.3);">
                <div class="card-icon" style="background:linear-gradient(135deg,#b8860b,#ffc107);">
                    <i class="fas fa-clock text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $pending_apps ?>"><?= $pending_apps ?></div>
                <div class="card-label">Pending</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-2">
            <div class="analytics-card" style="border-color:rgba(23,162,184,0.3);">
                <div class="card-icon" style="background:linear-gradient(135deg,#117a8b,#17a2b8);">
                    <i class="fas fa-search text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $under_review ?>"><?= $under_review ?></div>
                <div class="card-label">Under Review</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-3">
            <div class="analytics-card" style="border-color:rgba(40,167,69,0.3);">
                <div class="card-icon" style="background:linear-gradient(135deg,#1e7e34,#28a745);">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $approved_apps ?>"><?= $approved_apps ?></div>
                <div class="card-label">Approved</div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-4">
            <div class="analytics-card" style="border-color:rgba(220,53,69,0.3);">
                <div class="card-icon" style="background:linear-gradient(135deg,#b21f2d,#dc3545);">
                    <i class="fas fa-times text-white"></i>
                </div>
                <div class="card-value counter-animate" data-target="<?= $rejected_apps ?>"><?= $rejected_apps ?></div>
                <div class="card-label">Rejected</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Applications & Management -->
        <div class="col-lg-8">
            <div id="applications" class="glass-card p-0 mb-4">
                <div class="d-flex align-items-center justify-content-between p-4 pb-0">
                    <h6 style="color:#fff;font-weight:700;font-size:16px;margin:0;">
                        <i class="fas fa-tasks me-2"></i>Recent Applications
                    </h6>
                    <a href="manage_applications.php" style="font-size:13px;color:#60a5fa;text-decoration:none;">
                        Manage All <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="p-4">
                    <div class="responsive-table">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>App ID</th>
                                    <th>Applicant</th>
                                    <th>Scheme</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td data-label="App ID">
                                        <span style="font-family:monospace;font-size:12px;color:#a78bfa;">
                                            <?= htmlspecialchars($app['application_id']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Applicant">
                                        <div style="color:#fff;"><?= htmlspecialchars($app['user_name']) ?></div>
                                        <div style="font-size:11px;color:rgba(255,255,255,0.4);"><?= htmlspecialchars($app['user_email']) ?></div>
                                    </td>
                                    <td data-label="Scheme" style="font-size:13px;color:rgba(255,255,255,0.7);">
                                        <?= htmlspecialchars(substr($app['scheme_title'], 0, 25)) ?>...
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge status-<?= $app['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                                        </span>
                                    </td>
                                    <td data-label="Action">
                                        <button class="btn btn-glass" style="padding:5px 12px;font-size:12px;"
                                            onclick="openReview(<?= $app['id'] ?>, '<?= htmlspecialchars($app['application_id']) ?>', '<?= htmlspecialchars(addslashes($app['user_name'])) ?>', '<?= htmlspecialchars(addslashes($app['scheme_title'])) ?>', '<?= $app['status'] ?>', '<?= htmlspecialchars(addslashes($app['remarks'] ?? '')) ?>')">
                                            <i class="fas fa-gavel me-1"></i>Review
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Add Scheme -->
            <div id="schemes" class="glass-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 style="color:#fff;font-weight:700;font-size:16px;margin:0;">
                        <i class="fas fa-plus-circle me-2"></i>Quick Add Scheme
                    </h6>
                    <a href="manage_schemes.php?action=add" style="font-size:13px;color:#60a5fa;text-decoration:none;">
                        Full Form <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>

                <form method="POST" action="manage_schemes.php" class="form-glass">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="title" placeholder="Scheme Title" required>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" name="category">
                                <option value="Other">Select Category</option>
                                <option value="Agriculture">Agriculture</option>
                                <option value="Education">Education</option>
                                <option value="Health">Health</option>
                                <option value="Housing">Housing</option>
                                <option value="Employment">Employment</option>
                                <option value="Women">Women</option>
                                <option value="Elderly">Elderly</option>
                                <option value="Disability">Disability</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <textarea class="form-control" name="description" rows="2" placeholder="Brief description" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-glass">
                                <i class="fas fa-plus me-2"></i>Add Scheme
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Applications by Category -->
            <div class="glass-card p-4 mb-4">
                <h6 style="color:#fff;font-weight:700;font-size:15px;margin-bottom:16px;">
                    <i class="fas fa-chart-pie me-2"></i>By Category
                </h6>
                <?php if (empty($categories)): ?>
                <p style="color:rgba(255,255,255,0.4);font-size:13px;">No data yet.</p>
                <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:13px;color:rgba(255,255,255,0.8);"><?= htmlspecialchars($cat['category']) ?></span>
                        <span style="font-size:13px;color:#fff;font-weight:600;"><?= $cat['cnt'] ?></span>
                    </div>
                    <div class="progress-glass">
                        <div class="progress-bar" style="width:<?= $total_apps > 0 ? round(($cat['cnt']/$total_apps)*100) : 0 ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Users -->
            <div class="glass-card p-4 mb-4">
                <h6 style="color:#fff;font-weight:700;font-size:15px;margin-bottom:16px;">
                    <i class="fas fa-user-plus me-2"></i>New Users
                </h6>
                <?php foreach ($recent_users as $ru): ?>
                <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div class="profile-avatar" style="width:36px;height:36px;font-size:14px;border-radius:50%;">
                        <?= strtoupper(substr($ru['name'], 0, 1)) ?>
                    </div>
                    <div style="flex:1;overflow:hidden;">
                        <div style="font-size:13px;color:#fff;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($ru['name']) ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.4);"><?= date('d M Y', strtotime($ru['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Schemes -->
            <div class="glass-card p-4">
                <h6 style="color:#fff;font-weight:700;font-size:15px;margin-bottom:16px;">
                    <i class="fas fa-th-list me-2"></i>Recent Schemes
                </h6>
                <?php foreach ($recent_schemes as $rs): ?>
                <div class="mb-3 pb-3" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div style="font-size:13px;color:#fff;font-weight:500;margin-bottom:4px;"><?= htmlspecialchars($rs['title']) ?></div>
                    <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-bottom:6px;"><?= htmlspecialchars($rs['category']) ?></div>
                    <span class="status-badge <?= $rs['status'] === 'active' ? 'status-approved' : 'status-rejected' ?>" style="font-size:10px;">
                        <?= ucfirst($rs['status']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade modal-glass" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-gavel me-2"></i>Review Application
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="form-glass">
                <div class="modal-body" style="padding:24px;">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="app_id" id="modal_app_id">

                    <!-- App info display -->
                    <div class="p-3 mb-4" style="background:rgba(255,255,255,0.07);border-radius:10px;">
                        <div class="row g-2 font-size-sm">
                            <div class="col-6">
                                <div style="font-size:11px;color:rgba(255,255,255,0.4);">Application ID</div>
                                <div id="modal_app_ref" style="font-family:monospace;color:#a78bfa;font-weight:700;"></div>
                            </div>
                            <div class="col-6">
                                <div style="font-size:11px;color:rgba(255,255,255,0.4);">Applicant</div>
                                <div id="modal_user_name" style="color:#fff;font-weight:500;font-size:14px;"></div>
                            </div>
                            <div class="col-12 mt-2">
                                <div style="font-size:11px;color:rgba(255,255,255,0.4);">Scheme</div>
                                <div id="modal_scheme" style="color:rgba(255,255,255,0.8);font-size:13px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Update Status</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php foreach (['pending'=>['#ffc107','fas fa-clock'],'under_review'=>['#17a2b8','fas fa-search'],'approved'=>['#28a745','fas fa-check-circle'],'rejected'=>['#dc3545','fas fa-times-circle']] as $s=>[$color,$icon]): ?>
                            <label class="d-flex align-items-center gap-2" style="cursor:pointer;padding:10px 16px;border-radius:10px;border:1px solid rgba(255,255,255,0.15);transition:all 0.3s;">
                                <input type="radio" name="status" value="<?= $s ?>" style="accent-color:<?= $color ?>;">
                                <i class="<?= $icon ?>" style="color:<?= $color ?>;"></i>
                                <span style="font-size:14px;color:#fff;"><?= ucfirst(str_replace('_',' ',$s)) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admin Remarks <span style="color:rgba(255,255,255,0.4);font-weight:400;">(optional)</span></label>
                        <textarea class="form-control" name="remarks" id="modal_remarks" rows="3"
                            placeholder="Add a note or reason for the decision..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-glass-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-glass">
                        <i class="fas fa-save me-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReview(id, appRef, userName, scheme, currentStatus, remarks) {
    document.getElementById('modal_app_id').value = id;
    document.getElementById('modal_app_ref').textContent = appRef;
    document.getElementById('modal_user_name').textContent = userName;
    document.getElementById('modal_scheme').textContent = scheme;
    document.getElementById('modal_remarks').value = remarks;

    // Select current status radio
    document.querySelectorAll('input[name="status"]').forEach(r => {
        r.checked = (r.value === currentStatus);
    });

    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
