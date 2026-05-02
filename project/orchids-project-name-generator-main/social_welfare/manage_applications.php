<?php
/**
 * Manage Applications (Admin) - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();

$page_title = 'Manage Applications';
$uid = $_SESSION['user_id'];

if (isset($_GET['ajax_mark_read'])) {
    db_query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);
    echo json_encode(['ok'=>true]); exit;
}

$success = $error = '';

// ---- Handle status update ----
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

// Filters
$filter_status = sanitize($_GET['status'] ?? '');
$filter_search = sanitize($_GET['search'] ?? '');

// Build query
$where = "WHERE 1=1";
$params = [];

if ($filter_status) {
    $where .= " AND a.status = ?";
    $params[] = $filter_status;
}

if ($filter_search) {
    $where .= " AND (a.application_id LIKE ? OR u.name LIKE ? OR s.title LIKE ?)";
    $like = "%$filter_search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql = "SELECT a.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone, u.category, s.title AS scheme_title, s.category AS scheme_cat
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN schemes s ON a.scheme_id = s.id
        $where
        ORDER BY a.applied_at DESC";

$applications = db_query($sql, $params);

// Count by status
$counts = ['all'=>0,'pending'=>0,'under_review'=>0,'approved'=>0,'rejected'=>0];
$count_res = db_query("SELECT status, COUNT(*) as cnt FROM applications GROUP BY status");
$counts['all'] = db_fetch("SELECT COUNT(*) as cnt FROM applications")['cnt'];
foreach ($count_res as $cr) {
    $counts[$cr['status']] = $cr['cnt'];
}

if (!isset($_GET['embed'])) {
    include 'includes/header.php';
}
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            Manage Applications
            <small>Review, approve, or reject welfare scheme applications</small>
        </div>
    </div>

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

    <!-- Status Filter Tabs -->
    <div class="d-flex gap-2 flex-wrap mb-4">
        <?php $tabs = [
            ['', 'All', $counts['all'], ''],
            ['pending', 'Pending', $counts['pending'], '#ffc107'],
            ['under_review', 'Under Review', $counts['under_review'], '#17a2b8'],
            ['approved', 'Approved', $counts['approved'], '#28a745'],
            ['rejected', 'Rejected', $counts['rejected'], '#dc3545'],
        ]; ?>
        <?php foreach ($tabs as [$val, $label, $count, $color]): ?>
        <a href="?status=<?= $val ?>&search=<?= urlencode($filter_search) ?>"
            class="btn <?= $filter_status === $val ? 'btn-glass' : 'btn-glass-outline' ?>"
            style="font-size:13px;padding:7px 16px;">
            <?= $label ?>
            <span style="background:rgba(255,255,255,0.15);padding:2px 7px;border-radius:10px;font-size:11px;margin-left:6px;">
                <?= $count ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search Bar -->
    <div class="filter-bar mb-4">
        <form method="GET" class="d-flex gap-3 w-100 flex-wrap" id="filterForm">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
            <div class="search-wrap flex-grow-1">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" name="search"
                    placeholder="Search by App ID, Applicant, or Scheme..."
                    value="<?= htmlspecialchars($filter_search) ?>">
            </div>
            <button type="submit" class="btn btn-glass">
                <i class="fas fa-search me-2"></i>Search
            </button>
            <?php if ($filter_search): ?>
            <a href="?status=<?= $filter_status ?>" class="btn btn-glass-outline">
                <i class="fas fa-times me-2"></i>Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="glass-card p-0">
        <div class="p-4">
            <?php if (empty($applications)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox" style="font-size:40px;color:rgba(255,255,255,0.15);display:block;margin-bottom:12px;"></i>
                <p style="color:rgba(255,255,255,0.4);margin:0;">No applications found.</p>
            </div>
            <?php else: ?>
            <div class="responsive-table">
                <table class="glass-table">
                    <thead>
                        <tr>
                            <th>App ID</th>
                            <th>Applicant</th>
                            <th>Scheme</th>
                            <th>Applied</th>
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
                                <div style="font-weight:500;color:#fff;"><?= htmlspecialchars($app['user_name']) ?></div>
                                <div style="font-size:11px;color:rgba(255,255,255,0.4);"><?= htmlspecialchars($app['user_email']) ?></div>
                                <div style="font-size:11px;color:rgba(255,255,255,0.3);"><?= htmlspecialchars($app['user_phone']) ?> · <?= $app['category'] ?></div>
                            </td>
                            <td data-label="Scheme">
                                <div style="font-size:13px;color:#fff;"><?= htmlspecialchars($app['scheme_title']) ?></div>
                                <div style="font-size:11px;color:rgba(255,255,255,0.4);"><?= $app['scheme_cat'] ?></div>
                            </td>
                            <td data-label="Applied" style="font-size:13px;color:rgba(255,255,255,0.6);">
                                <?= date('d M Y', strtotime($app['applied_at'])) ?>
                            </td>
                            <td data-label="Status">
                                <span class="status-badge status-<?= $app['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                                </span>
                                <?php if ($app['remarks']): ?>
                                <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:4px;" title="<?= htmlspecialchars($app['remarks']) ?>">
                                    <i class="fas fa-comment me-1"></i><?= htmlspecialchars(substr($app['remarks'], 0, 30)) ?>...
                                </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Action">
                                <button class="btn btn-glass" style="padding:5px 12px;font-size:12px;"
                                    onclick="openReview(<?= $app['id'] ?>, '<?= htmlspecialchars($app['application_id']) ?>', '<?= htmlspecialchars(addslashes($app['user_name'])) ?>', '<?= htmlspecialchars(addslashes($app['scheme_title'])) ?>', '<?= $app['status'] ?>', '<?= htmlspecialchars(addslashes($app['remarks'] ?? '')) ?>')">
                                    <i class="fas fa-gavel me-1"></i>Review
                                </button>
                                <?php if ($app['documents']): ?>
                                <a href="uploads/<?= htmlspecialchars($app['documents']) ?>" target="_blank"
                                    class="btn btn-glass-outline" style="padding:5px 10px;font-size:12px;margin-top:4px;">
                                    <i class="fas fa-file-download"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
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

<?php
if (!isset($_GET['embed'])) {
    include 'includes/footer.php';
}
?>

