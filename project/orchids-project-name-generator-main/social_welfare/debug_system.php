<?php
/**
 * Debug System - Check Database Connectivity and Issues
 */
require_once 'includes/db.php';

echo "<h2>Database Connection Debug</h2>";

// Check connection
echo "<p><strong>Database Connection:</strong> " . ($conn ? "OK" : "FAILED") . "</p>";

// Check if tables exist
$tables = ['users', 'schemes', 'applications', 'notifications'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $result && $result->num_rows > 0;
    echo "<p><strong>Table '$table':</strong> " . ($exists ? "EXISTS" : "MISSING") . "</p>";
}

// Check users table structure and data
echo "<h3>Users Table Analysis</h3>";
$users_result = db_query("SELECT id, name, email, role FROM users ORDER BY created_at DESC");
if ($users_result === false) {
    echo "<p style='color: red;'>Query failed: " . $conn->error . "</p>";
} else {
    if (is_array($users_result)) {
        echo "<p><strong>Total users (array):</strong> " . count($users_result) . "</p>";
        foreach ($users_result as $user) {
            echo "<p>User ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}</p>";
        }
    } elseif ($users_result instanceof mysqli_result) {
        $count = $users_result->num_rows;
        echo "<p><strong>Total users (mysqli_result):</strong> $count</p>";
        while ($row = $users_result->fetch_assoc()) {
            echo "<p>User ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}, Role: {$row['role']}</p>";
        }
    } else {
        echo "<p style='color: orange;'>Unexpected result type: " . gettype($users_result) . "</p>";
    }
}

// Test application submission
echo "<h3>Application Submission Test</h3>";
try {
    $app_id = generateAppID($conn);
    echo "<p><strong>Generated App ID:</strong> $app_id</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>App ID generation failed: " . $e->getMessage() . "</p>";
}

// Check if required fields exist in applications table
echo "<h3>Applications Table Structure</h3>";
$structure = $conn->query("DESCRIBE applications");
if ($structure) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Could not get applications table structure</p>";
}
?>
