<?php
require_once 'includes/db.php';

echo "<h2>Setting up Schemes and Database</h2>";

// Check if schemes table exists and has data
$result = $conn->query("SELECT COUNT(*) as count FROM schemes");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Current schemes in database: " . $row['count'] . "</p>";
    
    if ($row['count'] == 0) {
        echo "<p>Adding sample schemes...</p>";
        
        // Insert sample schemes from the SQL file
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
}

// Check if users exist
echo "<h3>Checking Users:</h3>";
$users = db_query("SELECT id, name, email, role FROM users ORDER BY created_at DESC");
if ($users) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['role']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found.</p>";
}

// Check applications table structure
echo "<h3>Applications Table Structure:</h3>";
$result = $conn->query("DESCRIBE applications");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
}

echo "<p><a href='schemes.php'>Go to Schemes Page</a></p>";
echo "<p><a href='register.php'>Register New User</a></p>";
?>
