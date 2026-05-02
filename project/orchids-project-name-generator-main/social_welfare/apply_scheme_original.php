<?php
/**
 * Scheme Details + Apply
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
requireUser();

$page_title = 'Scheme Details';
$uid = (int)$_SESSION['user_id'];

$scheme_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$view_only = isset($_GET['view']) && $_GET['view'] == '1';

if ($scheme_id <= 0) {
    header('Location: schemes.php?msg=Invalid+scheme');
    exit;
}

$scheme = db_fetch("SELECT * FROM schemes WHERE id=? AND status='active'", [$scheme_id]);
if (!$scheme) {
    header('Location: schemes.php?msg=Scheme+not+found');
    exit;
}

// prevent duplicates
$existing = db_fetch("SELECT application_id, status FROM applications WHERE user_id=? AND scheme_id=? LIMIT 1", [$uid, $scheme_id]);

$error = '';
$success = '';
if (isset($_GET['msg'])) $success = sanitize($_GET['msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply']) && !$view_only) {
    if ($existing) {
        header('Location: track_application.php?id=' . urlencode($existing['application_id']));
        exit;
    }

    $app_id = generateAppID($conn);
    $ok = db_query(
        "INSERT INTO applications (application_id, user_id, scheme_id, status, applied_at) VALUES (?, ?, ?, 'pending', NOW())",
        [$app_id, $uid, $scheme_id]
    );

    if ($ok) {
        // small user notification
        db_query("INSERT INTO notifications (user_id, message) VALUES (?, ?)", [$uid, "Application submitted successfully. Your Application ID is {$app_id}."]);
        header('Location: track_application.php?id=' . urlencode($app_id));
        exit;
    }

    $error = 'Unable to submit application. Please try again.';
}

include __DIR__ . '/includes/header.php';
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            <?= htmlspecialchars($scheme['title']) ?>
            <small><?= htmlspecialchars($scheme['category']) ?></small>
        </div>
        <a href="schemes.php" class="btn btn-glass-outline">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <?php if ($error): ?>
    <div class="alert-glass error mb-4 auto-dismiss">
        <i class="fas fa-times-circle"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert-glass success mb-4 auto-dismiss">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <div class="glass-card p-4 mb-4">
        <div class="row g-4">
            <div class="col-lg-8">
                <h6 style="color:#fff;font-weight:700;">Description</h6>
                <p style="color:rgba(255,255,255,0.75);"><?= nl2br(htmlspecialchars($scheme['description'])) ?></p>

                <h6 style="color:#fff;font-weight:700;">Eligibility</h6>
                <p style="color:rgba(255,255,255,0.75);"><?= nl2br(htmlspecialchars($scheme['eligibility'])) ?></p>

                <h6 style="color:#fff;font-weight:700;">Benefits</h6>
                <p style="color:rgba(255,255,255,0.75);"><?= nl2br(htmlspecialchars($scheme['benefits'])) ?></p>

                <?php if (!empty($scheme['required_documents'])): ?>
                <h6 style="color:#fff;font-weight:700;">Required Documents</h6>
                <p style="color:rgba(255,255,255,0.75);"><?= nl2br(htmlspecialchars($scheme['required_documents'])) ?></p>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="glass-card p-4" style="background:rgba(255,255,255,0.04);">
                    <div style="font-size:12px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.6px;">Application</div>

                    <div class="mt-3" style="font-size:13px;color:rgba(255,255,255,0.75);">
                        <?php if ($scheme['last_date']): ?>
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.07);">
                            <span><i class="fas fa-calendar-times me-2"></i>Last date</span>
                            <span style="color:#fff;"><?= date('d M Y', strtotime($scheme['last_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.07);">
                            <span><i class="fas fa-rupee-sign me-2"></i>Max income</span>
                            <span style="color:#fff;">₹<?= number_format((float)$scheme['max_income']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span><i class="fas fa-user-clock me-2"></i>Age</span>
                            <span style="color:#fff;"><?= (int)$scheme['min_age'] ?> - <?= (int)$scheme['max_age'] ?></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <?php if ($existing): ?>
                            <a class="btn btn-success-glass w-100" href="track_application.php?id=<?= urlencode($existing['application_id']) ?>">
                                <i class="fas fa-eye me-2"></i>Already Applied (Track)
                            </a>
                        <?php elseif ($view_only): ?>
                            <a class="btn btn-glass w-100" href="apply_scheme.php?id=<?= $scheme_id ?>">
                                <i class="fas fa-paper-plane me-2"></i>Proceed to Apply
                            </a>
                        <?php else: ?>
                            <form method="POST">
                                <button class="btn btn-glass w-100 py-3" type="submit" name="apply" value="1">
                                    <i class="fas fa-paper-plane me-2"></i>Apply Now
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
