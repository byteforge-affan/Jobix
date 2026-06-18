<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('candidate');

$user      = currentUser();
$candidate = Database::fetch("SELECT * FROM candidates WHERE user_id = ?", [$user['id']]);
$jobId     = (int)($_GET['id'] ?? 0);

$job = Database::fetch(
    "SELECT j.*, c.company_name, c.logo, c.description AS company_desc, c.location AS company_loc,
            c.website, c.company_size, cat.name AS cat_name, c.id AS company_id
     FROM jobs j
     JOIN companies c ON j.company_id = c.id
     JOIN job_categories cat ON j.category_id = cat.id
     WHERE j.id = ? AND j.is_active = 1",
    [$jobId]
);

if (!$job) { setFlash('danger', 'Job not found or is no longer active.'); redirect('/candidate/jobs.php'); }

$alreadyApplied = Database::count(
    "SELECT COUNT(*) FROM applications WHERE job_id = ? AND candidate_id = ?",
    [$jobId, $candidate['id']]
);

$isSaved = Database::count(
    "SELECT COUNT(*) FROM saved_jobs WHERE job_id = ? AND candidate_id = ?",
    [$jobId, $candidate['id']]
);

// Increment views
Database::query("UPDATE jobs SET views = views + 1 WHERE id = ?", [$jobId]);

// Handle apply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyApplied) {
    verify_csrf();
    $coverLetter = trim($_POST['cover_letter'] ?? '');

    // Resume: use uploaded or fall back to profile resume
    $resume = $candidate['resume'];
    if (!empty($_FILES['resume']['name'])) {
        $uploaded = uploadFile($_FILES['resume'], 'resumes', ['pdf','doc','docx'], 5242880);
        if ($uploaded) $resume = $uploaded;
        else { setFlash('danger', 'Resume upload failed. Use PDF/DOC under 5MB.'); redirect('/candidate/job_detail.php?id=' . $jobId); }
    }

    Database::query(
        "INSERT INTO applications (job_id, candidate_id, cover_letter, resume) VALUES (?,?,?,?)",
        [$jobId, $candidate['id'], $coverLetter, $resume]
    );

    // Notify company user
    $companyUser = Database::fetch("SELECT u.id FROM users u JOIN companies c ON c.user_id = u.id WHERE c.id = ?", [$job['company_id']]);
    if ($companyUser) {
        createNotification(
            $companyUser['id'],
            'new_application',
            'New Application Received',
            $user['name'] . ' applied for ' . $job['title'],
            '/company/applications.php'
        );
    }

    setFlash('success', 'Application submitted! Good luck! 🎉');
    redirect('/candidate/applications.php');
}

// Similar jobs
$similarJobs = Database::fetchAll(
    "SELECT j.*, c.company_name, c.logo
     FROM jobs j JOIN companies c ON j.company_id = c.id
     WHERE j.category_id = ? AND j.id != ? AND j.is_active = 1
     LIMIT 4",
    [$job['category_id'], $jobId]
);

$pageTitle  = e($job['title']);
$activePage = 'jobs';

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<div class="row g-4">
  <!-- Main content -->
  <div class="col-lg-8">

    <!-- Job Header -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex gap-4 align-items-start">
          <img src="<?= logoUrl($job['logo'], $job['company_name']) ?>" alt=""
               style="width:72px;height:72px;border-radius:16px;object-fit:cover;border:2px solid var(--gray-200);flex-shrink:0">
          <div class="flex-1">
            <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><?= e($job['title']) ?></h1>
            <div class="d-flex flex-wrap gap-3 text-muted mb-3" style="font-size:13.5px">
              <span class="fw-600 text-dark"><?= e($job['company_name']) ?></span>
              <?php if ($job['job_loc'] ?? $job['location']): ?>
                <span><i class="bi bi-geo-alt me-1"></i><?= e($job['location']) ?></span>
              <?php endif; ?>
              <span><i class="bi bi-eye me-1"></i><?= number_format($job['views']) ?> views</span>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <?= jobTypeBadge($job['type']) ?>
              <span class="badge bg-secondary-subtle text-secondary"><?= e($job['cat_name']) ?></span>
              <?php if ($job['salary_min'] || $job['salary_max']): ?>
                <span class="badge bg-success-subtle text-success">
                  <i class="bi bi-cash me-1"></i><?= formatSalary($job['salary_min'], $job['salary_max']) ?>
                </span>
              <?php endif; ?>
              <?php if ($job['deadline']): ?>
                <span class="badge <?= strtotime($job['deadline']) < time() ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' ?>">
                  <i class="bi bi-calendar me-1"></i>Deadline: <?= date('M j, Y', strtotime($job['deadline'])) ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title">Job Description</h5></div>
      <div class="card-body" style="line-height:1.8">
        <?= nl2br(e($job['description'])) ?>
      </div>
    </div>

    <?php if ($job['requirements']): ?>
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title">Requirements</h5></div>
      <div class="card-body" style="line-height:1.8">
        <?= nl2br(e($job['requirements'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($job['benefits']): ?>
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title">Benefits &amp; Perks</h5></div>
      <div class="card-body" style="line-height:1.8">
        <?= nl2br(e($job['benefits'])) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Apply form -->
    <?php if ($alreadyApplied): ?>
    <div class="card border-success">
      <div class="card-body d-flex align-items-center gap-3 py-4">
        <div class="stat-icon success" style="width:52px;height:52px;border-radius:14px;font-size:22px">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <div>
          <div class="fw-700 fs-6">Application Submitted</div>
          <div class="text-muted small">You've already applied for this position. We'll notify you of any updates.</div>
        </div>
        <a href="<?= BASE_URL ?>/candidate/applications.php" class="btn btn-outline-success ms-auto">Track Application</a>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-header"><h5 class="card-title">Apply for this Job</h5></div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="mb-4">
            <label class="form-label">Cover Letter <span class="text-muted fw-normal">(recommended)</span></label>
            <textarea name="cover_letter" class="form-control" rows="6"
                      placeholder="Tell the employer why you're a great fit for this role. Mention your relevant experience, skills, and why you're excited about this opportunity…"><?= e($_POST['cover_letter'] ?? '') ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Resume / CV</label>
            <?php if ($candidate['resume']): ?>
            <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded-3" style="background:var(--gray-50);border:1px solid var(--gray-200)">
              <i class="bi bi-file-pdf text-danger fs-5"></i>
              <span class="small fw-600">Profile resume on file</span>
              <span class="text-muted small">— or upload a new one below</span>
            </div>
            <?php endif; ?>
            <div class="file-input-wrapper">
              <input type="file" name="resume" accept=".pdf,.doc,.docx" class="form-control">
              <div class="form-text">PDF or DOC, max 5MB. <?= !$candidate['resume'] ? 'Required if no resume on profile.' : '' ?></div>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-5 py-2">
              <i class="bi bi-send me-1"></i> Submit Application
            </button>
            <button type="button" class="btn btn-outline-secondary save-job-btn <?= $isSaved ? 'text-primary' : '' ?>"
                    data-job-id="<?= $jobId ?>">
              <i class="bi bi-bookmark<?= $isSaved ? '-fill' : '' ?> me-1"></i>
              <?= $isSaved ? 'Saved' : 'Save Job' ?>
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">
    <!-- Company info -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="<?= logoUrl($job['logo'], $job['company_name']) ?>" alt=""
               style="width:52px;height:52px;border-radius:12px;object-fit:cover">
          <div>
            <div class="fw-700"><?= e($job['company_name']) ?></div>
            <?php if ($job['website']): ?>
              <a href="<?= e($job['website']) ?>" target="_blank" class="small text-primary"><?= e(str_replace(['https://','http://'],'',$job['website'])) ?></a>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($job['company_desc']): ?>
          <p class="text-muted small mb-3"><?= e(substr($job['company_desc'], 0, 200)) ?>…</p>
        <?php endif; ?>
        <div class="row g-2" style="font-size:12.5px">
          <?php if ($job['company_loc']): ?>
          <div class="col-12 text-muted"><i class="bi bi-geo-alt me-2 text-primary"></i><?= e($job['company_loc']) ?></div>
          <?php endif; ?>
          <?php if ($job['company_size']): ?>
          <div class="col-12 text-muted"><i class="bi bi-people me-2 text-primary"></i><?= e($job['company_size']) ?> employees</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Job Summary -->
    <div class="card mb-3">
      <div class="card-header"><h6 class="card-title mb-0">Job Overview</h6></div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush" style="font-size:13px">
          <li class="list-group-item d-flex justify-content-between px-3 py-2">
            <span class="text-muted"><i class="bi bi-briefcase me-2"></i>Type</span>
            <span class="fw-600 text-capitalize"><?= e($job['type']) ?></span>
          </li>
          <li class="list-group-item d-flex justify-content-between px-3 py-2">
            <span class="text-muted"><i class="bi bi-tag me-2"></i>Category</span>
            <span class="fw-600"><?= e($job['cat_name']) ?></span>
          </li>
          <?php if ($job['salary_min'] || $job['salary_max']): ?>
          <li class="list-group-item d-flex justify-content-between px-3 py-2">
            <span class="text-muted"><i class="bi bi-cash me-2"></i>Salary</span>
            <span class="fw-600"><?= formatSalary($job['salary_min'], $job['salary_max']) ?></span>
          </li>
          <?php endif; ?>
          <?php if ($job['deadline']): ?>
          <li class="list-group-item d-flex justify-content-between px-3 py-2">
            <span class="text-muted"><i class="bi bi-calendar me-2"></i>Deadline</span>
            <span class="fw-600"><?= date('M j, Y', strtotime($job['deadline'])) ?></span>
          </li>
          <?php endif; ?>
          <li class="list-group-item d-flex justify-content-between px-3 py-2">
            <span class="text-muted"><i class="bi bi-clock me-2"></i>Posted</span>
            <span class="fw-600"><?= timeAgo($job['created_at']) ?></span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Similar jobs -->
    <?php if (!empty($similarJobs)): ?>
    <div class="card">
      <div class="card-header"><h6 class="card-title mb-0">Similar Jobs</h6></div>
      <div class="list-group list-group-flush">
        <?php foreach ($similarJobs as $sj): ?>
        <a href="<?= BASE_URL ?>/candidate/job_detail.php?id=<?= $sj['id'] ?>"
           class="list-group-item list-group-item-action px-3 py-2 d-flex align-items-center gap-2 text-decoration-none">
          <img src="<?= logoUrl($sj['logo'], $sj['company_name']) ?>" alt=""
               style="width:32px;height:32px;border-radius:8px;object-fit:cover;flex-shrink:0">
          <div class="min-w-0">
            <div class="fw-600 text-truncate" style="font-size:13px"><?= e($sj['title']) ?></div>
            <div class="text-muted" style="font-size:12px"><?= e($sj['company_name']) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
