<?php
/**
 * DEBUG FILE - Remove after fixing
 * Visit: http://localhost/social_welfare/debug_apply.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Step 1: DB Connection</h2>";
$conn = new mysqli('localhost', 'root', '', 'social_welfare');
if ($conn->connect_error) {
    die("<span style='color:red'>FAIL: " . $conn->connect_error . "</span><br>
    <b>Fix:</b> Make sure MySQL is running in XAMPP and the database 'social_welfare' exists.<br>
    Go to <a href='http://localhost/phpmyadmin'>phpMyAdmin</a> → Create database named <b>social_welfare</b> → Import <b>social_welfare.sql</b>");
}
echo "<span style='color:green'>OK - Connected to MySQL</span><br>";

echo "<h2>Step 2: Tables</h2>";
$tables = ['users','schemes','applications','notifications'];
foreach ($tables as $t) {
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    if ($r && $r->num_rows > 0) {
        echo "<span style='color:green'>OK - Table '$t' exists</span><br>";
    } else {
        echo "<span style='color:red'>MISSING - Table '$t' does not exist!</span><br>";
    }
}

echo "<h2>Step 3: applications table structure</h2>";
$r = $conn->query("DESCRIBE applications");
if ($r) {
    echo "<table border='1' cellpadding='4'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
}

echo "<h2>Step 4: Schemes in DB</h2>";
$r = $conn->query("SELECT id, title, status FROM schemes");
if ($r && $r->num_rows > 0) {
    echo "<table border='1' cellpadding='4'><tr><th>ID</th><th>Title</th><th>Status</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['title']}</td><td>{$row['status']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<span style='color:red'>No schemes found! Import social_welfare.sql</span><br>";
}

echo "<h2>Step 5: Users in DB</h2>";
$r = $conn->query("SELECT id, name, email, role FROM users");
if ($r && $r->num_rows > 0) {
    echo "<table border='1' cellpadding='4'><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['role']}</td></tr>";
    }
    echo "</table>";
}

echo "<h2>Step 6: Test INSERT into applications</h2>";
// Get first scheme id and first user id
$scheme = $conn->query("SELECT id FROM schemes LIMIT 1")->fetch_assoc();
$user   = $conn->query("SELECT id FROM users WHERE role='user' LIMIT 1")->fetch_assoc();

if (!$scheme) {
    echo "<span style='color:red'>No scheme found to test with</span><br>";
} elseif (!$user) {
    echo "<span style='color:red'>No regular user found to test with</span><br>";
} else {
    $sid = $scheme['id'];
    $uid = $user['id'];
    $app_id = 'SW-DEBUG-' . time();

    $stmt = $conn->prepare("INSERT INTO applications (application_id, user_id, scheme_id, status, applied_at) VALUES (?, ?, ?, 'pending', NOW())");
    if (!$stmt) {
        echo "<span style='color:red'>Prepare failed: " . $conn->error . "</span><br>";
    } else {
        $stmt->bind_param('sii', $app_id, $uid, $sid);
        $ok = $stmt->execute();
        if ($ok) {
            echo "<span style='color:green'>INSERT SUCCESS! Application inserted as $app_id</span><br>";
            // Clean up test row
            $conn->query("DELETE FROM applications WHERE application_id='$app_id'");
            echo "(Test row cleaned up)<br>";
        } else {
            echo "<span style='color:red'>INSERT FAILED: " . $stmt->error . "</span><br>";
        }
    }
}

echo "<h2>Step 7: Foreign Key Constraints on applications</h2>";
$r = $conn->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                   FROM information_schema.KEY_COLUMN_USAGE 
                   WHERE TABLE_SCHEMA='social_welfare' AND TABLE_NAME='applications' AND REFERENCED_TABLE_NAME IS NOT NULL");
if ($r && $r->num_rows > 0) {
    echo "<table border='1' cellpadding='4'><tr><th>Constraint</th><th>Column</th><th>References</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr><td>{$row['CONSTRAINT_NAME']}</td><td>{$row['COLUMN_NAME']}</td><td>{$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No FK constraints found.<br>";
}

echo "<br><b style='color:blue'>Send me a screenshot or copy of all the above output!</b>";
?>
