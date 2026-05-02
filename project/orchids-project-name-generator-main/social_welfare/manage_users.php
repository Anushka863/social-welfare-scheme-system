<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();

$page_title = 'Manage Users';

// ───────── Filters ─────────
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// ───────── Pagination ─────────
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// ───────── Build WHERE clause with parameters ─────────
$where_parts = ["role = 'user'"];
$params = [];
$param_types = '';

// Add search filter
if ($search !== '') {
    $like_search = "%$search%";
    $where_parts[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = $like_search;
    $params[] = $like_search;
    $params[] = $like_search;
    $param_types .= 'sss';
}

// Add status filter
if ($status === 'active') {
    $where_parts[] = "is_active = 1";
} elseif ($status === 'inactive') {
    $where_parts[] = "is_active = 0";
}

$where = "WHERE " . implode(" AND ", $where_parts);

// ───────── Count total users matching filters ─────────
$count_sql = "SELECT COUNT(*) as total FROM users $where";
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

if (!$count_stmt->execute()) {
    error_log("Count query failed: " . $count_stmt->error);
    $total = 0;
} else {
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total = $count_row['total'] ?? 0;
}

$total_pages = max(1, ceil($total / $per_page));

// ───────── Fetch users with application details ─────────
$sql = "SELECT u.*, 
                COALESCE(COUNT(a.id), 0) as total_applications,
                COALESCE(SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END), 0) as approved_count,
                COALESCE(SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                COALESCE(SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected_count,
                MAX(a.applied_at) as last_application_date
         FROM users u
         LEFT JOIN applications a ON u.id = a.user_id
         $where
         GROUP BY u.id
         ORDER BY u.created_at DESC 
         LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $users = [];
} else {
    // Build the complete bind_param string
    $bind_types = $param_types . 'ii';
    $bind_params = array_merge($params, [$per_page, $offset]);
    
    if (!empty($bind_params)) {
        $stmt->bind_param($bind_types, ...$bind_params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $users = [];
    } else {
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// ───────── Stats ─────────
$total_all = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_active = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' AND is_active=1")->fetch_assoc()['c'];
$total_inactive = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' AND is_active=0")->fetch_assoc()['c'];

// ───────── Auto-populate test data if no users exist ─────────
$auto_populated = false;
if ($total_all == 0) {
    $auto_populated = true;
    
    $test_users = [
        ['Rajesh Kumar', 'rajesh@example.com', '9876543210', password_hash('Test@123', PASSWORD_DEFAULT), '1985-03-15', 'Male', '123 Main Street, Delhi 110001', '123456789012', 180000, 'OBC'],
        ['Priya Singh', 'priya@example.com', '9123456789', password_hash('Test@123', PASSWORD_DEFAULT), '1990-07-22', 'Female', '456 Oak Avenue, Mumbai 400001', '234567890123', 220000, 'General'],
        ['Amit Patel', 'amit@example.com', '9987654321', password_hash('Test@123', PASSWORD_DEFAULT), '1988-11-05', 'Male', '789 Farm Road, Gujarat 380001', '345678901234', 150000, 'SC'],
        ['Neha Sharma', 'neha@example.com', '9234567890', password_hash('Test@123', PASSWORD_DEFAULT), '1992-05-18', 'Female', '321 Educational Lane, Bangalore 560001', '456789012345', 280000, 'General'],
        ['Vivek Gupta', 'vivek@example.com', '9345678901', password_hash('Test@123', PASSWORD_DEFAULT), '1987-09-30', 'Male', '555 Business Park, Hyderabad 500001', '567890123456', 320000, 'EWS'],
        ['Pooja Deshmukh', 'pooja@example.com', '8765432109', password_hash('Test@123', PASSWORD_DEFAULT), '1995-01-12', 'Female', '789 Knowledge Park, Pune 411001', '678901234567', 195000, 'ST'],
        ['Suresh Reddy', 'suresh@example.com', '9654321098', password_hash('Test@123', PASSWORD_DEFAULT), '1986-08-25', 'Male', '234 Agricultural Zone, Telangana 500002', '789012345678', 165000, 'OBC'],
    ];

    foreach ($test_users as $user) {
        $insert_sql = "INSERT INTO users (name, email, phone, password, dob, gender, address, aadhar, annual_income, category, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 1)";
        $insert_stmt = $conn->prepare($insert_sql);
        if ($insert_stmt) {
            $insert_stmt->bind_param("ssssssssi", $user[0], $user[1], $user[2], $user[3], $user[4], $user[5], $user[6], $user[7], $user[8]);
            if ($insert_stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Add sample applications
                $schemes = $conn->query("SELECT id FROM schemes WHERE status='active' LIMIT 3");
                if ($schemes && $schemes->num_rows > 0) {
                    $app_count = 0;
                    while ($scheme = $schemes->fetch_assoc()) {
                        $app_id = 'SW' . date('Y') . '-' . str_pad($user_id * 100 + $app_count, 4, '0', STR_PAD_LEFT);
                        $status_options = ['pending', 'approved', 'under_review'];
                        $status = $status_options[array_rand($status_options)];
                        $days_ago = rand(1, 30);
                        $applied_date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
                        
                        $app_sql = "INSERT INTO applications (application_id, user_id, scheme_id, status, applied_at) VALUES (?, ?, ?, ?, ?)";
                        $app_stmt = $conn->prepare($app_sql);
                        if ($app_stmt) {
                            $app_stmt->bind_param("sisis", $app_id, $user_id, $scheme['id'], $status, $applied_date);
                            $app_stmt->execute();
                        }
                        $app_count++;
                    }
                }
            }
            $insert_stmt->close();
        }
    }
    
    // Refresh counts after population
    $total_all = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
    $total_active = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' AND is_active=1")->fetch_assoc()['c'];
    $total_inactive = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' AND is_active=0")->fetch_assoc()['c'];
    
    // Re-fetch users after population
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bind_types = $param_types . 'ii';
        $bind_params = array_merge($params, [$per_page, $offset]);
        if (!empty($bind_params)) {
            $stmt->bind_param($bind_types, ...$bind_params);
        }
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    }
}
                            $app_stmt->execute();
                        }
                        $app_count++;
                    }
                }
            }
            $insert_stmt->close();
        }
    }
    
    // Refresh counts after population
    $total_all = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
    $total_active = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' AND is_active=1")->fetch_assoc()['c'];
    $total_inactive = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user' AND is_active=0")->fetch_assoc()['c'];
    
    // Re-fetch users after population
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bind_types = $param_types . 'ii';
        $bind_params = array_merge($params, [$per_page, $offset]);
        if (!empty($bind_params)) {
            $stmt->bind_param($bind_types, ...$bind_params);
        }
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">

<?php if ($auto_populated && $total_all > 0): ?>
<div style="background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; gap: 15px;">
        <span style="font-size: 32px;">✨</span>
        <div>
            <strong style="font-size: 16px;">Test Data Auto-Populated!</strong>
            <div style="font-size: 13px; margin-top: 5px; opacity: 0.9;">We've created <?= $total_all ?> test applicants with sample applications. You can now see all applicant details below!</div>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="margin-bottom: 30px;">
    <h2 style="color: #333; margin-bottom: 20px;">👥 Manage Applicants</h2>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: 700;"><?= $total_all ?></div>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">Total Applicants</div>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: 700;"><?= $total_active ?></div>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">Active Users</div>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: 700;"><?= $total_inactive ?></div>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.9;">Inactive Users</div>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto; gap: 12px; align-items: end;">
        <div>
            <label style="display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 6px;">Search by Name, Email or Phone</label>
            <input type="text" name="search" placeholder="e.g., Rajesh, rajesh@example.com, 9876543210" 
                   value="<?= htmlspecialchars($search) ?>" 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 6px;">Filter by Status</label>
            <select name="status" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white;">
                <option value="">All Statuses</option>
                <option value="active" <?= $status=='active'?'selected':'' ?>>✓ Active</option>
                <option value="inactive" <?= $status=='inactive'?'selected':'' ?>>✗ Inactive</option>
            </select>
        </div>
        <button type="submit" style="padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s;">
            🔍 Search
        </button>
    </form>
</div>

<?php if (empty($users)): ?>
    <div style="text-align: center; padding: 40px; background: #f5f5f5; border-radius: 8px; margin-top: 20px;">
        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
        <p style="font-size: 18px; color: #666; margin: 0;">No applicants found</p>
        <p style="color: #999; margin-top: 10px;">Register citizens to see them here</p>
    </div>
<?php else: ?>

<div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f0f0f0; border-bottom: 2px solid #ddd;">
            <tr>
                <th style="padding: 15px; text-align: left; border-right: 1px solid #ddd;">Applicant Details</th>
                <th style="padding: 15px; text-align: left; border-right: 1px solid #ddd;">Contact Info</th>
                <th style="padding: 15px; text-align: left; border-right: 1px solid #ddd;">Category</th>
                <th style="padding: 15px; text-align: left; border-right: 1px solid #ddd;">Income</th>
                <th style="padding: 15px; text-align: center; border-right: 1px solid #ddd;">Applications</th>
                <th style="padding: 15px; text-align: center; border-right: 1px solid #ddd;">Status</th>
                <th style="padding: 15px; text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr style="border-bottom: 1px solid #eee; hover: background #f9f9f9;">
                <td style="padding: 15px; border-right: 1px solid #eee;">
                    <div style="font-weight: 600; color: #333;">ID: <?= $u['id'] ?></div>
                    <div style="color: #666; margin: 5px 0;">👤 <?= htmlspecialchars($u['name']) ?></div>
                    <div style="color: #999; font-size: 12px;">Joined: <?= date('d-M-Y', strtotime($u['created_at'])) ?></div>
                </td>
                <td style="padding: 15px; border-right: 1px solid #eee;">
                    <div style="color: #333; margin: 3px 0;">📧 <?= htmlspecialchars($u['email']) ?></div>
                    <div style="color: #333; margin: 3px 0;">📱 <?= htmlspecialchars($u['phone']) ?></div>
                    <?php if ($u['aadhar']): ?>
                    <div style="color: #666; font-size: 12px; margin: 3px 0;">Aadhar: ****<?= substr($u['aadhar'], -4) ?></div>
                    <?php endif; ?>
                </td>
                <td style="padding: 15px; border-right: 1px solid #eee;">
                    <span style="display: inline-block; background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                        <?= htmlspecialchars($u['category']) ?>
                    </span>
                    <div style="color: #999; font-size: 12px; margin-top: 5px;">
                        DOB: <?= date('d-M-Y', strtotime($u['dob'])) ?><br>
                        Gender: <?= $u['gender'] ?>
                    </div>
                </td>
                <td style="padding: 15px; border-right: 1px solid #eee;">
                    <div style="font-weight: 600; color: #333;">₹<?= number_format($u['annual_income'], 0) ?></div>
                    <div style="color: #999; font-size: 12px; margin-top: 5px;">Annual Income</div>
                </td>
                <td style="padding: 15px; border-right: 1px solid #eee; text-align: center;">
                    <div style="font-weight: 600; color: #333; font-size: 16px;"><?= $u['total_applications'] ?? 0 ?></div>
                    <div style="color: #999; font-size: 12px; margin-top: 3px;">Total</div>
                    <?php if ($u['total_applications'] > 0): ?>
                    <div style="color: #4caf50; font-size: 11px; margin-top: 5px;">✓ <?= $u['approved_count'] ?? 0 ?> Approved</div>
                    <div style="color: #ff9800; font-size: 11px;">⏳ <?= $u['pending_count'] ?? 0 ?> Pending</div>
                    <div style="color: #f44336; font-size: 11px;">✗ <?= $u['rejected_count'] ?? 0 ?> Rejected</div>
                    <?php endif; ?>
                </td>
                <td style="padding: 15px; border-right: 1px solid #eee; text-align: center;">
                    <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; 
                                 <?= $u['is_active'] ? 'background: #c8e6c9; color: #2e7d32;' : 'background: #ffcdd2; color: #c62828;' ?>">
                        <?= $u['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                    </span>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <a href="scheme_details.php?user_id=<?= $u['id'] ?>" 
                       style="display: inline-block; background: #2196F3; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; margin: 2px;">
                        View Profile
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div style="margin-top: 30px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
    <?php if ($page > 1): ?>
        <a href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" 
           style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #2196F3; transition: all 0.3s;">
            ← First
        </a>
    <?php endif; ?>
    
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
            <span style="padding: 8px 12px; border: 1px solid #2196F3; border-radius: 4px; background: #2196F3; color: white; font-weight: 600;">
                <?= $i ?>
            </span>
        <?php else: ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" 
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #2196F3; transition: all 0.3s;">
                <?= $i ?>
            </a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" 
           style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #2196F3; transition: all 0.3s;">
            Last →
        </a>
    <?php endif; ?>
</div>
<div style="margin-top: 15px; text-align: center; color: #666; font-size: 12px;">
    Showing <?= count($users) > 0 ? (($page - 1) * $per_page + 1) : 0 ?> - <?= min($page * $per_page, $total) ?> of <?= $total ?> applicants
</div>
<?php endif; ?>

<?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>