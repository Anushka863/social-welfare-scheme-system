<?php
/**
 * Profile Page - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$page_title = 'My Profile';
$uid = $_SESSION['user_id'];

if (isset($_GET['ajax_mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    echo json_encode(['ok'=>true]); exit;
}

$success = '';
$error = '';
$fieldErrors = [];

// Fetch current user
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $annual_income_raw = trim($_POST['annual_income'] ?? '');
    $annual_income = null;
    $aadhar = sanitize($_POST['aadhar'] ?? '');

    $errors = [];

    if (!preg_match('/^[A-Za-z ]+$/', $name) || strlen($name) < 2) {
        $fieldErrors['name'] = 'Name must contain only alphabets and spaces (min 2 characters).';
    }

    // Indian mobile: exactly 10 digits, starts with 6/7/8/9
    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $fieldErrors['phone'] = 'Phone must be exactly 10 digits and start with 6, 7, 8, or 9.';
    }

    // DOB: required, valid, past, and 18+ (born in or before 2007)
    if (empty($dob)) {
        $fieldErrors['dob'] = 'Date of birth is required.';
    } else {
        $dobDt = DateTime::createFromFormat('Y-m-d', $dob);
        $dobValid = $dobDt && $dobDt->format('Y-m-d') === $dob;
        if (!$dobValid) {
            $fieldErrors['dob'] = 'Please enter a valid date of birth.';
        } else {
            $today = new DateTime('today');
            if ($dobDt > $today) {
                $fieldErrors['dob'] = 'Date of birth must be a past date.';
            } else {
                $age = $today->diff($dobDt)->y;
                if ((int)$dobDt->format('Y') > 2007) {
                    $fieldErrors['dob'] = 'You must be born in or before 2007 (minimum age 18).';
                } elseif ($age < 18) {
                    $fieldErrors['dob'] = 'You must be at least 18 years old.';
                }
            }
        }
    }

    // Annual income: required, numeric, min 50000
    if ($annual_income_raw === '') {
        $fieldErrors['annual_income'] = 'Annual income is required.';
    } elseif (!is_numeric($annual_income_raw)) {
        $fieldErrors['annual_income'] = 'Annual income must be a numeric value.';
    } else {
        $annual_income = (float)$annual_income_raw;
        if ($annual_income < 50000) {
            $fieldErrors['annual_income'] = 'Annual income must be at least ₹50000.';
        }
    }

    // Address: required, min 10 chars
    if (trim($address) === '') {
        $fieldErrors['address'] = 'Address is required.';
    } elseif (mb_strlen(trim($address)) < 10) {
        $fieldErrors['address'] = 'Address must be at least 10 characters.';
    }

    if ($aadhar && !preg_match('/^[0-9]{12}$/', $aadhar)) {
        $fieldErrors['aadhar'] = 'Aadhar must be exactly 12 digits.';
    }

    // Handle password change
    $password_changed = false;
    if (!empty($_POST['new_password'])) {
        $current_pw = $_POST['current_password'] ?? '';
        $new_pw = $_POST['new_password'];
        $confirm_pw = $_POST['confirm_new_password'] ?? '';

        if (!password_verify($current_pw, $user['password'])) {
            $fieldErrors['current_password'] = 'Current password is incorrect.';
        } elseif (strlen($new_pw) < 8 || !preg_match('/[A-Z]/', $new_pw) || !preg_match('/[0-9]/', $new_pw)) {
            $fieldErrors['new_password'] = 'New password must be 8+ chars, 1 uppercase, 1 number.';
        } elseif ($new_pw !== $confirm_pw) {
            $fieldErrors['confirm_new_password'] = 'New passwords do not match.';
        } else {
            $password_changed = true;
        }
    }

    if (empty($fieldErrors)) {
        if ($password_changed) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?,phone=?,dob=?,gender=?,address=?,category=?,annual_income=?,aadhar=?,password=? WHERE id=?");
            $stmt->bind_param("ssssssdssi", $name,$phone,$dob,$gender,$address,$category,$annual_income,$aadhar,$hashed,$uid);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?,phone=?,dob=?,gender=?,address=?,category=?,annual_income=?,aadhar=? WHERE id=?");
            $stmt->bind_param("ssssssdsi", $name,$phone,$dob,$gender,$address,$category,$annual_income,$aadhar,$uid);
        }

        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
            // Reload user
            $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update profile.';
        }
    } else {
        $error = 'Please correct the highlighted fields and try again.';
    }
}

// Profile completeness
$fields = ['name','email','phone','dob','gender','address','aadhar','annual_income','category','profile_photo'];
$filled = 0;
foreach ($fields as $f) if (!empty($user[$f])) $filled++;
$profile_pct = (int)(($filled / count($fields)) * 100);

// Age calculation
$user_age = 0;
if ($user['dob']) {
    $diff = (new DateTime())->diff(new DateTime($user['dob']));
    $user_age = $diff->y;
}

include 'includes/header.php';
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            My Profile
            <small>Manage your personal information and account settings</small>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert-glass success mb-4 auto-dismiss">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-glass error mb-4">
        <i class="fas fa-exclamation-circle flex-shrink-0"></i>
        <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Summary Card -->
        <div class="col-lg-3">
            <div class="glass-card p-4 text-center">
                <div class="profile-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:32px;border-radius:50%;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h5 style="color:#fff;font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($user['name']) ?></h5>
                <p style="color:rgba(255,255,255,0.5);font-size:13px;margin-bottom:4px;"><?= htmlspecialchars($user['email']) ?></p>
                <span class="status-badge status-approved"><?= htmlspecialchars($user['category']) ?></span>
                <?php if ($user['role'] === 'admin'): ?>
                <span class="status-badge status-under_review ms-1">Admin</span>
                <?php endif; ?>

                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:13px;color:rgba(255,255,255,0.6);">Profile Complete</span>
                        <span id="profileProgressLabel" style="font-size:13px;color:#fff;font-weight:600;">0%</span>
                    </div>
                    <div class="progress-glass">
                        <div class="progress-bar profile-progress-bar" data-progress="<?= $profile_pct ?>" style="width:0%;"></div>
                    </div>
                </div>

                <div class="mt-4 text-start" style="font-size:13px;">
                    <?php $quick = [
                        ['fas fa-phone','Phone', $user['phone']],
                        ['fas fa-birthday-cake','Age', $user_age . ' years'],
                        ['fas fa-venus-mars','Gender', $user['gender']],
                        ['fas fa-rupee-sign','Income', '₹' . number_format($user['annual_income']) . '/yr'],
                    ]; ?>
                    <?php foreach ($quick as [$icon, $label, $val]): ?>
                    <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <span style="color:rgba(255,255,255,0.45);"><i class="<?= $icon ?> me-1"></i><?= $label ?></span>
                        <span style="color:#fff;"><?= htmlspecialchars($val ?: '—') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-lg-9">
            <div class="glass-card p-4">
                <!-- Tabs -->
                <ul class="nav mb-4 gap-2" id="profileTabs">
                    <li class="nav-item">
                        <a class="btn btn-glass active" href="#" onclick="showTab('personal', this)">
                            <i class="fas fa-user me-2"></i>Personal Info
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-glass-outline" href="#" onclick="showTab('security', this)">
                            <i class="fas fa-lock me-2"></i>Security
                        </a>
                    </li>
                </ul>

                <form method="POST" class="form-glass" id="profileForm">
                    <!-- Personal Info Tab -->
                    <div id="tab-personal">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-user me-1"></i> Full Name *</label>
                                <input type="text" class="form-control <?= isset($fieldErrors['name']) ? 'is-invalid' : '' ?>" name="name"
                                    value="<?= htmlspecialchars($user['name']) ?>" required>
                                <div class="invalid-feedback"><?= isset($fieldErrors['name']) ? htmlspecialchars($fieldErrors['name']) : '' ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                    style="opacity:0.5;cursor:not-allowed;">
                                <div style="font-size:11px;color:rgba(255,255,255,0.3);margin-top:3px;">Email cannot be changed.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-phone me-1"></i> Phone *</label>
                                <input type="text" class="form-control <?= isset($fieldErrors['phone']) ? 'is-invalid' : '' ?>" name="phone"
                                    value="<?= htmlspecialchars($user['phone']) ?>" inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" required>
                                <div class="invalid-feedback"><?= isset($fieldErrors['phone']) ? htmlspecialchars($fieldErrors['phone']) : '' ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-calendar me-1"></i> Date of Birth *</label>
                                <input type="date" class="form-control <?= isset($fieldErrors['dob']) ? 'is-invalid' : '' ?>" name="dob"
                                    value="<?= htmlspecialchars($user['dob']) ?>"
                                    max="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback"><?= isset($fieldErrors['dob']) ? htmlspecialchars($fieldErrors['dob']) : '' ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-venus-mars me-1"></i> Gender</label>
                                <select class="form-select" name="gender">
                                    <?php foreach (['Male','Female','Other'] as $g): ?>
                                    <option value="<?= $g ?>" <?= $user['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-layer-group me-1"></i> Category</label>
                                <select class="form-select" name="category">
                                    <?php foreach (['General','OBC','SC','ST','EWS'] as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $user['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-rupee-sign me-1"></i> Annual Income (₹)</label>
                                <input type="number" class="form-control <?= isset($fieldErrors['annual_income']) ? 'is-invalid' : '' ?>" name="annual_income"
                                    value="<?= (int)$user['annual_income'] ?>" min="50000" step="1" required>
                                <div class="invalid-feedback"><?= isset($fieldErrors['annual_income']) ? htmlspecialchars($fieldErrors['annual_income']) : '' ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-id-card me-1"></i> Aadhar Number</label>
                                <input type="text" class="form-control <?= isset($fieldErrors['aadhar']) ? 'is-invalid' : '' ?>" name="aadhar"
                                    value="<?= htmlspecialchars($user['aadhar'] ?? '') ?>"
                                    maxlength="12" placeholder="12-digit Aadhar number">
                                <div class="invalid-feedback"><?= isset($fieldErrors['aadhar']) ? htmlspecialchars($fieldErrors['aadhar']) : '' ?></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                                <textarea class="form-control <?= isset($fieldErrors['address']) ? 'is-invalid' : '' ?>" name="address" rows="3" minlength="10" required
                                    placeholder="Your complete address (min 10 characters)"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                <div class="invalid-feedback"><?= isset($fieldErrors['address']) ? htmlspecialchars($fieldErrors['address']) : '' ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Tab -->
                    <div id="tab-security" style="display:none;">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert-glass info mb-3">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Leave password fields empty if you don't want to change your password.</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><i class="fas fa-lock me-1"></i> Current Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control <?= isset($fieldErrors['current_password']) ? 'is-invalid' : '' ?>" name="current_password" placeholder="Enter current password">
                                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="invalid-feedback"><?= isset($fieldErrors['current_password']) ? htmlspecialchars($fieldErrors['current_password']) : '' ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-lock me-1"></i> New Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control <?= isset($fieldErrors['new_password']) ? 'is-invalid' : '' ?>" id="password" name="new_password"
                                        placeholder="New password (min 8 chars)">
                                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="invalid-feedback"><?= isset($fieldErrors['new_password']) ? htmlspecialchars($fieldErrors['new_password']) : '' ?></div>
                                <div class="mt-2">
                                    <div class="progress-glass">
                                        <div id="strengthBar" style="width:0%;height:100%;background:#dc3545;border-radius:10px;transition:all 0.3s;"></div>
                                    </div>
                                    <div id="strengthLabel" style="font-size:11px;color:rgba(255,255,255,0.5);margin-top:3px;"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-lock me-1"></i> Confirm New Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control <?= isset($fieldErrors['confirm_new_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_new_password" placeholder="Re-enter new password">
                                    <button type="button" class="password-toggle"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="invalid-feedback"><?= isset($fieldErrors['confirm_new_password']) ? htmlspecialchars($fieldErrors['confirm_new_password']) : '' ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-3 flex-wrap">
                        <button type="submit" class="btn btn-glass">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="<?= isAdmin() ? 'admin_portal.php' : 'dashboard.php' ?>" class="btn btn-glass-outline">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tab, el) {
    document.getElementById('tab-personal').style.display = 'none';
    document.getElementById('tab-security').style.display = 'none';
    document.getElementById('tab-' + tab).style.display = 'block';
    document.querySelectorAll('#profileTabs a').forEach(a => {
        a.className = 'btn btn-glass-outline';
    });
    el.className = 'btn btn-glass';
    return false;
}
</script>

<?php include 'includes/footer.php'; ?>
