<?php
require_once 'includes/db.php';

echo "<h2>Complete System Fix</h2>";
echo "<p>This script will fix all database and application issues.</p>";

// Step 1: Ensure all tables exist with proper structure
echo "<h3>Step 1: Checking Database Structure</h3>";

// Check users table
echo "<p><strong>Users Table:</strong></p>";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Users table exists with {$row['count']} records<br>";
} else {
    echo "✗ Users table issue<br>";
}

// Check schemes table
echo "<p><strong>Schemes Table:</strong></p>";
$result = $conn->query("SELECT COUNT(*) as count FROM schemes");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Schemes table exists with {$row['count']} records<br>";
    
    if ($row['count'] == 0) {
        echo "<p>Adding sample schemes...</p>";
        
        $schemes = [
            [
                'title' => 'PM Kisan Samman Nidhi',
                'description' => 'Direct income support scheme for farmers providing ₹6000 per year in three equal installments of ₹2000 each to eligible farmer families.',
                'eligibility' => 'Small and marginal farmers owning cultivable land. Annual income below ₹2,00,000. Age between 18-65 years.',
                'benefits' => 'Financial benefit of ₹6000 per year directly to bank account in three installments. No middlemen involved.',
                'category' => 'Agriculture',
                'min_age' => 18,
                'max_age' => 65,
                'max_income' => 200000,
                'eligible_categories' => 'General,OBC,SC,ST,EWS',
                'required_documents' => 'Aadhar Card, Land Records, Bank Passbook, Income Certificate',
                'last_date' => '2026-12-31',
                'status' => 'active',
                'created_by' => 1
            ],
            [
                'title' => 'National Scholarship Portal',
                'description' => 'Merit-cum-means scholarship for students from economically weaker sections to pursue higher education.',
                'eligibility' => 'Students scoring above 50% marks. Family annual income below ₹2,50,000. Age 15-25 years.',
                'benefits' => 'Scholarship amount up to ₹50,000 per year. Covers tuition fees and maintenance allowance.',
                'category' => 'Education',
                'min_age' => 15,
                'max_age' => 25,
                'max_income' => 250000,
                'eligible_categories' => 'OBC,SC,ST,EWS',
                'required_documents' => 'Mark Sheets, Income Certificate, Aadhar Card, Bank Passbook, Institution Certificate',
                'last_date' => '2026-09-30',
                'status' => 'active',
                'created_by' => 1
            ],
            [
                'title' => 'Pradhan Mantri Awas Yojana',
                'description' => 'Housing for All mission to provide affordable housing to urban and rural poor with credit-linked subsidy.',
                'eligibility' => 'EWS/LIG/MIG categories. No pucca house in name or spouse name. Annual income below ₹6,00,000.',
                'benefits' => 'Interest subsidy up to ₹2.67 lakh on home loans. Direct benefit transfer for house construction.',
                'category' => 'Housing',
                'min_age' => 21,
                'max_age' => 60,
                'max_income' => 600000,
                'eligible_categories' => 'OBC,SC,ST,EWS',
                'required_documents' => 'Aadhar Card, Income Certificate, Land Documents, Bank Statements, Self-Declaration',
                'last_date' => '2026-06-30',
                'status' => 'active',
                'created_by' => 1
            ]
        ];
        
        foreach ($schemes as $scheme) {
            $ok = db_query(
                "INSERT INTO schemes (title, description, eligibility, benefits, category, min_age, max_age, max_income, eligible_categories, required_documents, last_date, status, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $scheme['title'],
                    $scheme['description'],
                    $scheme['eligibility'],
                    $scheme['benefits'],
                    $scheme['category'],
                    $scheme['min_age'],
                    $scheme['max_age'],
                    $scheme['max_income'],
                    $scheme['eligible_categories'],
                    $scheme['required_documents'],
                    $scheme['last_date'],
                    $scheme['status'],
                    $scheme['created_by']
                ]
            );
            
            if ($ok) {
                echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($scheme['title']) . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to add: " . htmlspecialchars($scheme['title']) . "</p>";
            }
        }
    }
} else {
    echo "✗ Schemes table missing<br>";
}

// Check applications table structure
echo "<p><strong>Applications Table:</strong></p>";
$result = $conn->query("DESCRIBE applications");
if ($result) {
    echo "✓ Applications table exists<br>";
    
    // Check for required columns
    $required_columns = ['id', 'application_id', 'user_id', 'scheme_id', 'status', 'remarks', 'documents', 'applied_at', 'updated_at'];
    $existing_columns = [];
    
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "✓ Column $col exists<br>";
        } else {
            echo "✗ Column $col missing<br>";
        }
    }
} else {
    echo "✗ Applications table missing<br>";
}

// Step 2: Create test user if none exists
echo "<h3>Step 2: Creating Test User</h3>";
$test_user = db_fetch("SELECT id FROM users WHERE email = 'testuser@example.com'");
if (!$test_user) {
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
        echo "<p style='color: green;'>✓ Test user created: testuser@example.com / password123</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create test user</p>";
    }
} else {
    echo "<p>✓ Test user already exists</p>";
}

// Step 3: Show current status
echo "<h3>Step 3: Current System Status</h3>";

// Show users
$users = db_query("SELECT id, name, email, role, is_active FROM users ORDER BY created_at DESC");
echo "<p><strong>Users:</strong></p>";
if ($users) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'><tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['role']}</td><td>" . ($u['is_active'] ? 'Yes' : 'No') . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found</p>";
}

// Show schemes
$schemes = db_query("SELECT id, title, category, status FROM schemes ORDER BY created_at DESC");
echo "<p><strong>Schemes:</strong></p>";
if ($schemes) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'><tr style='background: #f0f0f0;'><th>ID</th><th>Title</th><th>Category</th><th>Status</th></tr>";
    foreach ($schemes as $s) {
        echo "<tr><td>{$s['id']}</td><td>{$s['title']}</td><td>{$s['category']}</td><td>{$s['status']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No schemes found</p>";
}

// Show applications
$applications = db_query("SELECT a.*, u.name as user_name, s.title as scheme_title FROM applications a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN schemes s ON a.scheme_id = s.id ORDER BY a.applied_at DESC");
echo "<p><strong>Applications:</strong></p>";
if ($applications) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'><tr style='background: #f0f0f0;'><th>ID</th><th>Application ID</th><th>User</th><th>Scheme</th><th>Status</th><th>Applied At</th></tr>";
    foreach ($applications as $a) {
        echo "<tr><td>{$a['id']}</td><td>{$a['application_id']}</td><td>{$a['user_name']}</td><td>{$a['scheme_title']}</td><td>{$a['status']}</td><td>{$a['applied_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No applications found</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<p>1. <a href='login.php'>Login as test user</a> (testuser@example.com / password123)</p>";
echo "<p>2. <a href='schemes.php'>Browse available schemes</a></p>";
echo "<p>3. Apply for any scheme you're eligible for</p>";
echo "<p>4. <a href='manage_users.php'>Manage users</a> (as admin)</p>";
?>
