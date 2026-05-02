<?php
/**
 * Registration Page - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_portal.php' : 'dashboard.php'));
    exit;
}

$error = '';
$success = '';
$errors = [];
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize inputs
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $register_role = sanitize($_POST['register_role'] ?? 'user');
    $annual_income_raw = trim($_POST['annual_income'] ?? '');
    $annual_income = null;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (!preg_match('/^[A-Za-z ]+$/', $name) || strlen($name) < 2) {
        $fieldErrors['name'] = 'Name must contain only alphabets and spaces (min 2 characters).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Please enter a valid email address (e.g., user@gmail.com).';
    }

    // Indian mobile: exactly 10 digits, starts with 6/7/8/9
    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $fieldErrors['phone'] = 'Phone number must be exactly 10 digits and start with 6, 7, 8, or 9.';
    }

    // DOB: must be valid, past, and 18+ (born in or before 2007)
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

    if (empty($gender)) {
        $fieldErrors['gender'] = 'Please select a gender.';
    }

    if (!in_array($register_role, ['user', 'admin'], true)) {
        $fieldErrors['register_role'] = 'Please choose a valid role (User or Admin).';
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

    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $fieldErrors['password'] = 'Password must be at least 8 characters with 1 uppercase letter and 1 number.';
    }

    if ($password !== $confirm_password) {
        $fieldErrors['confirm_password'] = 'Passwords do not match.';
    }

    // Check duplicate email
    if (empty($fieldErrors)) {
        $existing = db_fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $fieldErrors['email'] = 'An account with this email already exists.';
        }
    }

    // Register user
    if (empty($fieldErrors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $result = db_query("INSERT INTO users (name, email, phone, password, dob, gender, address, category, annual_income, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$name, $email, $phone, $hashed, $dob, $gender, $address, $category, $annual_income, $register_role]);

        if ($result) {
            $success = 'Registration successful! Please login.';
        } else {
            $error = 'Registration failed. Please try again.';
        }
    } else {
        $error = 'Please correct the highlighted fields and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Social Welfare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="bg-animated"></div>

<div class="auth-page py-5">
    <div class="d-flex justify-content-between align-items-center mb-4" style="max-width:600px;width:100%;margin:0 auto;padding:0 15px;">
        <a href="index.php" class="btn btn-glass-outline btn-sm">
            <i class="fas fa-home me-1"></i>Back to Home
        </a>
        <a href="login.php" class="btn btn-glass-outline btn-sm">
            <i class="fas fa-sign-in-alt me-1"></i>Login
        </a>
    </div>
    <div class="auth-card" style="max-width:600px;">
        <div class="auth-logo">
            <div class="logo-icon">
                <i class="fas fa-user-plus text-white"></i>
            </div>
            <h1>Create Account</h1>
            <p>Register to access welfare schemes</p>
        </div>

        <?php if ($error): ?>
        <div class="alert-glass error mb-4">
            <i class="fas fa-exclamation-circle flex-shrink-0"></i>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert-glass success mb-4">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <div class="text-center">
            <a href="login.php" class="btn btn-glass px-5 py-2">
                <i class="fas fa-sign-in-alt me-2"></i>Login Now
            </a>
        </div>
        <?php else: ?>

        <form method="POST" id="registerForm" class="form-glass" novalidate>
            <div class="row g-3">
                <!-- Full Name -->
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-user me-1"></i> Full Name *</label>
                    <input type="text" class="form-control <?= isset($fieldErrors['name']) ? 'is-invalid' : '' ?>" id="name" name="name"
                        placeholder="Enter your full name"
                        value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                        required>
                    <div class="invalid-feedback"><?= isset($fieldErrors['name']) ? htmlspecialchars($fieldErrors['name']) : '' ?></div>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-envelope me-1"></i> Email *</label>
                    <input type="email" class="form-control <?= isset($fieldErrors['email']) ? 'is-invalid' : '' ?>" id="reg_email" name="email"
                        placeholder="user@gmail.com"
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                        required>
                    <div class="invalid-feedback"><?= isset($fieldErrors['email']) ? htmlspecialchars($fieldErrors['email']) : '' ?></div>
                </div>

                <!-- Phone -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-phone me-1"></i> Phone Number *</label>
                    <input type="text" class="form-control <?= isset($fieldErrors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone"
                        placeholder="10-digit mobile number"
                        value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                        inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" required>
                    <div class="invalid-feedback"><?= isset($fieldErrors['phone']) ? htmlspecialchars($fieldErrors['phone']) : '' ?></div>
                </div>

                <!-- Date of Birth -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-calendar me-1"></i> Date of Birth *</label>
                    <input type="date" class="form-control <?= isset($fieldErrors['dob']) ? 'is-invalid' : '' ?>" id="dob" name="dob"
                        value="<?= isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : '' ?>"
                        max="<?= date('Y-m-d') ?>" required>
                    <div class="invalid-feedback"><?= isset($fieldErrors['dob']) ? htmlspecialchars($fieldErrors['dob']) : '' ?></div>
                </div>

                <!-- Gender -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-venus-mars me-1"></i> Gender *</label>
                    <select class="form-select <?= isset($fieldErrors['gender']) ? 'is-invalid' : '' ?>" name="gender" required>
                        <option value="">Select Gender</option>
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= (isset($_POST['gender']) && $_POST['gender'] === $g) ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= isset($fieldErrors['gender']) ? htmlspecialchars($fieldErrors['gender']) : '' ?></div>
                </div>

                <!-- Category -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-layer-group me-1"></i> Category *</label>
                    <select class="form-select" name="category" required>
                        <?php foreach (['General','OBC','SC','ST','EWS'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Register As -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-user-tag me-1"></i> Register As *</label>
                    <select class="form-select <?= isset($fieldErrors['register_role']) ? 'is-invalid' : '' ?>" name="register_role" required>
                        <option value="user" <?= (!isset($_POST['register_role']) || $_POST['register_role'] === 'user') ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= (isset($_POST['register_role']) && $_POST['register_role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <div class="invalid-feedback"><?= isset($fieldErrors['register_role']) ? htmlspecialchars($fieldErrors['register_role']) : '' ?></div>
                </div>

                <!-- Annual Income -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-rupee-sign me-1"></i> Annual Income (₹)</label>
                    <input type="number" class="form-control <?= isset($fieldErrors['annual_income']) ? 'is-invalid' : '' ?>" id="annual_income" name="annual_income"
                        placeholder="Minimum ₹50000"
                        value="<?= isset($_POST['annual_income']) ? htmlspecialchars($_POST['annual_income']) : '' ?>"
                        min="50000" step="1" required>
                    <div class="invalid-feedback"><?= isset($fieldErrors['annual_income']) ? htmlspecialchars($fieldErrors['annual_income']) : '' ?></div>
                </div>

                <!-- Address -->
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i> Address</label>
                    <textarea class="form-control <?= isset($fieldErrors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" rows="2" minlength="10" required placeholder="Your full address (min 10 characters)"><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                    <div class="invalid-feedback"><?= isset($fieldErrors['address']) ? htmlspecialchars($fieldErrors['address']) : '' ?></div>
                </div>

                <!-- Password -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-lock me-1"></i> Password *</label>
                    <div class="position-relative">
                        <input type="password" class="form-control <?= isset($fieldErrors['password']) ? 'is-invalid' : '' ?>" id="password" name="password"
                            placeholder="Minimum 8 characters" required>
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"><?= isset($fieldErrors['password']) ? htmlspecialchars($fieldErrors['password']) : '' ?></div>
                    <!-- Password strength indicator -->
                    <div class="mt-2">
                        <div class="progress-glass">
                            <div id="strengthBar" style="width:0%;height:100%;background:#dc3545;border-radius:10px;transition:all 0.3s;"></div>
                        </div>
                        <div id="strengthLabel" style="font-size:11px;color:rgba(255,255,255,0.5);margin-top:3px;"></div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-lock me-1"></i> Confirm Password *</label>
                    <div class="position-relative">
                        <input type="password" class="form-control <?= isset($fieldErrors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password"
                            placeholder="Re-enter password" required>
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"><?= isset($fieldErrors['confirm_password']) ? htmlspecialchars($fieldErrors['confirm_password']) : '' ?></div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-glass w-100 py-3">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </div>
        </form>

        <?php endif; ?>

        <div class="text-center mt-3" style="color:rgba(255,255,255,0.6);font-size:14px;">
            Already have an account?
            <a href="login.php" style="color:#60a5fa;text-decoration:none;font-weight:600;">Login here</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
