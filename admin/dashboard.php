<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('admin');

$user = currentUser();
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Stats
$totalUsers      = Database::count("SELECT COUNT(*) FROM users WHERE role != 'admin'");
$totalCompanies  = Database::count("SELECT COUNT(*) FROM companies");
$totalCandidates = Database::count("SELECT COUNT(*) FROM candidates");
$totalJobs       = Database::count("SELECT COUNT(*) FROM jobs WHERE is_active = 1");
$totalApps       = Database::count("SELECT COUNT(*) FROM applications");
$newUsersThisMonth = Database::count("SELECT COUNT(*) FROM users WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");

// Recent jobs
$recentJobs = Database::fetchAll("
  SELECT j.*, c.company_name, cat.name AS category_name,
         (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count
  FROM jobs j
  JOIN companies c ON j.company_id = c.id
  JOIN job_categories cat ON j.category_id = cat.id
  ORDER BY j.created_at DESC LIMIT 8
");

// Recent applications
$recentApps = Database::fetchAll("
  SELECT a.*, j.title AS job_title, c.company_name,
         u.name AS candidate_name
  FROM applications a
  JOIN jobs j ON a.job_id = j.id
  JOIN companies c ON j.company_id = c.id
  JOIN candidates cd ON a.candidate_id = cd.id
  JOIN users u ON cd.user_id = u.id
  ORDER BY a.applied_at DESC LIMIT 6
");

// Chart: jobs per month (last 6 months)
$jobsPerMonth = Database::fetchAll("
  SELECT MONTHNAME(created_at) AS month, COUNT(*) AS count
  FROM jobs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY MONTH(created_at), MONTHNAME(created_at)
  ORDER BY MONTH(created_at)
");

$appsPerMonth = Database::fetchAll("
  SELECT MONTHNAME(applied_at) AS month, COUNT(*) AS count
  FROM applications WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY MONTH(applied_at), MONTHNAME(applied_at)
  ORDER BY MONTH(applied_at)
");

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">

<?= renderFlash() ?>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="stat-value"><?= number_format($totalUsers) ?></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-trend up"><i class="bi bi-arrow-up-right"></i> <?= $newUsersThisMonth ?> this month</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon success"><i class="bi bi-building-fill"></i></div>
      <div>
        <div class="stat-value"><?= number_format($totalCompanies) ?></div>
        <div class="stat-label">Companies</div>
        <div class="stat-trend up"><i class="bi bi-arrow-up-right"></i> Active hiring</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon warning"><i class="bi bi-briefcase-fill"></i></div>
      <div>
        <div class="stat-value"><?= number_format($totalJobs) ?></div>
        <div class="stat-label">Active Jobs</div>
        <div class="stat-trend up"><i class="bi bi-arrow-up-right"></i> Live listings</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon info"><i class="bi bi-file-text-fill"></i></div>
      <div>
        <div class="stat-value"><?= number_format($totalApps) ?></div>
        <div class="stat-label">Applications</div>
        <div class="stat-trend up"><i class="bi bi-arrow-up-right"></i> Total received</div>
      </div>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Jobs Posted – Last 6 Months</h5>
      </div>
      <div class="card-body">
        <div class="chart-container"><canvas id="jobsChart"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Applications – Last 6 Months</h5>
      </div>
      <div class="card-body">
        <div class="chart-container"><canvas id="appsChart"></canvas></div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Jobs table -->
<div class="row g-3">
  <div class="col-lg-7">
    <div class="table-wrapper">
      <div class="card-header">
        <h5 class="card-title">Recent Job Listings</h5>
        <a href="<?= BASE_URL ?>/admin/jobs.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Job Title</th>
              <th>Company</th>
              <th>Type</th>
              <th>Apps</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentJobs as $job): ?>
            <tr>
              <td>
                <div class="fw-600" style="font-size:13.5px"><?= e($job['title']) ?></div>
                <div class="text-muted" style="font-size:12px"><?= e($job['category_name']) ?></div>
              </td>
              <td><?= e($job['company_name']) ?></td>
              <td><?= jobTypeBadge($job['type']) ?></td>
              <td><span class="badge bg-primary-subtle text-primary"><?= $job['app_count'] ?></span></td>
              <td><?= badgeStatus($job['is_active'] ? 'active' : 'inactive') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="table-wrapper">
      <div class="card-header">
        <h5 class="card-title">Recent Applications</h5>
        <a href="<?= BASE_URL ?>/admin/applications.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr><th>Candidate</th><th>Job</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentApps as $app): ?>
            <tr>
              <td>
                <div class="fw-600" style="font-size:13px"><?= e($app['candidate_name']) ?></div>
                <div class="text-muted" style="font-size:11.5px"><?= timeAgo($app['applied_at']) ?></div>
              </td>
              <td style="font-size:13px"><?= e($app['job_title']) ?></td>
              <td><?= badgeStatus($app['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</div><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /app-shell -->

<?php
$chartMonths = array_column($jobsPerMonth, 'month');
$chartJobCounts = array_column($jobsPerMonth, 'count');
$chartAppMonths = array_column($appsPerMonth, 'month');
$chartAppCounts = array_column($appsPerMonth, 'count');

$extraScripts = '<script>
const jobsCtx = document.getElementById("jobsChart");
new Chart(jobsCtx, {
  type: "bar",
  data: {
    labels: ' . json_encode($chartMonths) . ',
    datasets: [{
      label: "Jobs Posted",
      data: ' . json_encode($chartJobCounts) . ',
      backgroundColor: "rgba(79,70,229,.2)",
      borderColor: "rgba(79,70,229,1)",
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,grid:{color:"rgba(0,0,0,.04)"}},x:{grid:{display:false}}} }
});

const appsCtx = document.getElementById("appsChart");
new Chart(appsCtx, {
  type: "line",
  data: {
    labels: ' . json_encode($chartAppMonths) . ',
    datasets: [{
      label: "Applications",
      data: ' . json_encode($chartAppCounts) . ',
      fill: true,
      backgroundColor: "rgba(6,182,212,.1)",
      borderColor: "rgba(6,182,212,1)",
      borderWidth: 2,
      tension: 0.4,
      pointBackgroundColor: "rgba(6,182,212,1)",
    }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,grid:{color:"rgba(0,0,0,.04)"}},x:{grid:{display:false}}} }
});
</script>';
include __DIR__ . '/../views/partials/footer.php';
?>
