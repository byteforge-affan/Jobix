<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('company');

$user = currentUser();
$company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);
if (!$company) {
    Database::query("INSERT INTO companies (user_id, company_name) VALUES (?,?)", [$user['id'], $user['name']]);
    $company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);
}

$pageTitle  = 'Company Dashboard';
$activePage = 'dashboard';

$totalJobs   = Database::count("SELECT COUNT(*) FROM jobs WHERE company_id = ?", [$company['id']]);
$activeJobs  = Database::count("SELECT COUNT(*) FROM jobs WHERE company_id = ? AND is_active = 1", [$company['id']]);
$totalApps   = Database::count("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id = ?", [$company['id']]);
$newApps     = Database::count("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='pending'", [$company['id']]);
$shortlisted = Database::count("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='shortlisted'", [$company['id']]);
$hired       = Database::count("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='hired'", [$company['id']]);

$recentJobs = Database::fetchAll("
  SELECT j.*, (SELECT COUNT(*) FROM applications WHERE job_id=j.id) AS app_count
  FROM jobs j WHERE j.company_id = ?
  ORDER BY j.created_at DESC LIMIT 5", [$company['id']]);

$recentApps = Database::fetchAll("
  SELECT a.*, j.title AS job_title, u.name AS candidate_name, cd.headline
  FROM applications a
  JOIN jobs j ON a.job_id = j.id
  JOIN candidates cd ON a.candidate_id = cd.id
  JOIN users u ON cd.user_id = u.id
  WHERE j.company_id = ?
  ORDER BY a.applied_at DESC LIMIT 6", [$company['id']]);

// Chart: applications per job
$appsPerJob = Database::fetchAll("
  SELECT j.title, COUNT(a.id) AS cnt
  FROM jobs j
  LEFT JOIN applications a ON a.job_id = j.id
  WHERE j.company_id = ?
  GROUP BY j.id ORDER BY cnt DESC LIMIT 6", [$company['id']]);

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<?php if (!$company['company_name'] || $company['company_name'] === $user['name']): ?>
<div class="alert alert-info d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-info-circle-fill"></i>
  <span>Complete your <a href="<?= BASE_URL ?>/company/profile.php" class="fw-600">company profile</a> to start attracting top talent.</span>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon primary"><i class="bi bi-briefcase-fill"></i></div>
      <div>
        <div class="stat-value"><?= $activeJobs ?></div>
        <div class="stat-label">Active Jobs</div>
        <div class="stat-trend"><span class="text-muted"><?= $totalJobs ?> total</span></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon warning"><i class="bi bi-file-text-fill"></i></div>
      <div>
        <div class="stat-value"><?= $totalApps ?></div>
        <div class="stat-label">Total Applications</div>
        <div class="stat-trend up"><?= $newApps ?> pending review</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon info"><i class="bi bi-person-check-fill"></i></div>
      <div>
        <div class="stat-value"><?= $shortlisted ?></div>
        <div class="stat-label">Shortlisted</div>
        <div class="stat-trend">Candidates</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon success"><i class="bi bi-trophy-fill"></i></div>
      <div>
        <div class="stat-value"><?= $hired ?></div>
        <div class="stat-label">Hired</div>
        <div class="stat-trend up">Successfully hired</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><h5 class="card-title">Applications per Job</h5></div>
      <div class="card-body">
        <div class="chart-container"><canvas id="appsChart"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title">Recent Applications</h5>
        <a href="<?= BASE_URL ?>/company/applications.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentApps)): ?>
          <div class="empty-state py-4">
            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
            <h5>No applications yet</h5>
            <p>Post a job to start receiving applications.</p>
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($recentApps as $app): ?>
            <a href="<?= BASE_URL ?>/company/application_detail.php?id=<?= $app['id'] ?>"
               class="list-group-item list-group-item-action px-4 py-3 d-flex align-items-center gap-3">
              <img src="<?= avatarUrl(null, $app['candidate_name']) ?>" alt="" class="avatar-sm flex-shrink-0">
              <div class="flex-1 min-w-0">
                <div class="fw-600" style="font-size:13.5px"><?= e($app['candidate_name']) ?></div>
                <div class="text-muted" style="font-size:12px"><?= e($app['job_title']) ?></div>
              </div>
              <div class="text-end">
                <?= badgeStatus($app['status']) ?>
                <div class="text-muted mt-1" style="font-size:11px"><?= timeAgo($app['applied_at']) ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Active Jobs -->
<div class="card">
  <div class="card-header">
    <h5 class="card-title">Your Job Listings</h5>
    <a href="<?= BASE_URL ?>/company/post_job.php" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Post New Job
    </a>
  </div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr><th>Job Title</th><th>Type</th><th>Location</th><th>Deadline</th><th>Applications</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($recentJobs)): ?>
          <tr><td colspan="7">
            <div class="empty-state py-4">
              <div class="empty-state-icon"><i class="bi bi-briefcase"></i></div>
              <h5>No jobs posted yet</h5>
              <p>Start hiring by posting your first job.</p>
              <a href="<?= BASE_URL ?>/company/post_job.php" class="btn btn-primary btn-sm">Post a Job</a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php foreach ($recentJobs as $j): ?>
          <tr>
            <td><div class="fw-600"><?= e($j['title']) ?></div></td>
            <td><?= jobTypeBadge($j['type']) ?></td>
            <td class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($j['location']) ?></td>
            <td class="text-muted"><?= $j['deadline'] ? date('M j', strtotime($j['deadline'])) : '–' ?></td>
            <td><span class="badge bg-primary-subtle text-primary"><?= $j['app_count'] ?></span></td>
            <td><?= badgeStatus($j['is_active'] ? 'active' : 'inactive') ?></td>
            <td>
              <a href="<?= BASE_URL ?>/company/edit_job.php?id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-pencil"></i></a>
              <a href="<?= BASE_URL ?>/company/applications.php?job_id=<?= $j['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon ms-1"><i class="bi bi-people"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div></div></div>

<?php
$labels = array_map(fn($r) => substr($r['title'], 0, 20) . (strlen($r['title']) > 20 ? '…' : ''), $appsPerJob);
$counts = array_column($appsPerJob, 'cnt');
$extraScripts = '<script>
new Chart(document.getElementById("appsChart"), {
  type: "doughnut",
  data: {
    labels: ' . json_encode($labels) . ',
    datasets: [{ data: ' . json_encode($counts) . ',
      backgroundColor: ["#4f46e5","#06b6d4","#10b981","#f59e0b","#ef4444","#8b5cf6"],
      borderWidth: 2, borderColor: "#fff"
    }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:"bottom", labels:{ font:{ size:11 } } } } }
});
</script>';
include __DIR__ . '/../views/partials/footer.php';
?>
