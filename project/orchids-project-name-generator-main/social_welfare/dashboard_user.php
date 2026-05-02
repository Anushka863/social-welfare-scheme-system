<?php
require_once 'auth.php';
require_user();

$uid = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';

$stmt = $conn->prepare('SELECT id, scheme_name, status, applied_at FROM applications WHERE user_id = ? ORDER BY id DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$applications = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>User Dashboard</h4>
        <a class="btn btn-outline-danger" href="logout.php">Logout</a>
    </div>
    <p>Welcome, <strong><?= htmlspecialchars($name) ?></strong></p>
    <div class="mb-3">
        <a class="btn btn-primary" href="apply_scheme.php">Apply for Scheme</a>
    </div>

    <div class="card">
        <div class="card-header">My Applications</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Scheme</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $applications->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= htmlspecialchars($row['scheme_name']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['applied_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
