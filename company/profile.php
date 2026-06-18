<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('company');

$user    = currentUser();
$company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name        = trim($_POST['company_name'] ?? '');
    $industry    = trim($_POST['industry'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $founded     = (int)($_POST['founded_year'] ?? 0) ?: null;
    $size        = $_POST['company_size'] ?? '1-10';

    // Logo upload
    $logo = $company['logo'];
    if (!empty($_FILES['logo']['name'])) {
        $uploaded = uploadFile($_FILES['logo'], 'logos', ['jpg','jpeg','png','gif','webp'], 2097152);
        if ($uploaded) $logo = $uploaded;
        else setFlash('danger', 'Logo upload failed. Use JPG/PNG under 2MB.');
    }

    Database::query(
        "UPDATE companies SET company_name=?, industry=?, website=?, description=?, location=?,
         phone=?, founded_year=?, company_size=?, logo=? WHERE user_id=?",
        [$name, $industry, $website, $description, $location, $phone, $founded, $size, $logo, $user['id']]
    );
    // Update display name
    Database::query("UPDATE users SET name=? WHERE id=?", [$name, $user['id']]);
    $_SESSION['user_name'] = $name;

    setFlash('success', 'Company profile updated!');
    redirect('/company/profile.php');
}

$pageTitle  = 'Company Profile';
$activePage = 'profile';
$sizes      = ['1-10','11-50','51-200','201-500','501-1000','1000+'];

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<div class="row g-4">
  <!-- Preview card -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center py-4">
        <div class="position-relative d-inline-block mb-3">
          <img src="<?= logoUrl($company['logo'], $company['company_name']) ?>" alt="" id="logoPreview"
               style="width:80px;height:80px;border-radius:16px;object-fit:cover;border:2px solid var(--gray-200)">
          <label for="logoInput" class="position-absolute bottom-0 end-0 btn btn-sm btn-primary btn-icon"
                 style="width:28px;height:28px;border-radius:50%;padding:0;cursor:pointer">
            <i class="bi bi-camera" style="font-size:12px"></i>
          </label>
        </div>
        <h5 class="fw-700 mb-1"><?= e($company['company_name']) ?></h5>
        <p class="text-muted small mb-3"><?= e($company['industry'] ?? 'No industry set') ?></p>
        <?php if ($company['is_verified']): ?>
          <span class="badge bg-success-subtle text-success"><i class="bi bi-patch-check me-1"></i>Verified Company</span>
        <?php else: ?>
          <span class="badge bg-warning-subtle text-warning"><i class="bi bi-clock me-1"></i>Pending Verification</span>
        <?php endif; ?>
      </div>
      <div class="card-body border-top pt-3" style="font-size:13.5px">
        <?php if ($company['location']): ?>
        <div class="d-flex align-items-center gap-2 mb-2 text-muted">
          <i class="bi bi-geo-alt text-primary"></i> <?= e($company['location']) ?>
        </div>
        <?php endif; ?>
        <?php if ($company['website']): ?>
        <div class="d-flex align-items-center gap-2 mb-2 text-muted">
          <i class="bi bi-globe text-primary"></i>
          <a href="<?= e($company['website']) ?>" target="_blank" class="text-truncate"><?= e(str_replace('https://','',$company['website'])) ?></a>
        </div>
        <?php endif; ?>
        <?php if ($company['company_size']): ?>
        <div class="d-flex align-items-center gap-2 mb-2 text-muted">
          <i class="bi bi-people text-primary"></i> <?= e($company['company_size']) ?> employees
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body" style="font-size:13.5px">
        <div class="fw-700 mb-2">Account Info</div>
        <div class="text-muted mb-1"><i class="bi bi-envelope me-2"></i><?= e($user['email']) ?></div>
        <div class="text-muted"><i class="bi bi-calendar me-2"></i>Joined <?= date('M Y', strtotime($user['created_at'])) ?></div>
      </div>
    </div>
  </div>

  <!-- Edit form -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h5 class="card-title">Edit Company Profile</h5></div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <!-- Hidden file input triggered by camera button -->
          <input type="file" name="logo" id="logoInput" accept="image/*" class="d-none"
                 onchange="previewLogo(this)">

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Company Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" class="form-control" value="<?= e($company['company_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Industry</label>
              <input type="text" name="industry" class="form-control" placeholder="e.g. Information Technology"
                     value="<?= e($company['industry'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Website</label>
              <input type="url" name="website" class="form-control" placeholder="https://yourcompany.com"
                     value="<?= e($company['website'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="+92-300-1234567"
                     value="<?= e($company['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" placeholder="City, Country"
                     value="<?= e($company['location'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Founded Year</label>
              <input type="number" name="founded_year" class="form-control" placeholder="2015"
                     value="<?= e($company['founded_year'] ?? '') ?>" min="1900" max="<?= date('Y') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Company Size</label>
              <select name="company_size" class="form-select">
                <?php foreach ($sizes as $s): ?>
                  <option value="<?= $s ?>" <?= ($company['company_size'] ?? '1-10') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">About the Company</label>
            <textarea name="description" class="form-control" rows="6"
                      placeholder="Describe what your company does, its culture, mission and values…"><?= e($company['description'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i> Save Profile
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

</div></div></div>

<script>
function previewLogo(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => document.getElementById('logoPreview').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
