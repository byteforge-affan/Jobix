<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('company');

$user    = currentUser();
$company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $appId  = (int)($_POST['app_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending','reviewed','shortlisted','rejected','hired'];

    if ($appId && in_array($status, $allowed)) {
        // Verify the application belongs to this company
        $app = Database::fetch(
            "SELECT a.*, j.company_id, cd.user_id AS candidate_user_id, j.title AS job_title, u.name AS candidate_name
             FROM applications a
             JOIN jobs j ON a.job_id = j.id
             JOIN candidates cd ON a.candidate_id = cd.id
             JOIN users u ON cd.user_id = u.id
             WHERE a.id = ? AND j.company_id = ?",
            [$appId, $company['id']]
        );
        if ($app) {
            Database::query("UPDATE applications SET status = ? WHERE id = ?", [$status, $appId]);

            // Notify candidate
            $msgMap = [
                'reviewed'    => 'Your application for ' . $app['job_title'] . ' has been reviewed.',
                'shortlisted' => 'You have been shortlisted for ' . $app['job_title'] . '!',
                'rejected'    => 'Your application for ' . $app['job_title'] . ' was not selected.',
                'hired'       => 'Congratulations! You have been hired for ' . $app['job_title'] . '!',
            ];
            if (isset($msgMap[$status])) {
                createNotification(
                    $app['candidate_user_id'],
                    'application_status',
                    ucfirst($status) . ': ' . $app['job_title'],
                    $msgMap[$status],
                    '/candidate/applications.php'
                );
            }
            setFlash('success', 'Application status updated to ' . ucfirst($status) . '.');
        }
    }
    redirect('/company/applications.php' . (isset($_GET['job_id']) ? '?job_id=' . (int)$_GET['job_id'] : ''));
}

$pageTitle  = 'Applications';
$activePage = 'applications';

$jobId  = (int)($_GET['job_id'] ?? 0);
$status = $_GET['status'] ?? '';
$params = [$company['id']];
$where  = ['j.company_id = ?'];

if ($jobId)  { $where[] = 'a.job_id = ?';   $params[] = $jobId; }
if ($status) { $where[] = 'a.status = ?';   $params[] = $status; }

$apps = Database::fetchAll(
    "SELECT a.*, j.title AS job_title, j.type AS job_type,
            u.name AS candidate_name, u.avatar AS candidate_avatar,
            cd.id AS cand_id, cd.headline, cd.skills, cd.location AS cand_location, cd.resume
     FROM applications a
     JOIN jobs j ON a.job_id = j.id
     JOIN candidates cd ON a.candidate_id = cd.id
     JOIN users u ON cd.user_id = u.id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY a.applied_at DESC",
    $params
);

// Jobs dropdown for filter
$myJobs = Database::fetchAll("SELECT id, title FROM jobs WHERE company_id = ? ORDER BY title", [$company['id']]);

// Count per status
$statusCounts = [];
foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s) {
    $statusCounts[$s] = Database::count(
        "SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status=?",
        [$company['id'], $s]
    );
}

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
    <h2>Applications</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= count($apps) ?> results</p>
  </div>
</div>

<!-- Summary stats -->
<div class="row g-2 mb-4">
  <?php
  $statusIcons  = ['pending'=>['warning','hourglass-split'],'reviewed'=>['info','eye'],'shortlisted'=>['primary','person-check'],'rejected'=>['danger','x-circle'],'hired'=>['success','trophy']];
  foreach ($statusCounts as $s => $cnt):
    [$col,$icon] = $statusIcons[$s];
  ?>
  <div class="col">
    <a href="?status=<?= $s ?><?= $jobId ? '&job_id='.$jobId : '' ?>"
       class="stat-card text-decoration-none d-flex flex-column gap-1 p-3 <?= $status===$s ? 'border-primary' : '' ?>" style="min-width:90px">
      <div class="stat-icon <?= $col ?>" style="width:36px;height:36px;border-radius:8px;font-size:14px">
        <i class="bi bi-<?= $icon ?>"></i>
      </div>
      <div class="fw-800" style="font-size:20px;line-height:1"><?= $cnt ?></div>
      <div style="font-size:11.5px;color:var(--gray-500);text-transform:capitalize"><?= $s ?></div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="card mb-4">
  <div class="card-body py-3">
    <form class="row g-2 align-items-end">
      <div class="col-sm-5 col-lg-4">
        <select name="job_id" class="form-select">
          <option value="">All Jobs</option>
          <?php foreach ($myJobs as $j): ?>
            <option value="<?= $j['id'] ?>" <?= $jobId === $j['id'] ? 'selected' : '' ?>><?= e($j['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-3 col-lg-2">
        <select name="status" class="form-select">
          <option value="">All Statuses</option>
          <?php foreach (array_keys($statusCounts) as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s ? 'selected' : '' ?> class="text-capitalize"><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
        <a href="<?= BASE_URL ?>/company/applications.php" class="btn btn-outline-secondary ms-1">Clear</a>
      </div>
    </form>
  </div>
</div>

<?php if (empty($apps)): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state py-5">
      <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
      <h5>No applications found</h5>
      <p>Try adjusting the filters above, or post more jobs to attract candidates.</p>
    </div>
  </div>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($apps as $app): ?>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start gap-3 mb-3">
          <img src="<?= avatarUrl($app['candidate_avatar'], $app['candidate_name']) ?>" alt="" class="avatar-lg flex-shrink-0">
          <div class="flex-1 min-w-0">
            <div class="fw-700" style="font-size:15px"><?= e($app['candidate_name']) ?></div>
            <?php if ($app['headline']): ?>
              <div class="text-muted" style="font-size:13px"><?= e($app['headline']) ?></div>
            <?php endif; ?>
            <?php if ($app['cand_location']): ?>
              <div class="text-muted small mt-1"><i class="bi bi-geo-alt me-1"></i><?= e($app['cand_location']) ?></div>
            <?php endif; ?>
          </div>
          <?= badgeStatus($app['status']) ?>
        </div>

        <div class="p-2 rounded-3 mb-3" style="background:var(--gray-50)">
          <div class="fw-600" style="font-size:13px"><?= e($app['job_title']) ?></div>
          <div class="text-muted small"><?= jobTypeBadge($app['job_type']) ?> · Applied <?= timeAgo($app['applied_at']) ?></div>
        </div>

        <?php if ($app['skills']): ?>
        <div class="mb-3 d-flex flex-wrap gap-1">
          <?php foreach (array_slice(explode(',', $app['skills']), 0, 4) as $skill): ?>
            <span class="skill-tag"><?= e(trim($skill)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($app['cover_letter']): ?>
        <blockquote class="border-start border-primary ps-3 mb-3 text-muted small" style="border-width:3px!important">
          <?= e(substr($app['cover_letter'], 0, 160)) ?><?= strlen($app['cover_letter']) > 160 ? '…' : '' ?>
        </blockquote>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div class="d-flex gap-1 flex-wrap">
            <?php foreach (['reviewed','shortlisted','rejected','hired'] as $s): ?>
              <?php if ($s !== $app['status']): ?>
              <form method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                <input type="hidden" name="status" value="<?= $s ?>">
                <?php
                  $btnClass = ['reviewed'=>'outline-info','shortlisted'=>'outline-primary','rejected'=>'outline-danger','hired'=>'success'][$s];
                ?>
                <button class="btn btn-sm btn-<?= $btnClass ?> text-capitalize" style="font-size:11.5px;padding:3px 10px">
                  <?= $s ?>
                </button>
              </form>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php if ($app['resume']): ?>
          <a href="<?= UPLOAD_URL . e($app['resume']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download me-1"></i> Resume
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
