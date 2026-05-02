<?php
/**
 * Simple Application Test - Direct approach
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireUser();

// Simple test without any validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])) {
    $uid = $_SESSION['user_id'];
    $scheme_id = (int)$_POST['scheme_id'];
    
    // Generate simple ID
    $app_id = 'TEST' . time();
    
    // Direct MySQL insert
    $sql = "INSERT INTO applications (application_id, user_id, scheme_id, status, applied_at) 
              VALUES ('$app_id', '$uid', '$scheme_id', 'pending', NOW())";
    
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<h3 style='color: green;'>SUCCESS: Application submitted!</h3>";
        echo "<p>Application ID: $app_id</p>";
        echo "<p>User ID: $uid</p>";
        echo "<p>Scheme ID: $scheme_id</p>";
        echo "<p><a href='track_application.php?id=$app_id'>Track Application</a></p>";
        
        // Verify it was inserted
        $check = $conn->query("SELECT * FROM applications WHERE application_id='$app_id'");
        $row = $check->fetch_assoc();
        if ($row) {
            echo "<p style='color: blue;'>Verified: Application exists in database</p>";
        } else {
            echo "<p style='color: red;'>ERROR: Application not found after insert</p>";
        }
    } else {
        echo "<h3 style='color: red;'>FAILED: " . $conn->error . "</h3>";
        echo "<p>SQL: $sql</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Application</title>
</head>
<body>
    <h2>Simple Application Test</h2>
    
    <form method="POST">
        <label>Scheme ID:</label>
        <input type="number" name="scheme_id" value="1" required>
        <br><br>
        <button type="submit" name="test_submit">Test Application</button>
    </form>
    
    <hr>
    <p>This test bypasses all validation and uses direct MySQL insert.</p>
    <p>If this works, the issue is in your validation logic.</p>
    <p>If this fails, the issue is in database connection or table structure.</p>
</body>
</html>
