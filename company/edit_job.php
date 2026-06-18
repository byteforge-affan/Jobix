<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('company');

$user    = currentUser();
$company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);
$jobId   = (int)($_GET['id'] ?? 0);

$job = Database::fetch("SELECT * FROM jobs WHERE id = ? AND company_id = ?", [$jobId, $company['id']]);
if (!$job) { setFlash('danger', 'Job not found.'); redirect('/company/jobs.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $errors = [];

    $title      = trim($_POST['title'] ?? '');
    $cat_id     = (int)($_POST['category_id'] ?? 0);
    $type       = $_POST['type'] ?? 'full-time';
    $salary_min = strlen(trim($_POST['salary_min'] ?? '')) ? (float)$_POST['salary_min'] : null;
    $salary_max = strlen(trim($_POST['salary_max'] ?? '')) ? (float)$_POST['salary_max'] : null;
    $location   = trim($_POST['location'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $req        = trim($_POST['requirements'] ?? '');
    $benefits   = trim($_POST['benefits'] ?? '');
    $deadline   = $_POST['deadline'] ?: null;
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (!$title) $errors[] = 'Job title is required.';
    if (!$desc)  $errors[] = 'Job description is required.';

    if (empty($errors)) {
        Database::query(
            "UPDATE jobs SET category_id=?, title=?, type=?, salary_min=?, salary_max=?,
             location=?, description=?, requirements=?, benefits=?, deadline=?, is_active=?
             WHERE id = ? AND company_id = ?",
            [$cat_id, $title, $type, $salary_min, $salary_max, $location, $desc, $req, $benefits, $deadline, $is_active, $jobId, $company['id']]
        );
        setFlash('success', 'Job updated successfully.');
        redirect('/company/jobs.php');
    }
    // Re-populate with POST data if errors
    $job = array_merge($job, $_POST);
}

$categories = Database::fetchAll("SELECT * FROM job_categories ORDER BY name");
$pageTitle  = 'Edit Job';
$activePage = 'jobs';

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
  <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-xl-9">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Edit Job Listing</h5>
        <div class="form-check form-switch mb-0">
          <label class="form-check-label fw-600 text-success" for="is_active">Job is Active</label>
        </div>
      </div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>

          <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-4" style="background:var(--gray-50);border:1px solid var(--gray-200)">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                     <?= $job['is_active'] ? 'checked' : '' ?> role="switch">
              <label class="form-check-label fw-600" for="is_active">
                <?= $job['is_active'] ? '<span class="text-success">Job is Published</span>' : '<span class="text-muted">Job is Inactive</span>' ?>
              </label>
            </div>
            <span class="text-muted small">Toggle to publish or unpublish this listing.</span>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-8">
              <label class="form-label">Job Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" value="<?= e($job['title']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Job Type</label>
              <select name="type" class="form-select">
                <?php foreach (['full-time'=>'Full Time','part-time'=>'Part Time','remote'=>'Remote','internship'=>'Internship','contract'=>'Contract'] as $v => $l): ?>
                  <option value="<?= $v ?>" <?= $job['type'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= (int)$job['category_id'] === $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" value="<?= e($job['location'] ?? '') ?>">
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">Min Salary (PKR)</label>
              <input type="number" name="salary_min" class="form-control" value="<?= e($job['salary_min'] ?? '') ?>" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Max Salary (PKR)</label>
              <input type="number" name="salary_max" class="form-control" value="<?= e($job['salary_max'] ?? '') ?>" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Application Deadline</label>
              <input type="date" name="deadline" class="form-control" value="<?= e($job['deadline'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Job Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="8" required><?= e($job['description']) ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Requirements</label>
            <textarea name="requirements" class="form-control" rows="5"><?= e($job['requirements'] ?? '') ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Benefits &amp; Perks</label>
            <textarea name="benefits" class="form-control" rows="4"><?= e($job['benefits'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Save Changes</button>
            <a href="<?= BASE_URL ?>/company/jobs.php" class="btn btn-outline-secondary">Cancel</a>
            <a href="<?= BASE_URL ?>/company/applications.php?job_id=<?= $jobId ?>"
               class="btn btn-outline-info ms-auto">
              <i class="bi bi-people me-1"></i> View Applications
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
