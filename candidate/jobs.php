<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('candidate');

$user      = currentUser();
$candidate = Database::fetch("SELECT * FROM candidates WHERE user_id = ?", [$user['id']]);

$pageTitle  = 'Find Jobs';
$activePage = 'jobs';

// Filters
$q        = trim($_GET['q'] ?? '');
$type     = $_GET['type'] ?? '';
$cat      = (int)($_GET['cat'] ?? 0);
$location = trim($_GET['location'] ?? '');

$where  = ['j.is_active = 1'];
$params = [];

if ($q)        { $where[] = "(j.title LIKE ? OR c.company_name LIKE ? OR j.description LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($type)     { $where[] = "j.type = ?"; $params[] = $type; }
if ($cat)      { $where[] = "j.category_id = ?"; $params[] = $cat; }
if ($location) { $where[] = "j.location LIKE ?"; $params[] = "%$location%"; }

$jobs = Database::fetchAll(
    "SELECT j.*, c.company_name, c.logo, cat.name AS cat_name,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND candidate_id = ?) AS already_applied,
            (SELECT COUNT(*) FROM saved_jobs WHERE job_id = j.id AND candidate_id = ?) AS is_saved
     FROM jobs j
     JOIN companies c ON j.company_id = c.id
     JOIN job_categories cat ON j.category_id = cat.id
     WHERE " . implode(' AND ', $where) . "
     ORDER BY j.created_at DESC",
    array_merge([$candidate['id'], $candidate['id']], $params)
);

$categories = Database::fetchAll("SELECT cat.*, COUNT(j.id) AS job_count FROM job_categories cat LEFT JOIN jobs j ON j.category_id=cat.id AND j.is_active=1 GROUP BY cat.id ORDER BY job_count DESC");
$types      = ['full-time'=>'Full Time','part-time'=>'Part Time','remote'=>'Remote','internship'=>'Internship','contract'=>'Contract'];

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">

<!-- Search Hero -->
<div class="search-hero mb-4">
  <h1>Find Your Next Role</h1>
  <p class="mb-3" style="opacity:.85">Explore <?= Database::count("SELECT COUNT(*) FROM jobs WHERE is_active=1") ?> open positions from top companies</p>
  <form method="GET">
    <div class="search-bar-wrapper">
      <i class="bi bi-search text-muted ms-2"></i>
      <input type="text" name="q" placeholder="Job title, company, or keyword…"
             value="<?= e($q) ?>" style="font-size:15px">
      <input type="text" name="location" placeholder="Location…" value="<?= e($location) ?>"
             style="max-width:180px;border-left:1px solid var(--gray-200)">
      <button type="submit" class="btn btn-primary px-4">Search</button>
    </div>
  </form>
</div>

<div class="row g-4">
  <!-- Filters sidebar -->
  <div class="col-lg-3">
    <form method="GET" id="filterForm">
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <input type="hidden" name="location" value="<?= e($location) ?>">

      <div class="filter-panel mb-3">
        <div class="filter-section-title">Job Type</div>
        <?php foreach ($types as $v => $l): ?>
        <div class="form-check mb-1">
          <input class="form-check-input" type="radio" name="type" id="type_<?= $v ?>"
                 value="<?= $v ?>" <?= $type === $v ? 'checked' : '' ?>
                 onchange="this.form.submit()">
          <label class="form-check-label" for="type_<?= $v ?>"><?= $l ?></label>
        </div>
        <?php endforeach; ?>
        <?php if ($type): ?>
          <a href="?q=<?= urlencode($q) ?>&location=<?= urlencode($location) ?>&cat=<?= $cat ?>"
             class="small text-danger d-block mt-1">Clear type</a>
        <?php endif; ?>
      </div>

      <div class="filter-panel">
        <div class="filter-section-title">Category</div>
        <?php foreach ($categories as $c): ?>
        <div class="form-check mb-1 d-flex align-items-center justify-content-between">
          <div>
            <input class="form-check-input" type="radio" name="cat" id="cat_<?= $c['id'] ?>"
                   value="<?= $c['id'] ?>" <?= $cat === $c['id'] ? 'checked' : '' ?>
                   onchange="this.form.submit()">
            <label class="form-check-label" for="cat_<?= $c['id'] ?>"><?= e($c['name']) ?></label>
          </div>
          <span class="badge bg-primary-subtle text-primary" style="font-size:10.5px"><?= $c['job_count'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php if ($cat): ?>
          <a href="?q=<?= urlencode($q) ?>&location=<?= urlencode($location) ?>&type=<?= urlencode($type) ?>"
             class="small text-danger d-block mt-1">Clear category</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Job cards -->
  <div class="col-lg-9">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <p class="mb-0 text-muted small fw-600"><?= count($jobs) ?> job<?= count($jobs) !== 1 ? 's' : '' ?> found</p>
      <?php if ($q || $type || $cat || $location): ?>
        <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i> Clear all filters
        </a>
      <?php endif; ?>
    </div>

    <?php if (empty($jobs)): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state py-5">
          <div class="empty-state-icon"><i class="bi bi-search"></i></div>
          <h5>No jobs match your search</h5>
          <p>Try different keywords or remove some filters.</p>
          <a href="<?= BASE_URL ?>/candidate/jobs.php" class="btn btn-outline-primary">Clear filters</a>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($jobs as $job): ?>
      <div class="col-md-6">
        <div class="job-card">
          <div class="d-flex align-items-start gap-3">
            <img src="<?= logoUrl($job['logo'], $job['company_name']) ?>" alt="" class="job-card-logo">
            <div class="flex-1 min-w-0">
              <a href="<?= BASE_URL ?>/candidate/job_detail.php?id=<?= $job['id'] ?>"
                 class="job-card-title text-decoration-none text-dark"><?= e($job['title']) ?></a>
              <div class="job-card-company mt-1"><?= e($job['company_name']) ?></div>
            </div>
            <button class="btn btn-link p-0 save-job-btn <?= $job['is_saved'] ? 'text-primary' : 'text-muted' ?>"
                    data-job-id="<?= $job['id'] ?>" style="font-size:18px;line-height:1">
              <i class="bi bi-bookmark<?= $job['is_saved'] ? '-fill' : '' ?>"></i>
            </button>
          </div>
          <div class="job-card-meta">
            <?= jobTypeBadge($job['type']) ?>
            <?php if ($job['location']): ?>
              <span><i class="bi bi-geo-alt"></i> <?= e($job['location']) ?></span>
            <?php endif; ?>
            <span><i class="bi bi-cash"></i> <?= formatSalary($job['salary_min'], $job['salary_max']) ?></span>
          </div>
          <div class="job-card-footer">
            <div>
              <div class="text-muted small"><?= $job['app_count'] ?> applicants</div>
              <div class="text-muted" style="font-size:11.5px"><?= timeAgo($job['created_at']) ?></div>
            </div>
            <?php if ($job['already_applied']): ?>
              <span class="badge bg-success-subtle text-success py-2 px-3">
                <i class="bi bi-check-circle me-1"></i> Applied
              </span>
            <?php else: ?>
              <a href="<?= BASE_URL ?>/candidate/job_detail.php?id=<?= $job['id'] ?>"
                 class="btn btn-sm btn-primary">Apply Now</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
