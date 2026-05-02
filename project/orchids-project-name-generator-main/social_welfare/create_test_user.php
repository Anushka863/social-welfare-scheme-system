<?php
require_once 'includes/db.php';

// Check if user already exists
$existing = db_fetch("SELECT id FROM users WHERE email = ?", ['testuser@example.com']);
if (!$existing) {
    // Create a test user
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    $ok = db_query(
        "INSERT INTO users (name, email, phone, password, dob, gender, address, aadhar, annual_income, category, role, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
        [
            'Test Citizen',
            'testuser@example.com',
            '9876543210',
            $hash,
            '1990-01-01',
            'Male',
            '123 Test Street, Test City',
            '123456789012',
            50000.00,
            'OBC',
            'user'
        ]
    );
    
    if ($ok) {
        echo "Test user created successfully!<br>";
        echo "Email: testuser@example.com<br>";
        echo "Password: password123<br>";
    } else {
        echo "Failed to create test user.<br>";
    }
} else {
    echo "Test user already exists.<br>";
}

// Show all users
echo "<h3>All Users in Database:</h3>";
$users = db_query("SELECT id, name, email, phone, category, annual_income, is_active, role, created_at FROM users ORDER BY created_at DESC");
if ($users) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Category</th><th>Income</th><th>Active</th><th>Role</th><th>Created</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['phone']}</td><td>{$u['category']}</td><td>{$u['annual_income']}</td><td>{$u['is_active']}</td><td>{$u['role']}</td><td>{$u['created_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No users found or query failed.";
}
?>
