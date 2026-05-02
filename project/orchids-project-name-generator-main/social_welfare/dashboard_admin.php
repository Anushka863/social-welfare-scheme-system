<?php
require_once 'auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
    $applicationId = (int)$_POST['application_id'];
    $status = $_POST['status'];
    if (in_array($status, ['approved', 'rejected', 'pending'], true)) {
        $stmt = $conn->prepare('UPDATE applications SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $applicationId);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: dashboard_admin.php');
    exit;
}

$sql = "SELECT a.id, u.name AS user_name, a.scheme_name, a.status, a.applied_at
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        ORDER BY a.id DESC";
$rows = $conn->query($sql);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Admin Dashboard</h4>
        <a class="btn btn-outline-danger" href="logout.php">Logout</a>
    </div>
    <div class="card">
        <div class="card-header">All Applications</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User Name</th>
                    <th>Scheme</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $rows->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['scheme_name']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['applied_at']) ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="application_id" value="<?= (int)$row['id'] ?>">
                                <select class="form-select form-select-sm" name="status">
                                    <option value="pending">pending</option>
                                    <option value="approved">approved</option>
                                    <option value="rejected">rejected</option>
                                </select>
                                <button class="btn btn-sm btn-primary" type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
