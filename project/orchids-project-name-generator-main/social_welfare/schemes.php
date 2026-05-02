<?php
/**
 * Browse Schemes - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireUser();

$page_title = 'Browse Schemes';
$uid = $_SESSION['user_id'];

// Mark notifications read if requested
if (isset($_GET['ajax_mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
}

// Fetch user profile for eligibility comparison
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$user_age = 0;
if ($user['dob']) {
    $diff = (new DateTime())->diff(new DateTime($user['dob']));
    $user_age = $diff->y;
}

// Fetch all active schemes
$schemes = $conn->query("SELECT * FROM schemes WHERE status='active' ORDER BY created_at DESC");

// Get already applied scheme IDs for this user
$stmt = $conn->prepare("SELECT scheme_id FROM applications WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$applied_ids = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $applied_ids[] = $row['scheme_id'];

// Category colors
$cat_colors = [
    'Agriculture' => 'linear-gradient(135deg,#1e7e34,#28a745)',
    'Education' => 'linear-gradient(135deg,#0077b6,#00b4d8)',
    'Health' => 'linear-gradient(135deg,#b21f2d,#dc3545)',
    'Housing' => 'linear-gradient(135deg,#6a11cb,#2575fc)',
    'Employment' => 'linear-gradient(135deg,#b8860b,#ffc107)',
    'Women' => 'linear-gradient(135deg,#c2185b,#e91e63)',
    'Elderly' => 'linear-gradient(135deg,#5d4037,#795548)',
    'Disability' => 'linear-gradient(135deg,#37474f,#546e7a)',
    'Other' => 'linear-gradient(135deg,#4a148c,#7b1fa2)',
];

include 'includes/header.php';
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            Browse Schemes
            <small>Find government welfare schemes you are eligible for</small>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="filter-bar mb-4">
        <div class="search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" id="schemeSearch" placeholder="Search schemes by name...">
        </div>
        <select id="categoryFilter">
            <option value="">All Categories</option>
            <?php foreach (array_keys($cat_colors) as $cat): ?>
            <option value="<?= strtolower($cat) ?>"><?= $cat ?></option>
            <?php endforeach; ?>
        </select>
        <select id="eligibilityFilter" onchange="filterByEligibility(this.value)">
            <option value="">All Schemes</option>
            <option value="eligible">Eligible Only</option>
        </select>
    </div>

    <!-- Schemes Grid -->
    <div class="row g-4" id="schemesGrid">
        <?php
        $schemes_list = [];
        while ($scheme = $schemes->fetch_assoc()) {
            $schemes_list[] = $scheme;
        }

        if (empty($schemes_list)): ?>
        <div class="col-12">
            <div class="glass-card p-5 text-center">
                <i class="fas fa-list-alt" style="font-size:50px;color:rgba(255,255,255,0.2);display:block;margin-bottom:16px;"></i>
                <p style="color:rgba(255,255,255,0.5);">No active schemes available.</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($schemes_list as $scheme):
            // Auto eligibility check
            $eligible_cats = $scheme['eligible_categories'];
            $is_eligible = (
                $user_age >= $scheme['min_age'] &&
                $user_age <= $scheme['max_age'] &&
                $user['annual_income'] <= $scheme['max_income'] &&
                (strpos($eligible_cats, 'All') !== false || strpos($eligible_cats, $user['category']) !== false)
            );

            $already_applied = in_array($scheme['id'], $applied_ids);
            $bg = $cat_colors[$scheme['category']] ?? $cat_colors['Other'];
        ?>
        <div class="col-md-6 col-lg-4 scheme-card-wrap animate-on-scroll"
             data-title="<?= strtolower(htmlspecialchars($scheme['title'])) ?>"
             data-category="<?= strtolower(htmlspecialchars($scheme['category'])) ?>"
             data-eligible="<?= $is_eligible ? 'eligible' : 'not' ?>">
            <div class="scheme-card">
                <div class="scheme-card-badge" style="background:<?= $bg ?>;color:#fff;">
                    <i class="fas fa-tag"></i>
                    <?= htmlspecialchars($scheme['category']) ?>
                </div>

                <div class="scheme-card-title"><?= htmlspecialchars($scheme['title']) ?></div>
                <div class="scheme-card-desc"><?= htmlspecialchars($scheme['description']) ?></div>

                <div class="mb-3">
                    <div style="font-size:12px;color:rgba(255,255,255,0.6);margin-bottom:6px;">
                        <i class="fas fa-check me-1"></i><strong>Benefits:</strong> <?= htmlspecialchars(substr($scheme['benefits'], 0, 80)) ?>...
                    </div>
                    <?php if ($scheme['last_date']): ?>
                    <div style="font-size:12px;color:rgba(255,193,7,0.8);">
                        <i class="fas fa-calendar-times me-1"></i>Last Date: <?= date('d M Y', strtotime($scheme['last_date'])) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="scheme-card-footer">
                    <div class="scheme-eligible <?= $is_eligible ? 'yes' : 'no' ?>">
                        <i class="fas fa-<?= $is_eligible ? 'check-circle' : 'times-circle' ?>"></i>
                        <?= $is_eligible ? 'You are eligible' : 'Not eligible' ?>
                    </div>

                    <?php if ($already_applied): ?>
                    <a href="track_application.php" class="btn btn-success-glass">
                        <i class="fas fa-eye me-1"></i>Track
                    </a>
                    <?php elseif ($is_eligible): ?>
                    <a href="apply_scheme.php?id=<?= $scheme['id'] ?>" class="btn btn-glass" style="padding:7px 16px;font-size:13px;">
                        <i class="fas fa-paper-plane me-1"></i>Apply Now
                    </a>
                    <?php else: ?>
                    <a href="apply_scheme.php?id=<?= $scheme['id'] ?>&view=1" class="btn btn-glass-outline" style="padding:7px 16px;font-size:13px;">
                        <i class="fas fa-info me-1"></i>Details
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByEligibility(val) {
    document.querySelectorAll('.scheme-card-wrap').forEach(function(w) {
        if (!val) { w.style.display = ''; return; }
        w.style.display = (w.dataset.eligible === 'eligible') ? '' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
