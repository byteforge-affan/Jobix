<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('candidate');

$user      = currentUser();
$candidate = Database::fetch("SELECT * FROM candidates WHERE user_id = ?", [$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $headline   = trim($_POST['headline'] ?? '');
    $skills     = trim($_POST['skills'] ?? '');
    $education  = trim($_POST['education'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $location   = trim($_POST['location'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $linkedin   = trim($_POST['linkedin'] ?? '');
    $portfolio  = trim($_POST['portfolio'] ?? '');

    // Avatar upload
    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $uploaded = uploadFile($_FILES['avatar'], 'avatars', ['jpg','jpeg','png','gif','webp'], 2097152);
        if ($uploaded) $avatar = $uploaded;
        else setFlash('danger', 'Avatar upload failed.');
    }

    // Resume upload
    $resume = $candidate['resume'];
    if (!empty($_FILES['resume']['name'])) {
        $uploaded = uploadFile($_FILES['resume'], 'resumes', ['pdf','doc','docx'], 5242880);
        if ($uploaded) $resume = $uploaded;
        else setFlash('danger', 'Resume upload failed. Use PDF/DOC under 5MB.');
    }

    Database::query(
        "UPDATE candidates SET headline=?, skills=?, education=?, experience=?, location=?, phone=?, linkedin=?, portfolio=?, resume=?
         WHERE user_id=?",
        [$headline, $skills, $education, $experience, $location, $phone, $linkedin, $portfolio, $resume, $user['id']]
    );
    Database::query("UPDATE users SET name=?, email=?, avatar=? WHERE id=?", [$name, $email, $avatar, $user['id']]);
    $_SESSION['user_name'] = $name;

    setFlash('success', 'Profile updated successfully!');
    redirect('/candidate/profile.php');
}

// Profile completeness
$fields = ['headline', 'skills', 'education', 'experience', 'location', 'phone', 'resume'];
$filled = count(array_filter($fields, fn($f) => !empty($candidate[$f])));
$completeness = (int)(($filled / count($fields)) * 100);

$pageTitle  = 'My Profile';
$activePage = 'profile';

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<div class="row g-4">
  <!-- Left: Preview card -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center py-4">
        <div class="position-relative d-inline-block mb-3">
          <img src="<?= avatarUrl($user['avatar'], $user['name']) ?>" id="avatarPreview"
               alt="" style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--gray-200)">
          <label for="avatarInput" class="position-absolute bottom-0 end-0 btn btn-sm btn-primary btn-icon"
                 style="width:28px;height:28px;border-radius:50%;padding:0;cursor:pointer">
            <i class="bi bi-camera" style="font-size:12px"></i>
          </label>
        </div>
        <h5 class="fw-800 mb-1"><?= e($user['name']) ?></h5>
        <?php if ($candidate['headline']): ?>
          <p class="text-muted small mb-2"><?= e($candidate['headline']) ?></p>
        <?php endif; ?>
        <?php if ($candidate['location']): ?>
          <div class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i><?= e($candidate['location']) ?></div>
        <?php endif; ?>

        <!-- Skills -->
        <?php if ($candidate['skills']): ?>
        <div class="d-flex flex-wrap gap-1 justify-content-center mt-2">
          <?php foreach (array_slice(explode(',', $candidate['skills']), 0, 6) as $skill): ?>
            <span class="skill-tag"><?= e(trim($skill)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Profile completeness -->
      <div class="card-body border-top pt-3">
        <div class="d-flex justify-content-between mb-1">
          <span class="small fw-600">Profile Strength</span>
          <span class="small fw-700 text-primary"><?= $completeness ?>%</span>
        </div>
        <div class="progress" style="height:6px;border-radius:3px">
          <div class="progress-bar bg-primary" style="width:<?= $completeness ?>%"></div>
        </div>
        <?php if ($completeness < 100): ?>
          <p class="text-muted mt-2 mb-0" style="font-size:12px">
            <?= $completeness < 50 ? 'Add more details to get noticed by recruiters.' : 'Almost there — fill in the remaining fields.' ?>
          </p>
        <?php else: ?>
          <p class="text-success mt-2 mb-0 small fw-600"><i class="bi bi-check-circle me-1"></i> Profile is complete!</p>
        <?php endif; ?>
      </div>

      <?php if ($candidate['resume']): ?>
      <div class="card-body border-top pt-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
          <div class="flex-1">
            <div class="small fw-600">Resume on file</div>
            <div class="text-muted" style="font-size:11.5px">Shared with job applications</div>
          </div>
          <a href="<?= UPLOAD_URL . e($candidate['resume']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download"></i>
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Links -->
    <div class="card">
      <div class="card-body" style="font-size:13.5px">
        <div class="fw-700 mb-2">Links</div>
        <?php if ($candidate['linkedin']): ?>
          <a href="<?= e($candidate['linkedin']) ?>" target="_blank" class="d-flex align-items-center gap-2 mb-2 text-muted">
            <i class="bi bi-linkedin text-primary"></i> LinkedIn Profile
          </a>
        <?php endif; ?>
        <?php if ($candidate['portfolio']): ?>
          <a href="<?= e($candidate['portfolio']) ?>" target="_blank" class="d-flex align-items-center gap-2 text-muted">
            <i class="bi bi-globe text-success"></i> Portfolio Website
          </a>
        <?php endif; ?>
        <?php if (!$candidate['linkedin'] && !$candidate['portfolio']): ?>
          <p class="text-muted small mb-0">Add your LinkedIn and portfolio in the form.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: Edit form -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h5 class="card-title">Edit Profile</h5></div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none"
                 onchange="previewAvatar(this)">

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Professional Headline</label>
              <input type="text" name="headline" class="form-control"
                     placeholder="e.g. Senior PHP Developer | 5+ Years Experience"
                     value="<?= e($candidate['headline'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" placeholder="City, Country"
                     value="<?= e($candidate['location'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="+92-300-1234567"
                     value="<?= e($candidate['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">LinkedIn URL</label>
              <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/you"
                     value="<?= e($candidate['linkedin'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Portfolio / Website</label>
              <input type="url" name="portfolio" class="form-control" placeholder="https://yoursite.com"
                     value="<?= e($candidate['portfolio'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Skills <span class="text-muted fw-normal">(comma-separated)</span></label>
            <input type="text" name="skills" class="form-control"
                   placeholder="PHP, Laravel, MySQL, JavaScript, Vue.js…"
                   value="<?= e($candidate['skills'] ?? '') ?>">
          </div>

          <div class="mb-4">
            <label class="form-label">Education</label>
            <textarea name="education" class="form-control" rows="4"
                      placeholder="BS Computer Science – FAST NUCES (2015–2019)"><?= e($candidate['education'] ?? '') ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Work Experience</label>
            <textarea name="experience" class="form-control" rows="5"
                      placeholder="5 years at Company A as PHP Developer&#10;2 years freelance on Upwork"><?= e($candidate['experience'] ?? '') ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Upload Resume <span class="text-muted fw-normal">(PDF/DOC, max 5MB)</span></label>
            <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
            <?php if ($candidate['resume']): ?>
              <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Current resume on file — upload to replace it.</div>
            <?php endif; ?>
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
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
