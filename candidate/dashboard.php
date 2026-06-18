<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('candidate');

$user      = currentUser();
$candidate = Database::fetch("SELECT * FROM candidates WHERE user_id = ?", [$user['id']]);
if (!$candidate) {
    Database::query("INSERT INTO candidates (user_id) VALUES (?)", [$user['id']]);
    $candidate = Database::fetch("SELECT * FROM candidates WHERE user_id = ?", [$user['id']]);
}

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$totalApps   = Database::count("SELECT COUNT(*) FROM applications WHERE candidate_id = ?", [$candidate['id']]);
$savedJobs   = Database::count("SELECT COUNT(*) FROM saved_jobs WHERE candidate_id = ?", [$candidate['id']]);
$shortlisted = Database::count("SELECT COUNT(*) FROM applications WHERE candidate_id = ? AND status = 'shortlisted'", [$candidate['id']]);
$hired       = Database::count("SELECT COUNT(*) FROM applications WHERE candidate_id = ? AND status = 'hired'", [$candidate['id']]);

$recentApps = Database::fetchAll(
    "SELECT a.*, j.title AS job_title, j.type AS job_type, j.location AS job_location,
            c.company_name, c.logo AS company_logo
     FROM applications a
     JOIN jobs j ON a.job_id = j.id
     JOIN companies c ON j.company_id = c.id
     WHERE a.candidate_id = ?
     ORDER BY a.applied_at DESC LIMIT 5",
    [$candidate['id']]
);

$recommendedJobs = Database::fetchAll(
    "SELECT j.*, c.company_name, c.logo, cat.name AS cat_name,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count,
            (SELECT COUNT(*) FROM saved_jobs WHERE job_id = j.id AND candidate_id = ?) AS is_saved
     FROM jobs j
     JOIN companies c ON j.company_id = c.id
     JOIN job_categories cat ON j.category_id = cat.id
     WHERE j.is_active = 1
       AND j.id NOT IN (SELECT job_id FROM applications WHERE candidate_id = ?)
     ORDER BY j.created_at DESC LIMIT 4",
    [$candidate['id'], $candidate['id']]
);

// App status history for chart
$appHistory = Database::fetchAll(
    "SELECT status, COUNT(*) AS cnt FROM applications WHERE candidate_id = ? GROUP BY status",
    [$candidate['id']]
);

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<?php if (!$candidate['skills'] && !$candidate['headline']): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-person-badge-fill fs-5"></i>
  <span>Your profile is incomplete. <a href="<?= BASE_URL ?>/candidate/profile.php" class="fw-600">Complete it now</a> to stand out to employers.</span>
  <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon primary"><i class="bi bi-send-fill"></i></div>
      <div>
        <div class="stat-value"><?= $totalApps ?></div>
        <div class="stat-label">Applications Sent</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon warning"><i class="bi bi-bookmark-fill"></i></div>
      <div>
        <div class="stat-value"><?= $savedJobs ?></div>
        <div class="stat-label">Saved Jobs</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon info"><i class="bi bi-person-check-fill"></i></div>
      <div>
        <div class="stat-value"><?= $shortlisted ?></div>
        <div class="stat-label">Shortlisted</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon success"><i class="bi bi-trophy-fill"></i></div>
      <div>
        <div class="stat-value"><?= $hired ?></div>
        <div class="stat-label">Hired</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Recent applications -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Recent Applications</h5>
        <a href="<?= BASE_URL ?>/candidate/applications.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentApps)): ?>
        <div class="empty-state py-4">
          <div class="empty-state-icon"><i class="bi bi-send"></i></div>
          <h5>No applications yet</h5>
          <p>Start applying to jobs that match your skills.</p>
          <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-primary btn-sm">Browse Jobs</a>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($recentApps as $app): ?>
          <div class="list-group-item px-4 py-3 d-flex align-items-center gap-3">
            <img src="<?= logoUrl($app['company_logo'], $app['company_name']) ?>"
                 alt="" style="width:40px;height:40px;border-radius:10px;object-fit:cover;flex-shrink:0">
            <div class="flex-1 min-w-0">
              <div class="fw-600" style="font-size:13.5px"><?= e($app['job_title']) ?></div>
              <div class="text-muted small"><?= e($app['company_name']) ?> · <?= timeAgo($app['applied_at']) ?></div>
            </div>
            <?= badgeStatus($app['status']) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Application breakdown chart -->
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><h5 class="card-title">Application Summary</h5></div>
      <div class="card-body d-flex flex-column">
        <?php if (empty($appHistory)): ?>
        <div class="empty-state py-3">
          <div class="empty-state-icon"><i class="bi bi-pie-chart"></i></div>
          <h5>No data yet</h5>
        </div>
        <?php else: ?>
        <div class="chart-container flex-1"><canvas id="statusChart"></canvas></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recommended Jobs -->
<div class="mb-2 d-flex align-items-center justify-content-between">
  <h5 class="section-title mb-0">Recommended Jobs</h5>
  <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-sm btn-outline-primary">Browse All</a>
</div>
<div class="row g-3">
  <?php foreach ($recommendedJobs as $job): ?>
  <div class="col-md-6">
    <div class="job-card">
      <div class="d-flex align-items-center gap-3">
        <img src="<?= logoUrl($job['logo'], $job['company_name']) ?>" alt="" class="job-card-logo">
        <div class="flex-1 min-w-0">
          <div class="job-card-title"><?= e($job['title']) ?></div>
          <div class="job-card-company"><?= e($job['company_name']) ?></div>
        </div>
        <button class="btn btn-outline-secondary border-0 save-job-btn <?= $job['is_saved'] ? 'text-primary' : '' ?>"
                data-job-id="<?= $job['id'] ?>">
          <i class="bi bi-bookmark<?= $job['is_saved'] ? '-fill' : '' ?>"></i>
        </button>
      </div>
      <div class="job-card-meta">
        <?= jobTypeBadge($job['type']) ?>
        <span><i class="bi bi-geo-alt"></i> <?= e($job['location'] ?? 'Remote') ?></span>
        <span><i class="bi bi-currency-dollar"></i> <?= formatSalary($job['salary_min'], $job['salary_max']) ?></span>
      </div>
      <div class="job-card-footer">
        <span class="text-muted small"><?= $job['app_count'] ?> applicants · <?= timeAgo($job['created_at']) ?></span>
        <a href="<?= BASE_URL ?>/candidate/job_detail.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">Apply Now</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($recommendedJobs)): ?>
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="empty-state py-3">
          <div class="empty-state-icon"><i class="bi bi-briefcase"></i></div>
          <h5>You've applied to all available jobs!</h5>
          <p>Check back soon for new listings.</p>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

</div></div></div>

<?php
$chartLabels = array_column($appHistory, 'status');
$chartData   = array_column($appHistory, 'cnt');
$colorMap = ['pending'=>'#f59e0b','reviewed'=>'#3b82f6','shortlisted'=>'#4f46e5','rejected'=>'#ef4444','hired'=>'#10b981'];
$colors = array_map(fn($s) => $colorMap[$s] ?? '#94a3b8', $chartLabels);

$extraScripts = '<script>
' . (empty($appHistory) ? '' : '
new Chart(document.getElementById("statusChart"), {
  type: "doughnut",
  data: {
    labels: ' . json_encode(array_map('ucfirst', $chartLabels)) . ',
    datasets: [{ data: ' . json_encode($chartData) . ',
      backgroundColor: ' . json_encode($colors) . ',
      borderWidth: 2, borderColor: "#fff"
    }]
  },
  options: { responsive:true, maintainAspectRatio:false, cutout:"65%",
    plugins:{ legend:{ position:"bottom", labels:{ font:{size:12} } } }
  }
});') . '
</script>';
include __DIR__ . '/../views/partials/footer.php';
?>
