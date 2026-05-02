<?php
require_once 'includes/db.php';

echo "<h2>Debug: Users Table</h2>";

// Check table structure
echo "<h3>Table Structure:</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
}

// Check total users
echo "<h3>Total Users:</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Total users: " . $row['count'] . "</p>";
}

// Check users by role
echo "<h3>Users by Role:</h3>";
$result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
if ($result) {
    echo "<table border='1'><tr><th>Role</th><th>Count</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['role']}</td><td>{$row['count']}</td></tr>";
    }
    echo "</table>";
}

// Try the exact query from manage_users.php
echo "<h3>Query from manage_users.php:</h3>";
$users = db_query("SELECT id, name, email, phone, category, annual_income, is_active, created_at FROM users WHERE role='user' ORDER BY created_at DESC");
if ($users === false) {
    echo "<p style='color: red;'>Query failed: " . $conn->error . "</p>";
} elseif (empty($users)) {
    echo "<p>No users found with role='user'</p>";
} else {
    echo "<p>Found " . count($users) . " users</p>";
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Category</th><th>Income</th><th>Active</th><th>Created</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['phone']}</td><td>{$u['category']}</td><td>{$u['annual_income']}</td><td>{$u['is_active']}</td><td>{$u['created_at']}</td></tr>";
    }
    echo "</table>";
}

// Show all users regardless of role
echo "<h3>All Users:</h3>";
$result = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['role']}</td><td>{$row['created_at']}</td></tr>";
    }
    echo "</table>";
}
?>
