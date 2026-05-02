<?php
/**
 * Setup Test Data - Creates sample users and applications for testing
 * Visit: http://localhost/social_welfare/setup_test_data.php
 */
require_once 'includes/db.php';

$message = '';
$success = false;

try {
    // Insert test users
    $test_users = [
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
            'category' => 'OBC',
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
            'category' => 'General',
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
            'category' => 'SC',
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
            'category' => 'General',
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
            'category' => 'EWS',
        ],
    ];

    $inserted_users = 0;
    $inserted_applications = 0;

    foreach ($test_users as $user) {
        // Check if user already exists
        $existing = db_fetch("SELECT id FROM users WHERE email = ?", [$user['email']]);
        
        if (!$existing) {
            $result = db_query(
                "INSERT INTO users (name, email, phone, password, dob, gender, address, aadhar, annual_income, category, role, is_active) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 1)",
                [
                    $user['name'], $user['email'], $user['phone'], $user['password'],
                    $user['dob'], $user['gender'], $user['address'], $user['aadhar'],
                    $user['annual_income'], $user['category']
                ]
            );
            
            if ($result) {
                $inserted_users++;
                
                // Get the inserted user ID
                $new_user = db_fetch("SELECT id FROM users WHERE email = ?", [$user['email']]);
                $user_id = $new_user['id'];
                
                // Insert sample applications for this user
                $schemes = db_query("SELECT id FROM schemes WHERE status='active' LIMIT 3", []);
                $scheme_count = 0;
                
                foreach ($schemes as $scheme) {
                    $app_id = 'SW' . date('Y') . '-' . str_pad($user_id * 100 + $scheme_count, 4, '0', STR_PAD_LEFT);
                    $status_options = ['pending', 'approved', 'under_review'];
                    $status = $status_options[array_rand($status_options)];
                    
                    $app_result = db_query(
                        "INSERT INTO applications (application_id, user_id, scheme_id, status, applied_at) 
                         VALUES (?, ?, ?, ?, ?)",
                        [$app_id, $user_id, $scheme['id'], $status, date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))]
                    );
                    
                    if ($app_result) {
                        $inserted_applications++;
                    }
                    $scheme_count++;
                }
            }
        }
    }

    if ($inserted_users > 0) {
        $success = true;
        $message = "<strong>✓ Success!</strong><br>
                   Created <strong>$inserted_users</strong> test users<br>
                   Created <strong>$inserted_applications</strong> test applications<br><br>
                   <strong>Test User Credentials:</strong><br>
                   Email: rajesh@example.com | Password: Test@123<br>
                   Email: priya@example.com | Password: Test@123<br>
                   Email: amit@example.com | Password: Test@123<br>
                   Email: neha@example.com | Password: Test@123<br>
                   Email: vivek@example.com | Password: Test@123";
    } else {
        $message = "ℹ Test users already exist in database.";
    }

} catch (Exception $e) {
    $message = "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Test Data | Social Welfare System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; max-width: 600px; width: 100%; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #333; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 14px; }
        .message { padding: 20px; border-radius: 8px; margin: 20px 0; line-height: 1.6; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .button-group { display: flex; gap: 10px; margin-top: 30px; }
        .btn { flex: 1; padding: 12px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #e0e0e0; color: #333; }
        .btn-secondary:hover { background: #d0d0d0; }
        .code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Setup Test Data</h1>
            <p>Initialize sample users and applications for testing</p>
        </div>

        <?php if ($success): ?>
            <div class="message success">
                <?= $message ?>
            </div>
            <div class="button-group">
                <a href="login.php" class="btn btn-primary">📝 Login Now</a>
                <a href="admin_portal.php" class="btn btn-secondary">📊 Admin Portal</a>
            </div>
        <?php else: ?>
            <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'info' ?>">
                <?= $message ?>
            </div>
            <div class="button-group">
                <a href="manage_users.php" class="btn btn-secondary">← Back to Manage Users</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
