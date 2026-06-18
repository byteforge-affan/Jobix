<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('company');

$user = currentUser();
$company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $errors = [];

    $title      = trim($_POST['title'] ?? '');
    $cat_id     = (int)($_POST['category_id'] ?? 0);
    $type       = $_POST['type'] ?? 'full-time';
    $salary_min = (float)($_POST['salary_min'] ?? 0);
    $salary_max = (float)($_POST['salary_max'] ?? 0);
    $location   = trim($_POST['location'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $req        = trim($_POST['requirements'] ?? '');
    $benefits   = trim($_POST['benefits'] ?? '');
    $deadline   = $_POST['deadline'] ?? null;

    if (!$title)  $errors[] = 'Job title is required.';
    if (!$cat_id) $errors[] = 'Category is required.';
    if (!$desc)   $errors[] = 'Job description is required.';

    if (empty($errors)) {
        $jobId = Database::insert(
            "INSERT INTO jobs (company_id, category_id, title, type, salary_min, salary_max, location, description, requirements, benefits, deadline)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$company['id'], $cat_id, $title, $type, $salary_min ?: null, $salary_max ?: null, $location, $desc, $req, $benefits, $deadline ?: null]
        );

        // Notify candidates? (could add here)
        setFlash('success', 'Job posted successfully!');
        redirect('/company/jobs.php');
    }
}

$categories = Database::fetchAll("SELECT * FROM job_categories ORDER BY name");
$pageTitle  = 'Post a Job';
$activePage = 'post_job';

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
        <h5 class="card-title">Create Job Listing</h5>
        <span class="text-muted small">Fill in the details to attract the right candidates</span>
      </div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>

          <div class="row g-3 mb-4">
            <div class="col-md-8">
              <label class="form-label">Job Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Senior PHP Developer"
                     value="<?= e($_POST['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Job Type <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required>
                <?php foreach (['full-time'=>'Full Time','part-time'=>'Part Time','remote'=>'Remote','internship'=>'Internship','contract'=>'Contract'] as $v => $l): ?>
                  <option value="<?= $v ?>" <?= (($_POST['type'] ?? 'full-time') === $v) ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <option value="">Select a category…</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= (int)($_POST['category_id'] ?? 0) === $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Karachi, Pakistan"
                     value="<?= e($_POST['location'] ?? '') ?>">
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12">
              <label class="form-label">Salary Range (PKR)</label>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-normal text-muted small">Minimum</label>
              <input type="number" name="salary_min" id="salary_min" class="form-control" placeholder="80000"
                     value="<?= e($_POST['salary_min'] ?? '') ?>" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-normal text-muted small">Maximum</label>
              <input type="number" name="salary_max" id="salary_max" class="form-control" placeholder="150000"
                     value="<?= e($_POST['salary_max'] ?? '') ?>" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-normal text-muted small">Application Deadline</label>
              <input type="date" name="deadline" class="form-control"
                     value="<?= e($_POST['deadline'] ?? '') ?>"
                     min="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Job Description <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="7"
                      placeholder="Describe the role, responsibilities, and what the candidate will be doing…"
                      required><?= e($_POST['description'] ?? '') ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Requirements</label>
            <textarea name="requirements" class="form-control" rows="5"
                      placeholder="• 3+ years experience&#10;• PHP / Laravel proficiency&#10;• Strong communication skills"><?= e($_POST['requirements'] ?? '') ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Benefits & Perks</label>
            <textarea name="benefits" class="form-control" rows="4"
                      placeholder="• Health insurance&#10;• Remote work options&#10;• Annual bonus"><?= e($_POST['benefits'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-send me-1"></i> Publish Job
            </button>
            <a href="<?= BASE_URL ?>/company/jobs.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
