<?php
/**
 * Admin Users Management
 * - Create new admin accounts from UI
 * - Activate/deactivate existing admin accounts
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();

$page_title = 'Admin Users';
$uid = (int)$_SESSION['user_id'];
$success = '';
$error = '';
$fieldErrors = [];

if (isset($_GET['ajax_mark_read'])) {
    db_query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);
    echo json_encode(['ok' => true]);
    exit;
}

if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    if ($targetId === $uid) {
        $error = 'You cannot deactivate your own admin account.';
    } else {
        db_query("UPDATE users SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=? AND role='admin'", [$targetId]);
        $success = 'Admin status updated.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $annualIncomeRaw = trim($_POST['annual_income'] ?? '');
    $annualIncome = is_numeric($annualIncomeRaw) ? (float)$annualIncomeRaw : 0;
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[A-Za-z ]{2,}$/', $name)) {
        $fieldErrors['name'] = 'Name must be at least 2 letters (alphabets/spaces only).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Please enter a valid email.';
    }
    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $fieldErrors['phone'] = 'Phone must be 10 digits and start with 6-9.';
    }
    if (empty($dob)) {
        $fieldErrors['dob'] = 'Date of birth is required.';
    }
    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $fieldErrors['gender'] = 'Please select a valid gender.';
    }
    if (mb_strlen(trim($address)) < 10) {
        $fieldErrors['address'] = 'Address must be at least 10 characters.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $fieldErrors['password'] = 'Password must be 8+ chars with at least 1 uppercase and 1 number.';
    }
    if ($password !== $confirmPassword) {
        $fieldErrors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($fieldErrors)) {
        $existing = db_fetch("SELECT id FROM users WHERE email=?", [$email]);
        if ($existing) {
            $fieldErrors['email'] = 'Email already exists.';
        }
    }

    if (empty($fieldErrors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ok = db_query(
            "INSERT INTO users (name, email, phone, password, dob, gender, address, category, annual_income, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin', 1)",
            [$name, $email, $phone, $hash, $dob, $gender, $address, $category, $annualIncome]
        );
        if ($ok) {
            $success = 'New admin account created successfully.';
            $_POST = [];
        } else {
            $error = 'Failed to create admin account.';
        }
    } else {
        $error = 'Please fix the highlighted fields.';
    }
}

$adminsResult = db_query("SELECT id, name, email, phone, category, is_active, created_at FROM users WHERE role='admin' ORDER BY created_at DESC");

include 'includes/header.php';
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            Admin Users
            <small>Create and manage admin credentials</small>
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
        <i class="fas fa-exclamation-circle"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="glass-card p-4">
                <h6 style="color:rgba(255,255,255,0.6);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:18px;">
                    <i class="fas fa-user-shield me-2"></i>Create Admin
                </h6>
                <form method="POST" class="form-glass">
                    <input type="hidden" name="create_admin" value="1">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control <?= isset($fieldErrors['name']) ? 'is-invalid' : '' ?>" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['name'] ?? '') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control <?= isset($fieldErrors['email']) ? 'is-invalid' : '' ?>" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['email'] ?? '') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control <?= isset($fieldErrors['phone']) ? 'is-invalid' : '' ?>" name="phone" maxlength="10" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['phone'] ?? '') ?></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">DOB</label>
                            <input type="date" class="form-control <?= isset($fieldErrors['dob']) ? 'is-invalid' : '' ?>" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" required>
                            <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['dob'] ?? '') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select <?= isset($fieldErrors['gender']) ? 'is-invalid' : '' ?>" name="gender" required>
                                <option value="">Select</option>
                                <?php foreach (['Male','Female','Other'] as $g): ?>
                                <option value="<?= $g ?>" <?= (($_POST['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['gender'] ?? '') ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control <?= isset($fieldErrors['address']) ? 'is-invalid' : '' ?>" name="address" rows="2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['address'] ?? '') ?></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <?php foreach (['General','OBC','SC','ST','EWS'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= (($_POST['category'] ?? 'General') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Annual Income</label>
                            <input type="number" class="form-control" name="annual_income" value="<?= htmlspecialchars($_POST['annual_income'] ?? '0') ?>" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control <?= isset($fieldErrors['password']) ? 'is-invalid' : '' ?>" name="password" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['password'] ?? '') ?></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control <?= isset($fieldErrors['confirm_password']) ? 'is-invalid' : '' ?>" name="confirm_password" required>
                        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['confirm_password'] ?? '') ?></div>
                    </div>

                    <button type="submit" class="btn btn-glass w-100">
                        <i class="fas fa-user-plus me-2"></i>Create Admin
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="glass-card p-0">
                <div class="p-4 pb-0">
                    <h6 style="color:#fff;font-weight:700;">Existing Admin Accounts</h6>
                </div>
                <div class="p-4">
                    <div class="responsive-table">
                        <table class="glass-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($adminsResult): while ($admin = db_fetch($adminsResult)): ?>
                                <tr>
                                    <td data-label="Name"><?= htmlspecialchars($admin['name']) ?></td>
                                    <td data-label="Email"><?= htmlspecialchars($admin['email']) ?></td>
                                    <td data-label="Phone"><?= htmlspecialchars($admin['phone']) ?></td>
                                    <td data-label="Status">
                                        <span class="status-badge <?= (int)$admin['is_active'] === 1 ? 'status-approved' : 'status-rejected' ?>">
                                            <?= (int)$admin['is_active'] === 1 ? 'Active' : 'Disabled' ?>
                                        </span>
                                    </td>
                                    <td data-label="Action">
                                        <?php if ((int)$admin['id'] === $uid): ?>
                                            <span style="font-size:12px;color:rgba(255,255,255,0.45);">Current account</span>
                                        <?php else: ?>
                                            <a href="?toggle=1&id=<?= (int)$admin['id'] ?>" class="btn btn-glass-outline" style="padding:6px 12px;font-size:12px;">
                                                <i class="fas fa-power-off me-1"></i><?= (int)$admin['is_active'] === 1 ? 'Disable' : 'Enable' ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

