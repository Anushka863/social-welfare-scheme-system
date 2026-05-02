<?php
require_once 'includes/db.php';

echo "<h2>Fixing Applications Table Structure</h2>";

// Check current applications table structure
echo "<h3>Current Applications Table:</h3>";
$result = $conn->query("DESCRIBE applications");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
}

// Check if application_id column exists, if not add it
$check_app_id = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_id'");
if ($check_app_id && $check_app_id->num_rows == 0) {
    echo "<p>Adding application_id column...</p>";
    $conn->query("ALTER TABLE applications ADD COLUMN application_id VARCHAR(20) UNIQUE NOT NULL AFTER id");
    echo "<p style='color: green;'>✓ Added application_id column</p>";
}

// Check if scheme_id column exists, if not add it
$check_scheme_id = $conn->query("SHOW COLUMNS FROM applications LIKE 'scheme_id'");
if ($check_scheme_id && $check_scheme_id->num_rows == 0) {
    echo "<p>Adding scheme_id column...</p>";
    $conn->query("ALTER TABLE applications ADD COLUMN scheme_id INT NOT NULL AFTER user_id");
    echo "<p style='color: green;'>✓ Added scheme_id column</p>";
}

// Check if remarks column exists, if not add it
$check_remarks = $conn->query("SHOW COLUMNS FROM applications LIKE 'remarks'");
if ($check_remarks && $check_remarks->num_rows == 0) {
    echo "<p>Adding remarks column...</p>";
    $conn->query("ALTER TABLE applications ADD COLUMN remarks TEXT AFTER status");
    echo "<p style='color: green;'>✓ Added remarks column</p>";
}

// Check if documents column exists, if not add it
$check_documents = $conn->query("SHOW COLUMNS FROM applications LIKE 'documents'");
if ($check_documents && $check_documents->num_rows == 0) {
    echo "<p>Adding documents column...</p>";
    $conn->query("ALTER TABLE applications ADD COLUMN documents VARCHAR(255) AFTER remarks");
    echo "<p style='color: green;'>✓ Added documents column</p>";
}

// Check if updated_at column exists, if not add it
$check_updated_at = $conn->query("SHOW COLUMNS FROM applications LIKE 'updated_at'");
if ($check_updated_at && $check_updated_at->num_rows == 0) {
    echo "<p>Adding updated_at column...</p>";
    $conn->query("ALTER TABLE applications ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER applied_at");
    echo "<p style='color: green;'>✓ Added updated_at column</p>";
}

// Update existing applications to have application_id if they don't have one
$update_result = $conn->query("UPDATE applications SET application_id = CONCAT('SW', YEAR(CURDATE()), '-', LPAD(id, 4, '0')) WHERE application_id IS NULL OR application_id = ''");
if ($update_result) {
    echo "<p style='color: green;'>✓ Updated application IDs for existing records</p>";
}

// Show final structure
echo "<h3>Updated Applications Table:</h3>";
$result = $conn->query("DESCRIBE applications");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
}

// Show current applications
echo "<h3>Current Applications:</h3>";
$applications = db_query("SELECT * FROM applications ORDER BY applied_at DESC");
if ($applications) {
    echo "<table border='1'><tr><th>ID</th><th>Application ID</th><th>User ID</th><th>Scheme ID</th><th>Status</th><th>Applied At</th></tr>";
    foreach ($applications as $app) {
        echo "<tr><td>{$app['id']}</td><td>{$app['application_id']}</td><td>{$app['user_id']}</td><td>{$app['scheme_id']}</td><td>{$app['status']}</td><td>{$app['applied_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No applications found.</p>";
}

echo "<p><a href='schemes.php'>Go to Schemes Page</a></p>";
echo "<p><a href='setup_schemes.php'>Setup Schemes</a></p>";
?>
