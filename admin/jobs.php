<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['job_id'] ?? 0);
    if ($action === 'delete' && $id) {
        Database::query("DELETE FROM jobs WHERE id = ?", [$id]);
        setFlash('success', 'Job deleted.');
    } elseif ($action === 'toggle' && $id) {
        Database::query("UPDATE jobs SET is_active = NOT is_active WHERE id = ?", [$id]);
        setFlash('success', 'Job status updated.');
    }
    redirect('/admin/jobs.php');
}

$user = currentUser();
$pageTitle  = 'All Jobs';
$activePage = 'jobs';

$search = trim($_GET['search'] ?? '');
$type   = $_GET['type'] ?? '';
$params = [];
$where  = ['1=1'];

if ($search) { $where[] = "(j.title LIKE ? OR c.company_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($type)   { $where[] = "j.type = ?"; $params[] = $type; }

$jobs = Database::fetchAll("
  SELECT j.*, c.company_name, cat.name AS cat_name,
         (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count
  FROM jobs j
  JOIN companies c ON j.company_id = c.id
  JOIN job_categories cat ON j.category_id = cat.id
  WHERE " . implode(' AND ', $where) . "
  ORDER BY j.created_at DESC", $params);

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
    <h2>All Jobs</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= count($jobs) ?> listings found</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body py-3">
    <form class="row g-2">
      <div class="col-sm-5">
        <div class="position-relative">
          <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted" style="font-size:13px"></i>
          <input type="text" name="search" class="form-control ps-5" placeholder="Search jobs or companies…" value="<?= e($search) ?>">
        </div>
      </div>
      <div class="col-sm-3 col-lg-2">
        <select name="type" class="form-select">
          <option value="">All Types</option>
          <?php foreach (['full-time','part-time','remote','internship','contract'] as $t): ?>
            <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a href="<?= BASE_URL ?>/admin/jobs.php" class="btn btn-outline-secondary ms-1">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Job Title</th>
          <th>Company</th>
          <th>Type</th>
          <th>Location</th>
          <th>Applications</th>
          <th>Posted</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($jobs)): ?>
          <tr><td colspan="9">
            <div class="empty-state py-5">
              <div class="empty-state-icon"><i class="bi bi-briefcase"></i></div>
              <h5>No jobs found</h5>
            </div>
          </td></tr>
        <?php else: ?>
          <?php foreach ($jobs as $i => $j): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td>
              <div class="fw-600"><?= e($j['title']) ?></div>
              <div class="text-muted" style="font-size:12px"><?= e($j['cat_name']) ?></div>
            </td>
            <td><?= e($j['company_name']) ?></td>
            <td><?= jobTypeBadge($j['type']) ?></td>
            <td class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($j['location']) ?></td>
            <td><span class="badge bg-primary-subtle text-primary"><?= $j['app_count'] ?></span></td>
            <td class="text-muted"><?= date('M j, Y', strtotime($j['created_at'])) ?></td>
            <td><?= badgeStatus($j['is_active'] ? 'active' : 'inactive') ?></td>
            <td>
              <div class="d-flex gap-1">
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary btn-icon" title="Toggle status" data-bs-toggle="tooltip">
                    <i class="bi bi-toggle-<?= $j['is_active'] ? 'on' : 'off' ?>"></i>
                  </button>
                </form>
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger btn-icon"
                          data-confirm="Delete this job and all its applications?"
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
