<?php
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'social_welfare');
define('DB_PORT', 3307);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
}

$conn->set_charset('utf8mb4');

function run_query(mysqli $conn, string $sql): void {
    if (!$conn->query($sql)) {
        die('Database setup failed: ' . htmlspecialchars($conn->error));
    }
}

function ensure_column(mysqli $conn, string $table, string $column, string $definition): void {
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $existsSql = "SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '{$tableEsc}'
                  AND COLUMN_NAME = '{$columnEsc}'
                  LIMIT 1";
    $exists = $conn->query($existsSql);
    if ($exists && $exists->num_rows === 0) {
        run_query($conn, "ALTER TABLE `{$table}` ADD COLUMN {$definition}");
    }
}

run_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        phone VARCHAR(15) NOT NULL,
        password VARCHAR(255) NOT NULL,
        dob DATE NOT NULL,
        gender ENUM('Male','Female','Other') NOT NULL,
        address TEXT,
        aadhar VARCHAR(12),
        annual_income DECIMAL(12,2) DEFAULT 0,
        category ENUM('General','OBC','SC','ST','EWS') DEFAULT 'General',
        profile_photo VARCHAR(255) DEFAULT NULL,
        role ENUM('user','admin') DEFAULT 'user',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

run_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(20) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        scheme_id INT NOT NULL,
        status ENUM('pending','under_review','approved','rejected') DEFAULT 'pending',
        remarks TEXT,
        documents VARCHAR(255),
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_applications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

run_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS schemes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        eligibility TEXT NOT NULL,
        benefits TEXT NOT NULL,
        category ENUM('Agriculture','Education','Health','Housing','Employment','Women','Elderly','Disability','Other') DEFAULT 'Other',
        min_age INT DEFAULT 0,
        max_age INT DEFAULT 120,
        max_income DECIMAL(12,2) DEFAULT 999999,
        eligible_categories SET('General','OBC','SC','ST','EWS','All') DEFAULT 'All',
        required_documents TEXT,
        last_date DATE,
        status ENUM('active','inactive') DEFAULT 'active',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

run_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Keep older DBs compatible with new requirements.
ensure_column($conn, 'users', 'phone', "phone VARCHAR(15) NOT NULL DEFAULT ''");
ensure_column($conn, 'users', 'dob', "dob DATE NOT NULL DEFAULT '2000-01-01'");
ensure_column($conn, 'users', 'gender', "gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Other'");
ensure_column($conn, 'users', 'address', "address TEXT");
ensure_column($conn, 'users', 'aadhar', "aadhar VARCHAR(12)");
ensure_column($conn, 'users', 'annual_income', "annual_income DECIMAL(12,2) DEFAULT 0");
ensure_column($conn, 'users', 'category', "category ENUM('General','OBC','SC','ST','EWS') DEFAULT 'General'");
ensure_column($conn, 'users', 'profile_photo', "profile_photo VARCHAR(255) DEFAULT NULL");
ensure_column($conn, 'users', 'role', "role ENUM('user','admin') DEFAULT 'user'");
ensure_column($conn, 'users', 'is_active', "is_active TINYINT(1) DEFAULT 1");
ensure_column($conn, 'users', 'updated_at', "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
?>
