<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['company_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'verify') {
        Database::query("UPDATE companies SET is_verified = NOT is_verified WHERE id = ?", [$id]);
        setFlash('success', 'Company verification status updated.');
    } elseif ($action === 'delete') {
        Database::query("DELETE FROM users WHERE id = (SELECT user_id FROM companies WHERE id = ?)", [$id]);
        setFlash('success', 'Company deleted.');
    }
    redirect('/admin/companies.php');
}

$user = currentUser();
$pageTitle  = 'Companies';
$activePage = 'companies';

$companies = Database::fetchAll("
  SELECT c.*, u.email, u.is_active, u.created_at AS reg_date,
         (SELECT COUNT(*) FROM jobs WHERE company_id = c.id) AS job_count
  FROM companies c
  JOIN users u ON c.user_id = u.id
  ORDER BY c.created_at DESC
");

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
    <h2>Companies</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= count($companies) ?> registered</p>
  </div>
</div>

<div class="row g-3">
  <?php foreach ($companies as $c): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="<?= logoUrl($c['logo'], $c['company_name']) ?>" alt="" style="width:52px;height:52px;border-radius:12px;object-fit:cover;">
          <div class="flex-1 min-w-0">
            <div class="fw-700"><?= e($c['company_name']) ?></div>
            <div class="text-muted" style="font-size:12.5px"><?= e($c['industry'] ?? 'Industry not set') ?></div>
          </div>
          <?php if ($c['is_verified']): ?>
            <span class="badge bg-success-subtle text-success"><i class="bi bi-patch-check me-1"></i>Verified</span>
          <?php endif; ?>
        </div>
        <div class="row g-2 mb-3" style="font-size:12.5px">
          <div class="col-6 d-flex align-items-center gap-1 text-muted">
            <i class="bi bi-geo-alt"></i> <?= e($c['location'] ?? 'N/A') ?>
          </div>
          <div class="col-6 d-flex align-items-center gap-1 text-muted">
            <i class="bi bi-briefcase"></i> <?= $c['job_count'] ?> jobs
          </div>
          <div class="col-12 d-flex align-items-center gap-1 text-muted">
            <i class="bi bi-envelope"></i> <?= e($c['email']) ?>
          </div>
        </div>
        <div class="d-flex gap-2">
          <form method="POST" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
            <button class="btn btn-sm <?= $c['is_verified'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
              <i class="bi bi-<?= $c['is_verified'] ? 'x-circle' : 'patch-check' ?> me-1"></i>
              <?= $c['is_verified'] ? 'Unverify' : 'Verify' ?>
            </button>
          </form>
          <form method="POST" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
            <button class="btn btn-sm btn-outline-danger"
                    data-confirm="Delete <?= e($c['company_name']) ?> and all its data?">
              <i class="bi bi-trash me-1"></i> Delete
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
