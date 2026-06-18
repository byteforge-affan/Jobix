<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('candidate');

$user      = currentUser();
$candidate = Database::fetch("SELECT * FROM candidates WHERE user_id = ?", [$user['id']]);

$pageTitle  = 'My Applications';
$activePage = 'applications';

$statusFilter = $_GET['status'] ?? '';
$params = [$candidate['id']];
$where  = ['a.candidate_id = ?'];
if ($statusFilter) { $where[] = 'a.status = ?'; $params[] = $statusFilter; }

$apps = Database::fetchAll(
    "SELECT a.*, j.title AS job_title, j.type AS job_type, j.location AS job_location,
            j.salary_min, j.salary_max, j.deadline,
            c.company_name, c.logo AS company_logo
     FROM applications a
     JOIN jobs j ON a.job_id = j.id
     JOIN companies c ON j.company_id = c.id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY a.applied_at DESC",
    $params
);

$statusCounts = [];
foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s) {
    $statusCounts[$s] = Database::count(
        "SELECT COUNT(*) FROM applications WHERE candidate_id = ? AND status = ?",
        [$candidate['id'], $s]
    );
}

$trackerMap = ['pending'=>0,'reviewed'=>1,'shortlisted'=>2,'hired'=>4,'rejected'=>4];
$trackerLabels = ['Applied','Reviewed','Shortlisted','Hired'];

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
    <h2>My Applications</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= array_sum($statusCounts) ?> total</p>
  </div>
  <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-primary">
    <i class="bi bi-search me-1"></i> Find More Jobs
  </a>
</div>

<!-- Status filter pills -->
<div class="d-flex gap-2 mb-4 flex-wrap">
  <a href="<?= BASE_URL ?>/candidate/applications.php"
     class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline-secondary' ?>">
    All (<?= array_sum($statusCounts) ?>)
  </a>
  <?php
  $icons = ['pending'=>'hourglass-split','reviewed'=>'eye','shortlisted'=>'person-check','rejected'=>'x-circle','hired'=>'trophy'];
  foreach ($statusCounts as $s => $cnt): ?>
  <a href="?status=<?= $s ?>"
     class="btn btn-sm <?= $statusFilter===$s ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <i class="bi bi-<?= $icons[$s] ?> me-1"></i>
    <?= ucfirst($s) ?> (<?= $cnt ?>)
  </a>
  <?php endforeach; ?>
</div>

<?php if (empty($apps)): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state py-5">
      <div class="empty-state-icon"><i class="bi bi-send"></i></div>
      <h5>No applications <?= $statusFilter ? 'with status "' . ucfirst($statusFilter) . '"' : 'yet' ?></h5>
      <p><?= $statusFilter ? 'Try a different filter.' : 'Start applying to jobs that match your skills.' ?></p>
      <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-primary">Browse Jobs</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($apps as $app): ?>
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="row align-items-center g-3">
          <!-- Company logo + job info -->
          <div class="col-sm-6 col-lg-5">
            <div class="d-flex align-items-center gap-3">
              <img src="<?= logoUrl($app['company_logo'], $app['company_name']) ?>"
                   alt="" style="width:52px;height:52px;border-radius:12px;object-fit:cover;flex-shrink:0;border:1px solid var(--gray-200)">
              <div class="min-w-0">
                <div class="fw-700" style="font-size:15px"><?= e($app['job_title']) ?></div>
                <div class="text-muted small"><?= e($app['company_name']) ?></div>
                <div class="d-flex gap-2 mt-1 flex-wrap">
                  <?= jobTypeBadge($app['job_type']) ?>
                  <?php if ($app['job_location']): ?>
                    <span class="text-muted" style="font-size:12px"><i class="bi bi-geo-alt me-1"></i><?= e($app['job_location']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress tracker -->
          <div class="col-lg-4 d-none d-lg-block">
            <?php
              $step = $app['status'] === 'rejected' ? -1 : $trackerMap[$app['status']] ?? 0;
            ?>
            <?php if ($app['status'] === 'rejected'): ?>
              <div class="text-center">
                <span class="badge bg-danger-subtle text-danger px-3 py-2">
                  <i class="bi bi-x-circle me-1"></i> Application Not Selected
                </span>
              </div>
            <?php else: ?>
            <div class="app-tracker">
              <?php foreach ($trackerLabels as $i => $label): ?>
              <div class="tracker-step <?= $step > $i ? 'done' : ($step === $i ? 'current' : '') ?>">
                <div class="tracker-dot">
                  <?php if ($step > $i): ?><i class="bi bi-check" style="font-size:10px"></i><?php else: ?><?= $i+1 ?><?php endif; ?>
                </div>
                <div class="tracker-label"><?= $label ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Status + date -->
          <div class="col-sm-6 col-lg-3 text-sm-end">
            <?= badgeStatus($app['status']) ?>
            <div class="text-muted small mt-1">Applied <?= timeAgo($app['applied_at']) ?></div>
            <?php if ($app['salary_min'] || $app['salary_max']): ?>
              <div class="text-muted small"><?= formatSalary($app['salary_min'], $app['salary_max']) ?></div>
            <?php endif; ?>
            <?php if ($app['resume']): ?>
              <a href="<?= UPLOAD_URL . e($app['resume']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                <i class="bi bi-file-earmark-text me-1"></i> View Resume
              </a>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($app['cover_letter']): ?>
        <div class="mt-3 pt-3 border-top">
          <div class="text-muted small fw-600 mb-1">Cover Letter</div>
          <p class="text-muted small mb-0"><?= e(substr($app['cover_letter'], 0, 200)) ?><?= strlen($app['cover_letter']) > 200 ? '…' : '' ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
