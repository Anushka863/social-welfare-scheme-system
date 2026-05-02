<?php
/**
 * Quick User Population Script
 * This inserts test users directly into the database
 */

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "social_welfare";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<h2 style='color:red;'>Database Connection Failed!</h2><p>Error: " . $conn->connect_error . "</p><p>Make sure MySQL is running and the 'social_welfare' database exists.</p>");
}

$conn->set_charset("utf8mb4");

// Test users data
$users = [
    [
        'name' => 'Rajesh Kumar',
        'email' => 'rajesh@example.com',
        'phone' => '9876543210',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'dob' => '1985-03-15',
        'gender' => 'Male',
        'address' => '123 Main Street, Delhi 110001',
        'aadhar' => '123456789012',
        'annual_income' => 180000,
        'category' => 'OBC'
    ],
    [
        'name' => 'Priya Singh',
        'email' => 'priya@example.com',
        'phone' => '9123456789',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'dob' => '1990-07-22',
        'gender' => 'Female',
        'address' => '456 Oak Avenue, Mumbai 400001',
        'aadhar' => '234567890123',
        'annual_income' => 220000,
        'category' => 'General'
    ],
    [
        'name' => 'Amit Patel',
        'email' => 'amit@example.com',
        'phone' => '9987654321',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'dob' => '1988-11-05',
        'gender' => 'Male',
        'address' => '789 Farm Road, Gujarat 380001',
        'aadhar' => '345678901234',
        'annual_income' => 150000,
        'category' => 'SC'
    ],
    [
        'name' => 'Neha Sharma',
        'email' => 'neha@example.com',
        'phone' => '9234567890',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'dob' => '1992-05-18',
        'gender' => 'Female',
        'address' => '321 Educational Lane, Bangalore 560001',
        'aadhar' => '456789012345',
        'annual_income' => 280000,
        'category' => 'General'
    ],
    [
        'name' => 'Vivek Gupta',
        'email' => 'vivek@example.com',
        'phone' => '9345678901',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'dob' => '1987-09-30',
        'gender' => 'Male',
        'address' => '555 Business Park, Hyderabad 500001',
        'aadhar' => '567890123456',
        'annual_income' => 320000,
        'category' => 'EWS'
    ],
    [
        'name' => 'Pooja Deshmukh',
        'email' => 'pooja@example.com',
        'phone' => '8765432109',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'dob' => '1995-01-12',
        'gender' => 'Female',
        'address' => '789 Knowledge Park, Pune 411001',
        'aadhar' => '678901234567',
        'annual_income' => 195000,
        'category' => 'ST'
    ],
    [
        'name' => 'Suresh Reddy',
        'email' => 'suresh@example.com',
        'phone' => '9654321098',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'dob' => '1986-08-25',
        'gender' => 'Male',
        'address' => '234 Agricultural Zone, Telangana 500002',
        'aadhar' => '789012345678',
        'annual_income' => 165000,
        'category' => 'OBC'
    ]
];

$inserted = 0;
$skipped = 0;
$errors = [];

foreach ($users as $user) {
    // Check if user already exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $user['email']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $skipped++;
        continue;
    }
    
    // Insert user
    $insert_sql = "INSERT INTO users (name, email, phone, password, dob, gender, address, aadhar, annual_income, category, role, is_active) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 1)";
    $insert_stmt = $conn->prepare($insert_sql);
    
    if (!$insert_stmt) {
        $errors[] = "Prepare failed: " . $conn->error;
        continue;
    }
    
    $insert_stmt->bind_param(
        "ssssssssi",
        $user['name'],
        $user['email'],
        $user['phone'],
        $user['password'],
        $user['dob'],
        $user['gender'],
        $user['address'],
        $user['aadhar'],
        $user['annual_income']
    );
    
    // Need to bind category separately as it's not included in the first bind
    if ($insert_stmt->execute()) {
        $inserted++;
        $user_id = $conn->insert_id;
        
        // Create sample applications for this user
        $schemes_sql = "SELECT id FROM schemes WHERE status='active' LIMIT 3";
        $schemes_result = $conn->query($schemes_sql);
        $app_count = 0;
        
        while ($scheme = $schemes_result->fetch_assoc()) {
            $app_id = 'SW' . date('Y') . '-' . str_pad($user_id * 100 + $app_count, 4, '0', STR_PAD_LEFT);
            $status_options = ['pending', 'approved', 'under_review'];
            $status = $status_options[array_rand($status_options)];
            $days_ago = rand(1, 30);
            $applied_date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
            
            $app_sql = "INSERT INTO applications (application_id, user_id, scheme_id, status, applied_at) VALUES (?, ?, ?, ?, ?)";
            $app_stmt = $conn->prepare($app_sql);
            $app_stmt->bind_param("sisis", $app_id, $user_id, $scheme['id'], $status, $applied_date);
            $app_stmt->execute();
            $app_count++;
        }
    } else {
        $errors[] = "Insert failed for " . $user['email'] . ": " . $insert_stmt->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Populate Users | Social Welfare System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 700px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 30px 0;
        }
        .stat-box {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin: 10px 0;
        }
        .stat-box .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .success { color: #4caf50; }
        .warning { color: #ff9800; }
        .error-text { color: #f44336; }
        .message {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            line-height: 1.6;
        }
        .success-msg {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #81c784;
        }
        .info-msg {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #64b5f6;
        }
        .error-msg {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        .credentials {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 13px;
        }
        .credentials-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .credential-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .credential-item:last-child {
            border-bottom: none;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #d0d0d0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Test Data Populated</h1>
            <p>Users have been added to the database</p>
        </div>

        <div class="stats">
            <div class="stat-box">
                <div class="label">Inserted</div>
                <div class="number success"><?= $inserted ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Skipped</div>
                <div class="number warning"><?= $skipped ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Total</div>
                <div class="number"><?= $inserted + $skipped ?></div>
            </div>
        </div>

        <?php if ($inserted > 0): ?>
        <div class="message success-msg">
            <strong>✓ Success!</strong> <?= $inserted ?> applicant<?= $inserted != 1 ? 's' : '' ?> created successfully with sample applications.
        </div>

        <div class="credentials">
            <div class="credentials-title">📝 Test Login Credentials (Password: Test@123)</div>
            <div class="credential-item">
                <span>rajesh@example.com</span>
                <span style="color: #667eea; font-family: monospace;">Test@123</span>
            </div>
            <div class="credential-item">
                <span>priya@example.com</span>
                <span style="color: #667eea; font-family: monospace;">Test@123</span>
            </div>
            <div class="credential-item">
                <span>amit@example.com</span>
                <span style="color: #667eea; font-family: monospace;">Test@123</span>
            </div>
            <div class="credential-item">
                <span>neha@example.com</span>
                <span style="color: #667eea; font-family: monospace;">Test@123</span>
            </div>
            <div class="credential-item">
                <span>vivek@example.com</span>
                <span style="color: #667eea; font-family: monospace;">Test@123</span>
            </div>
            <div class="credential-item">
                <span>pooja@example.com</span>
                <span style="color: #667eea; font-family: monospace;">Test@123</span>
            </div>
            <div class="credential-item">
                <span>suresh@example.com</span>
                <span style="color: #667eea; font-family: monospace;">Test@123</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($skipped > 0): ?>
        <div class="message info-msg">
            ℹ️ <?= $skipped ?> user<?= $skipped != 1 ? 's' : '' ?> already exist<?= $skipped != 1 ? '' : 's' ?> in the database and were skipped.
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="message error-msg">
            <strong>⚠ Errors:</strong>
            <?php foreach ($errors as $error): ?>
                <div>• <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="button-group">
            <a href="manage_users.php" class="btn btn-primary">
                👥 View All Applicants
            </a>
            <a href="admin_portal.php" class="btn btn-secondary">
                ← Back to Admin
            </a>
        </div>
    </div>
</body>
</html>
