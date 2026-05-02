<?php
/**
 * Test script to verify the fixes for user management and applications
 */
require_once 'includes/db.php';

echo "<h2>Testing Database Fixes</h2>";

// Test 1: User query
echo "<h3>Test 1: User Query</h3>";
$users = db_query("SELECT id, name, email, role FROM users ORDER BY created_at DESC");
if ($users === false) {
    echo "<p style='color: red;'>User query FAILED</p>";
} else {
    echo "<p style='color: green;'>User query SUCCESS - Found " . count($users) . " users</p>";
    foreach ($users as $user) {
        echo "<p>User: {$user['name']} ({$user['email']}) - Role: {$user['role']}</p>";
    }
}

// Test 2: Admin query
echo "<h3>Test 2: Admin Query</h3>";
$admins = db_query("SELECT id, name, email, role FROM users WHERE role='admin' ORDER BY created_at DESC");
if ($admins === false) {
    echo "<p style='color: red;'>Admin query FAILED</p>";
} else {
    echo "<p style='color: green;'>Admin query SUCCESS - Found " . count($admins) . " admins</p>";
    foreach ($admins as $admin) {
        echo "<p>Admin: {$admin['name']} ({$admin['email']})</p>";
    }
}

// Test 3: Applications query
echo "<h3>Test 3: Applications Query</h3>";
$applications = db_query("
    SELECT a.*, u.name AS user_name, s.title AS scheme_title
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN schemes s ON a.scheme_id = s.id
    ORDER BY a.applied_at DESC
    LIMIT 5
");
if ($applications === false) {
    echo "<p style='color: red;'>Applications query FAILED</p>";
} else {
    echo "<p style='color: green;'>Applications query SUCCESS - Found " . count($applications) . " applications</p>";
    foreach ($applications as $app) {
        echo "<p>App: {$app['application_id']} by {$app['user_name']} for {$app['scheme_title']}</p>";
    }
}

// Test 4: Application ID generation
echo "<h3>Test 4: Application ID Generation</h3>";
try {
    $app_id = generateAppID($conn);
    echo "<p style='color: green;'>App ID generation SUCCESS: $app_id</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>App ID generation FAILED: " . $e->getMessage() . "</p>";
}

echo "<h3>Summary</h3>";
echo "<p>If all tests show green, the fixes are working correctly.</p>";
?>
