<?php
/**
 * Simple diagnostic check - won't modify any existing files
 */
echo "<h2>System Status Check</h2>";

// Check database connection
try {
    require_once 'includes/db.php';
    echo "<p style='color: green;'>Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection: FAILED - " . $e->getMessage() . "</p>";
}

// Check if applications table exists and has data
if ($conn) {
    $tables_check = $conn->query("SHOW TABLES LIKE 'applications'");
    if ($tables_check && $tables_check->num_rows > 0) {
        echo "<p style='color: green;'>Applications table: EXISTS</p>";
        
        $count_check = $conn->query("SELECT COUNT(*) as cnt FROM applications");
        if ($count_check) {
            $row = $count_check->fetch_assoc();
            echo "<p>Current applications in database: " . $row['cnt'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Applications table: MISSING</p>";
    }
    
    // Check schemes table
    $schemes_check = $conn->query("SHOW TABLES LIKE 'schemes'");
    if ($schemes_check && $schemes_check->num_rows > 0) {
        echo "<p style='color: green;'>Schemes table: EXISTS</p>";
        
        $active_schemes = $conn->query("SELECT COUNT(*) as cnt FROM schemes WHERE status='active'");
        if ($active_schemes) {
            $row = $active_schemes->fetch_assoc();
            echo "<p>Active schemes available: " . $row['cnt'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Schemes table: MISSING</p>";
    }
    
    // Check users table
    $users_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($users_check && $users_check->num_rows > 0) {
        echo "<p style='color: green;'>Users table: EXISTS</p>";
        
        $user_count = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='user'");
        if ($user_count) {
            $row = $user_count->fetch_assoc();
            echo "<p>Regular users in database: " . $row['cnt'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Users table: MISSING</p>";
    }
}

// Check session
echo "<h3>Session Status</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session ID: " . session_id() . "</p>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>Logged in user ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>User role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";
} else {
    echo "<p style='color: orange;'>No user logged in</p>";
}

echo "<h3>Quick Test</h3>";
echo "<p>If you're logged in, try to apply for a scheme and see what error message you get.</p>";
echo "<p>Then check this list above to identify the issue.</p>";
?>
