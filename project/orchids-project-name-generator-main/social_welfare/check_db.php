<?php
/**
 * Database structure check - read-only, won't modify anything
 */
echo "<h2>Database Structure Check</h2>";

require_once 'includes/db.php';

if (!$conn) {
    echo "<p style='color: red;'>Cannot connect to database</p>";
    exit;
}

echo "<h3>Applications Table Structure</h3>";
$app_structure = $conn->query("DESCRIBE applications");
if ($app_structure) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $app_structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Cannot get applications table structure</p>";
}

echo "<h3>Sample Applications Data</h3>";
$sample_data = $conn->query("SELECT * FROM applications LIMIT 3");
if ($sample_data && $sample_data->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Application ID</th><th>User ID</th><th>Scheme ID</th><th>Status</th></tr>";
    while ($row = $sample_data->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['application_id']}</td><td>{$row['user_id']}</td><td>{$row['scheme_id']}</td><td>{$row['status']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No applications data found (table might be empty)</p>";
}

echo "<h3>Test Simple Query</h3>";
$test_query = $conn->query("SELECT COUNT(*) as total FROM applications");
if ($test_query) {
    $result = $test_query->fetch_assoc();
    echo "<p style='color: green;'>Simple query works: {$result['total']} applications total</p>";
} else {
    echo "<p style='color: red;'>Simple query failed: " . $conn->error . "</p>";
}

echo "<h3>Test Insert Query (Safe)</h3>";
// Just test the query structure without actually inserting
$test_insert = "INSERT INTO applications (application_id, user_id, scheme_id, status, applied_at) VALUES ('TEST-123', 1, 1, 'pending', NOW())";
echo "<p>Query structure that would be used:</p>";
echo "<code style='background: #f0f0f0; padding: 5px;'>$test_insert</code>";
echo "<p>This is the exact query structure that apply_scheme.php uses.</p>";

echo "<h3>Common Issues to Check:</h3>";
echo "<ul>";
echo "<li>Is the applications table structure correct?</li>";
echo "<li>Are there any missing required fields?</li>";
echo "<li>Is the database user missing INSERT permissions?</li>";
echo "<li>Are there any foreign key constraints blocking inserts?</li>";
echo "</ul>";
?>
