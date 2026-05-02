<?php
/**
 * Database Connection - Social Welfare Scheme Management System
 * Uses MySQLi with prepared statements for security
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'social_welfare');

/**
 * Auto-create schema from social_welfare.sql when tables are missing.
 */
function ensure_schema_ready($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check && $check->num_rows > 0) {
        return true;
    }

    $sqlPath = dirname(__DIR__) . '/social_welfare.sql';
    if (!is_readable($sqlPath)) {
        return false;
    }

    $sql = file_get_contents($sqlPath);
    if ($sql === false || trim($sql) === '') {
        return false;
    }

    // Keep import portable even on restricted MySQL users.
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\s+.*?;\s*$/mi', '', $sql);
    $sql = preg_replace('/^\s*USE\s+.*?;\s*$/mi', '', $sql);

    if (!$conn->multi_query($sql)) {
        error_log('Schema import failed: ' . $conn->error);
        return false;
    }

    while ($conn->more_results()) {
        if (!$conn->next_result()) {
            error_log('Schema import next_result failed: ' . $conn->error);
            return false;
        }
        // Exhaust every result set from multi_query.
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }

    $check = $conn->query("SHOW TABLES LIKE 'users'");
    return $check && $check->num_rows > 0;
}

// Try MySQL first, fallback to SQLite
$conn = null;
$using_sqlite = false;

try {
    // MySQLi throws exceptions by default in PHP 8.1+
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    if (!ensure_schema_ready($conn)) {
        throw new Exception('Schema initialization failed. Please ensure social_welfare.sql exists and is valid.');
    }
} catch (Exception $e) {
    die('
        <div style="font-family: system-ui, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ffcaca; border-radius: 8px; background: #fff5f5; color: #333;">
            <h2 style="color: #d32f2f; margin-top: 0;">Database Connection Failed</h2>
            <p>It looks like your MySQL database is not running, or the <code>social_welfare</code> database hasn\'t been set up yet.</p>
            <h3>How to fix this:</h3>
            <ol style="line-height: 1.6;">
                <li>Start the <strong>MySQL</strong> service (using XAMPP, WAMP, or similar).</li>
                <li>Open your database manager (like phpMyAdmin at <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>).</li>
                <li>Create a new database named <strong>social_welfare</strong>.</li>
                <li>Import the <strong>social_welfare.sql</strong> file (located in your project folder) into this database.</li>
            </ol>
            <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
                <strong>Technical Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
            </p>
        </div>
    ');
}

/**
 * Sanitize input to prevent XSS
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim((string)$data)));
}

/**
 * Compatibility fallback for environments without mbstring.
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null) {
        return strlen((string)$string);
    }
}

/**
 * Compatibility fallback for environments without fileinfo.
 */
if (!function_exists('mime_content_type')) {
    function mime_content_type($filename) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $type = finfo_file($finfo, $filename);
                finfo_close($finfo);
                if (!empty($type)) {
                    return $type;
                }
            }
        }

        $ext = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
        $map = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }
}

/**
 * Internal helper to infer bind types for prepared statements.
 */
function db_param_types(array $params) {
    $types = '';
    foreach ($params as $p) {
        if (is_int($p)) {
            $types .= 'i';
        } elseif (is_float($p)) {
            $types .= 'd';
        } elseif (is_null($p)) {
            $types .= 's';
        } else {
            $types .= 's';
        }
    }
    return $types;
}

/**
 * Execute a query with optional parameters.
 *
 * Supported call styles:
 * - db_query("SELECT ...", [$param1, ...])
 * - db_query($conn, "SELECT ...", [$param1, ...]) // legacy style
 */
function db_query($arg1, $arg2 = [], $arg3 = []) {
    global $conn, $using_sqlite;

    $db = $conn;
    $sql = $arg1;
    $params = $arg2;

    // Legacy style: db_query($conn, $sql, $params)
    if (($arg1 instanceof mysqli) || ($arg1 instanceof SQLite3)) {
        $db = $arg1;
        $sql = (string)$arg2;
        $params = $arg3;
    }

    if (!is_array($params)) {
        $params = [];
    }

    if ($using_sqlite) {
        if (empty($params)) {
            return $db->query($sql);
        }

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        foreach (array_values($params) as $i => $param) {
            $index = $i + 1;
            if (is_int($param)) {
                $stmt->bindValue($index, $param, SQLITE3_INTEGER);
            } elseif (is_float($param)) {
                $stmt->bindValue($index, $param, SQLITE3_FLOAT);
            } elseif (is_null($param)) {
                $stmt->bindValue($index, null, SQLITE3_NULL);
            } else {
                $stmt->bindValue($index, (string)$param, SQLITE3_TEXT);
            }
        }

        return $stmt->execute();
    }

    // MySQL
    try {
        if (empty($params)) {
            return $db->query($sql);
        }

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $types = db_param_types($params);
        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        if (!$ok) {
            return false;
        }

        // For SELECT-like queries, return result set; for INSERT/UPDATE/DELETE return bool.
        $result = $stmt->get_result();
        if ($result !== false) {
            return $result;
        }
        return true;
    } catch (Throwable $e) {
        error_log('db_query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return false;
    }
}

/**
 * Fetch a row.
 *
 * Supported call styles:
 * - db_fetch("SELECT ... WHERE id=?", [$id])   // returns first row or null
 * - db_fetch($result)                          // iterates result row-by-row
 */
function db_fetch($queryOrResult, $params = []) {
    global $using_sqlite;

    if (is_string($queryOrResult)) {
        $result = db_query($queryOrResult, $params);
        if ($result === false || $result === true) {
            return null;
        }
        if ($using_sqlite) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            return $row ?: null;
        }
        $row = $result->fetch_assoc();
        return $row ?: null;
    }

    if ($queryOrResult instanceof mysqli_result) {
        $row = $queryOrResult->fetch_assoc();
        return $row ?: null;
    }

    if ($queryOrResult instanceof SQLite3Result) {
        $row = $queryOrResult->fetchArray(SQLITE3_ASSOC);
        return $row ?: null;
    }

    return null;
}

/**
 * Generate unique application ID: SW2026-XXXX
 */
function generateAppID($conn) {
    global $using_sqlite;
    $year = date('Y');
    
    if ($using_sqlite) {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM applications");
        $row = $result->fetchArray(SQLITE3_ASSOC);
    } else {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM applications");
        $row = $result->fetch_assoc();
    }
    
    $next = $row['cnt'] + 1;
    return 'SW' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotifications($conn, $user_id) {
    global $using_sqlite;
    
    if ($using_sqlite) {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
    }
    
    return $row['cnt'];
}
