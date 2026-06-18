<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('company');

$user    = currentUser();
$company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);

// Handle delete/toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $jid    = (int)($_POST['job_id'] ?? 0);
    if ($action === 'delete' && $jid) {
        Database::query("DELETE FROM jobs WHERE id = ? AND company_id = ?", [$jid, $company['id']]);
        setFlash('success', 'Job deleted.');
    } elseif ($action === 'toggle' && $jid) {
        Database::query("UPDATE jobs SET is_active = NOT is_active WHERE id = ? AND company_id = ?", [$jid, $company['id']]);
        setFlash('success', 'Job status updated.');
    }
    redirect('/company/jobs.php');
}

$pageTitle  = 'My Jobs';
$activePage = 'jobs';

$filter = $_GET['filter'] ?? '';
$where  = ['company_id = ?'];
$params = [$company['id']];
if ($filter === 'active')   { $where[] = 'is_active = 1'; }
if ($filter === 'inactive') { $where[] = 'is_active = 0'; }

$jobs = Database::fetchAll(
    "SELECT j.*, cat.name AS cat_name,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status = 'pending') AS pending_count
     FROM jobs j JOIN job_categories cat ON j.category_id = cat.id
     WHERE " . implode(' AND ', $where) . " ORDER BY j.created_at DESC",
    $params
);

$totalActive   = Database::count("SELECT COUNT(*) FROM jobs WHERE company_id = ? AND is_active = 1", [$company['id']]);
$totalInactive = Database::count("SELECT COUNT(*) FROM jobs WHERE company_id = ? AND is_active = 0", [$company['id']]);

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
    <h2>My Job Listings</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= $totalActive ?> active · <?= $totalInactive ?> inactive</p>
  </div>
  <a href="<?= BASE_URL ?>/company/post_job.php" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i> Post New Job
  </a>
</div>

<div class="d-flex gap-2 mb-4">
  <a href="<?= BASE_URL ?>/company/jobs.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-outline-secondary' ?>">All (<?= $totalActive + $totalInactive ?>)</a>
  <a href="?filter=active"   class="btn btn-sm <?= $filter==='active'   ? 'btn-primary' : 'btn-outline-secondary' ?>">Active (<?= $totalActive ?>)</a>
  <a href="?filter=inactive" class="btn btn-sm <?= $filter==='inactive' ? 'btn-primary' : 'btn-outline-secondary' ?>">Inactive (<?= $totalInactive ?>)</a>
</div>

<?php if (empty($jobs)): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state py-5">
      <div class="empty-state-icon"><i class="bi bi-briefcase"></i></div>
      <h5>No jobs posted yet</h5>
      <p>Post your first job to start receiving applications from top candidates.</p>
      <a href="<?= BASE_URL ?>/company/post_job.php" class="btn btn-primary">Post a Job</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="table-wrapper">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Job Title</th>
          <th>Category</th>
          <th>Type</th>
          <th>Location</th>
          <th>Deadline</th>
          <th>Applications</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($jobs as $j): ?>
        <tr>
          <td>
            <div class="fw-600"><?= e($j['title']) ?></div>
            <div class="text-muted" style="font-size:11.5px"><?= timeAgo($j['created_at']) ?></div>
          </td>
          <td class="text-muted"><?= e($j['cat_name']) ?></td>
          <td><?= jobTypeBadge($j['type']) ?></td>
          <td class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($j['location'] ?: '–') ?></td>
          <td class="text-muted">
            <?php if ($j['deadline']): ?>
              <?php $expired = strtotime($j['deadline']) < time(); ?>
              <span class="<?= $expired ? 'text-danger' : '' ?>"><?= date('M j, Y', strtotime($j['deadline'])) ?></span>
            <?php else: ?>–<?php endif; ?>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/company/applications.php?job_id=<?= $j['id'] ?>" class="text-decoration-none">
              <span class="badge bg-primary-subtle text-primary fw-600"><?= $j['app_count'] ?></span>
              <?php if ($j['pending_count'] > 0): ?>
                <span class="badge bg-warning-subtle text-warning ms-1"><?= $j['pending_count'] ?> new</span>
              <?php endif; ?>
            </a>
          </td>
          <td><?= badgeStatus($j['is_active'] ? 'active' : 'inactive') ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= BASE_URL ?>/company/edit_job.php?id=<?= $j['id'] ?>"
                 class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <a href="<?= BASE_URL ?>/company/applications.php?job_id=<?= $j['id'] ?>"
                 class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="tooltip" title="Applications">
                <i class="bi bi-people"></i>
              </a>
              <form method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary btn-icon" data-bs-toggle="tooltip"
                        title="<?= $j['is_active'] ? 'Deactivate' : 'Activate' ?>">
                  <i class="bi bi-<?= $j['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                </button>
              </form>
              <form method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                <button class="btn btn-sm btn-outline-danger btn-icon"
                        data-confirm="Delete '<?= e($j['title']) ?>'? This will remove all applications too."
                        data-bs-toggle="tooltip" title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
