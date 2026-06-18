<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('admin');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle' && $id) {
        Database::query("UPDATE users SET is_active = NOT is_active WHERE id = ?", [$id]);
        setFlash('success', 'User status updated.');
    } elseif ($action === 'delete' && $id) {
        Database::query("DELETE FROM users WHERE id = ? AND role != 'admin'", [$id]);
        setFlash('success', 'User deleted.');
    }
    redirect('/admin/users.php');
}

$user = currentUser();
$pageTitle  = 'Users';
$activePage = 'users';

$search = trim($_GET['search'] ?? '');
$role   = $_GET['role'] ?? '';
$params = [];
$where  = ["u.role != 'admin'"];

if ($search) { $where[] = "(u.name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($role)   { $where[] = "u.role = ?"; $params[] = $role; }

$sql = "SELECT u.*, 
        CASE WHEN u.role='company' THEN c.company_name ELSE NULL END AS company_name,
        CASE WHEN u.role='candidate' THEN (SELECT COUNT(*) FROM applications a JOIN candidates cd ON a.candidate_id=cd.id WHERE cd.user_id=u.id) ELSE 0 END AS app_count
        FROM users u
        LEFT JOIN companies c ON c.user_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY u.created_at DESC";

$users = Database::fetchAll($sql, $params);

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<div class="page-header">
  <div>
    <h2>Users</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= count($users) ?> total users</p>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form class="row g-2 align-items-end">
      <div class="col-sm-6 col-lg-5">
        <div class="position-relative">
          <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted" style="font-size:13px"></i>
          <input type="text" name="search" class="form-control ps-5" placeholder="Search by name or email…" value="<?= e($search) ?>">
        </div>
      </div>
      <div class="col-sm-3 col-lg-2">
        <select name="role" class="form-select">
          <option value="">All Roles</option>
          <option value="company"   <?= $role === 'company'   ? 'selected' : '' ?>>Company</option>
          <option value="candidate" <?= $role === 'candidate' ? 'selected' : '' ?>>Candidate</option>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-secondary ms-1">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>User</th>
          <th>Role</th>
          <th>Email</th>
          <th>Registered</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7">
            <div class="empty-state py-5">
              <div class="empty-state-icon"><i class="bi bi-people"></i></div>
              <h5>No users found</h5>
              <p>Try adjusting your search or filters.</p>
            </div>
          </td></tr>
        <?php else: ?>
          <?php foreach ($users as $i => $u): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="<?= avatarUrl($u['avatar'], $u['name']) ?>" alt="" class="avatar-sm">
                <div>
                  <div class="fw-600"><?= e($u['name']) ?></div>
                  <?php if ($u['company_name']): ?>
                    <div class="text-muted" style="font-size:12px"><?= e($u['company_name']) ?></div>
                  <?php elseif ($u['app_count'] > 0): ?>
                    <div class="text-muted" style="font-size:12px"><?= $u['app_count'] ?> application<?= $u['app_count'] > 1 ? 's' : '' ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <span class="badge <?= $u['role'] === 'company' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' ?> text-capitalize">
                <?= e($u['role']) ?>
              </span>
            </td>
            <td class="text-muted"><?= e($u['email']) ?></td>
            <td class="text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td><?= badgeStatus($u['is_active'] ? 'active' : 'inactive') ?></td>
            <td>
              <div class="d-flex gap-1">
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="tooltip"
                          title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                    <i class="bi bi-<?= $u['is_active'] ? 'pause' : 'play' ?>"></i>
                  </button>
                </form>
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger btn-icon"
                          data-confirm="Delete this user? This cannot be undone."
                          data-bs-toggle="tooltip" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
