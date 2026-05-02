<?php
/**
 * Manage Schemes (Admin) - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireAdmin();

$page_title = 'Manage Schemes';
$uid = $_SESSION['user_id'];

if (isset($_GET['ajax_mark_read'])) {
    db_query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$uid]);
    echo json_encode(['ok'=>true]); exit;
}

$success = $error = '';
$action = sanitize($_GET['action'] ?? 'list');
$edit_scheme = null;

// ---- Handle DELETE ----
if ($action === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    db_query("DELETE FROM schemes WHERE id=?", [$del_id]);
    header('Location: manage_schemes.php?success=Scheme+deleted+successfully');
    exit;
}

// ---- Handle TOGGLE STATUS ----
if ($action === 'toggle' && isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    db_query("UPDATE schemes SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id=?", [$tid]);
    header('Location: manage_schemes.php?success=Status+updated');
    exit;
}

// ---- Load scheme for editing ----
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $edit_scheme = db_fetch("SELECT * FROM schemes WHERE id=?", [$edit_id]);
    if (!$edit_scheme) { header('Location: manage_schemes.php'); exit; }
}

// ---- Handle ADD / EDIT FORM SUBMIT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $eligibility = sanitize($_POST['eligibility'] ?? '');
    $benefits = sanitize($_POST['benefits'] ?? '');
    $category = sanitize($_POST['category'] ?? 'Other');
    $min_age = (int)($_POST['min_age'] ?? 0);
    $max_age = (int)($_POST['max_age'] ?? 120);
    $max_income = (float)($_POST['max_income'] ?? 999999);
    $eligible_cats = isset($_POST['eligible_categories']) ? implode(',', array_map('sanitize', $_POST['eligible_categories'])) : 'All';
    $required_docs = sanitize($_POST['required_documents'] ?? '');
    $last_date = sanitize($_POST['last_date'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    $scheme_id_edit = (int)($_POST['scheme_id'] ?? 0);

    if (empty($title) || empty($description)) {
        $error = 'Title and Description are required.';
    } elseif ($min_age > $max_age) {
        $error = 'Minimum age cannot be greater than maximum age.';
    } else {
        if ($scheme_id_edit) {
            // Update existing
            $result = db_query("UPDATE schemes SET title=?,description=?,eligibility=?,benefits=?,category=?,min_age=?,max_age=?,max_income=?,eligible_categories=?,required_documents=?,last_date=?,status=? WHERE id=?",
                [$title,$description,$eligibility,$benefits,$category,$min_age,$max_age,$max_income,$eligible_cats,$required_docs,$last_date,$status,$scheme_id_edit]);
        } else {
            // Insert new
            $result = db_query("INSERT INTO schemes (title,description,eligibility,benefits,category,min_age,max_age,max_income,eligible_categories,required_documents,last_date,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$title,$description,$eligibility,$benefits,$category,$min_age,$max_age,$max_income,$eligible_cats,$required_docs,$last_date,$status,$uid]);
        }

        if ($result) {
            header('Location: manage_schemes.php?success=' . ($scheme_id_edit ? 'Scheme+updated' : 'Scheme+added+successfully'));
            exit;
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}



// ---- Fetch all schemes ----
$schemes = db_query("SELECT s.*, u.name as creator FROM schemes s LEFT JOIN users u ON s.created_by=u.id ORDER BY s.created_at DESC");

if (isset($_GET['success'])) $success = sanitize($_GET['success']);

$categories_list = ['Agriculture','Education','Health','Housing','Employment','Women','Elderly','Disability','Other'];

if (!isset($_GET['embed'])) {
    include 'includes/header.php';
}
?>

<div class="fade-in">
    <div class="section-header">
        <div class="section-title">
            Manage Schemes
            <small>Add, edit, and manage welfare schemes</small>
        </div>
        <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-glass">
            <i class="fas fa-plus me-2"></i>Add New Scheme
        </a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
    <div class="alert-glass success mb-4 auto-dismiss">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="glass-card p-4">
        <h6 style="color:rgba(255,255,255,0.6);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">
            <?= $action === 'edit' ? 'Edit Scheme: ' . htmlspecialchars($edit_scheme['title']) : 'New Scheme' ?>
        </h6>

        <?php if ($error): ?>
        <div class="alert-glass error mb-4">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" class="form-glass">
            <?php if ($edit_scheme): ?>
            <input type="hidden" name="scheme_id" value="<?= $edit_scheme['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Scheme Title *</label>
                    <input type="text" class="form-control" name="title"
                        value="<?= htmlspecialchars($edit_scheme['title'] ?? $_POST['title'] ?? '') ?>"
                        placeholder="Enter scheme title" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Description *</label>
                    <textarea class="form-control" name="description" rows="3"
                        placeholder="Describe the scheme in detail"><?= htmlspecialchars($edit_scheme['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Eligibility Criteria</label>
                    <textarea class="form-control" name="eligibility" rows="2"
                        placeholder="Who is eligible for this scheme"><?= htmlspecialchars($edit_scheme['eligibility'] ?? '') ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Benefits</label>
                    <textarea class="form-control" name="benefits" rows="2"
                        placeholder="What benefits does this scheme provide"><?= htmlspecialchars($edit_scheme['benefits'] ?? '') ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <?php foreach ($categories_list as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($edit_scheme['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= ($edit_scheme['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($edit_scheme['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Last Date to Apply</label>
                    <input type="date" class="form-control" name="last_date"
                        value="<?= htmlspecialchars($edit_scheme['last_date'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Min Age</label>
                    <input type="number" class="form-control" name="min_age"
                        value="<?= $edit_scheme['min_age'] ?? 0 ?>" min="0" max="120">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Max Age</label>
                    <input type="number" class="form-control" name="max_age"
                        value="<?= $edit_scheme['max_age'] ?? 120 ?>" min="0" max="120">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Max Annual Income (₹)</label>
                    <input type="number" class="form-control" name="max_income"
                        value="<?= $edit_scheme['max_income'] ?? 999999 ?>" min="0">
                </div>

                <div class="col-12">
                    <label class="form-label">Eligible Categories</label>
                    <div class="d-flex flex-wrap gap-3 mt-1">
                        <?php
                        $ec = isset($edit_scheme) ? explode(',', $edit_scheme['eligible_categories']) : ['All'];
                        foreach (['All','General','OBC','SC','ST','EWS'] as $cat):
                        ?>
                        <label class="d-flex align-items-center gap-2" style="color:rgba(255,255,255,0.8);cursor:pointer;font-size:14px;">
                            <input type="checkbox" name="eligible_categories[]" value="<?= $cat ?>"
                                <?= in_array($cat, $ec) ? 'checked' : '' ?>>
                            <?= $cat ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Required Documents</label>
                    <input type="text" class="form-control" name="required_documents"
                        value="<?= htmlspecialchars($edit_scheme['required_documents'] ?? '') ?>"
                        placeholder="e.g. Aadhar Card, Income Certificate, Bank Passbook">
                </div>

                <div class="col-12 mt-3 d-flex gap-3 flex-wrap">
                    <button type="submit" class="btn btn-glass">
                        <i class="fas fa-save me-2"></i><?= $action === 'edit' ? 'Update Scheme' : 'Add Scheme' ?>
                    </button>
                    <a href="admin_portal.php?tab=schemes" class="btn btn-glass-outline">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- Schemes List -->
    <div class="glass-card p-0">
        <div class="p-4 pb-0">
            <div class="filter-bar mb-4" style="background:rgba(255,255,255,0.05);">
                <div class="search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" id="schemeSearch" placeholder="Search schemes...">
                </div>
                <select id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories_list as $cat): ?>
                    <option value="<?= strtolower($cat) ?>"><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="p-4 pt-0">
            <div class="responsive-table">
                <table class="glass-table" id="schemesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Age Range</th>
                            <th>Max Income</th>
                            <th>Last Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach ($schemes as $s): ?>
                        <tr class="scheme-card-wrap" data-title="<?= strtolower(htmlspecialchars($s['title'])) ?>" data-category="<?= strtolower($s['category']) ?>">
                            <td data-label="#"><?= $i++ ?></td>
                            <td data-label="Title">
                                <div style="font-weight:600;color:#fff;"><?= htmlspecialchars($s['title']) ?></div>
                                <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:2px;">
                                    <?= htmlspecialchars(substr($s['description'], 0, 60)) ?>...
                                </div>
                            </td>
                            <td data-label="Category">
                                <span style="background:rgba(255,255,255,0.1);padding:3px 10px;border-radius:10px;font-size:12px;">
                                    <?= htmlspecialchars($s['category']) ?>
                                </span>
                            </td>
                            <td data-label="Age" style="font-size:13px;"><?= $s['min_age'] ?>–<?= $s['max_age'] ?> yrs</td>
                            <td data-label="Income" style="font-size:13px;">₹<?= number_format($s['max_income']) ?></td>
                            <td data-label="Last Date" style="font-size:13px;color:rgba(255,255,255,0.6);">
                                <?= $s['last_date'] ? date('d M Y', strtotime($s['last_date'])) : '—' ?>
                            </td>
                            <td data-label="Status">
                                <a href="?action=toggle&id=<?= $s['id'] ?>" class="status-badge <?= $s['status'] === 'active' ? 'status-approved' : 'status-rejected' ?>" style="text-decoration:none;cursor:pointer;" title="Click to toggle">
                                    <?= ucfirst($s['status']) ?>
                                </a>
                            </td>
                            <td data-label="Actions">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-glass-outline" style="padding:5px 12px;font-size:12px;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $s['id'] ?>"
                                        class="btn btn-danger-glass" style="padding:5px 12px;font-size:12px;"
                                        data-confirm="Are you sure you want to delete this scheme? This cannot be undone.">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Filter schemes table
document.addEventListener('DOMContentLoaded', function() {
    const search = document.getElementById('schemeSearch');
    const catFilter = document.getElementById('categoryFilter');
    function filterTable() {
        const q = search ? search.value.toLowerCase() : '';
        const cat = catFilter ? catFilter.value : '';
        document.querySelectorAll('#schemesTable tbody tr').forEach(function(row) {
            const t = (row.dataset.title || '').toLowerCase();
            const c = (row.dataset.category || '').toLowerCase();
            row.style.display = (!q || t.includes(q)) && (!cat || c === cat) ? '' : 'none';
        });
    }
    if (search) search.addEventListener('input', filterTable);
    if (catFilter) catFilter.addEventListener('change', filterTable);
});
</script>

<?php
if (!isset($_GET['embed'])) {
    include 'includes/footer.php';
}
?>
