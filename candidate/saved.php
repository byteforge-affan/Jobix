<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('candidate');

$user      = currentUser();
$candidate = Database::fetch("SELECT * FROM candidates WHERE user_id = ?", [$user['id']]);

// Remove saved job
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $jobId = (int)($_POST['job_id'] ?? 0);
    if ($jobId) {
        Database::query("DELETE FROM saved_jobs WHERE candidate_id = ? AND job_id = ?", [$candidate['id'], $jobId]);
        setFlash('success', 'Job removed from saved list.');
    }
    redirect('/candidate/saved.php');
}

$pageTitle  = 'Saved Jobs';
$activePage = 'saved';

$savedJobs = Database::fetchAll(
    "SELECT j.*, c.company_name, c.logo, cat.name AS cat_name, sj.saved_at,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND candidate_id = ?) AS already_applied
     FROM saved_jobs sj
     JOIN jobs j ON sj.job_id = j.id
     JOIN companies c ON j.company_id = c.id
     JOIN job_categories cat ON j.category_id = cat.id
     WHERE sj.candidate_id = ?
     ORDER BY sj.saved_at DESC",
    [$candidate['id'], $candidate['id']]
);

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
    <h2>Saved Jobs</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= count($savedJobs) ?> bookmarked</p>
  </div>
  <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-outline-primary">
    <i class="bi bi-search me-1"></i> Browse More
  </a>
</div>

<?php if (empty($savedJobs)): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state py-5">
      <div class="empty-state-icon"><i class="bi bi-bookmark"></i></div>
      <h5>No saved jobs</h5>
      <p>Bookmark jobs while browsing to easily find them later.</p>
      <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-primary">Find Jobs</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($savedJobs as $job): ?>
  <div class="col-md-6">
    <div class="job-card">
      <div class="d-flex align-items-start gap-3">
        <img src="<?= logoUrl($job['logo'], $job['company_name']) ?>" alt="" class="job-card-logo">
        <div class="flex-1 min-w-0">
          <a href="<?= BASE_URL ?>/candidate/job_detail.php?id=<?= $job['id'] ?>"
             class="job-card-title text-decoration-none text-dark d-block"><?= e($job['title']) ?></a>
          <div class="job-card-company"><?= e($job['company_name']) ?></div>
        </div>
        <!-- Remove bookmark -->
        <form method="POST" class="d-inline flex-shrink-0">
          <?= csrf_field() ?>
          <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
          <button class="btn btn-link p-0 text-primary" title="Remove from saved" data-bs-toggle="tooltip">
            <i class="bi bi-bookmark-fill fs-5"></i>
          </button>
        </form>
      </div>

      <div class="job-card-meta">
        <?= jobTypeBadge($job['type']) ?>
        <?php if ($job['location']): ?>
          <span><i class="bi bi-geo-alt"></i> <?= e($job['location']) ?></span>
        <?php endif; ?>
        <span><i class="bi bi-cash"></i> <?= formatSalary($job['salary_min'], $job['salary_max']) ?></span>
        <span class="text-muted"><i class="bi bi-tag"></i> <?= e($job['cat_name']) ?></span>
      </div>

      <?php if (!$job['is_active']): ?>
        <div class="alert alert-warning py-2 px-3 mb-0 small">
          <i class="bi bi-exclamation-triangle me-1"></i> This job is no longer accepting applications.
        </div>
      <?php elseif ($job['deadline'] && strtotime($job['deadline']) < time()): ?>
        <div class="alert alert-danger py-2 px-3 mb-0 small">
          <i class="bi bi-calendar-x me-1"></i> Application deadline has passed.
        </div>
      <?php endif; ?>

      <div class="job-card-footer">
        <div>
          <div class="text-muted small"><?= $job['app_count'] ?> applicants</div>
          <div class="text-muted" style="font-size:11.5px">Saved <?= timeAgo($job['saved_at']) ?></div>
        </div>
        <?php if ($job['already_applied']): ?>
          <span class="badge bg-success-subtle text-success py-2 px-3">
            <i class="bi bi-check-circle me-1"></i> Applied
          </span>
        <?php elseif ($job['is_active']): ?>
          <a href="<?= BASE_URL ?>/candidate/job_detail.php?id=<?= $job['id'] ?>"
             class="btn btn-sm btn-primary">Apply Now</a>
        <?php else: ?>
          <span class="badge bg-secondary">Closed</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
