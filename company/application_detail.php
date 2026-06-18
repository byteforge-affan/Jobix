<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('company');

$user    = currentUser();
$company = Database::fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);
$appId   = (int)($_GET['id'] ?? 0);

$app = Database::fetch(
    "SELECT a.*, j.title AS job_title, j.type AS job_type, j.location AS job_loc, j.id AS job_id,
            u.name AS candidate_name, u.email AS candidate_email, u.avatar AS candidate_avatar,
            cd.id AS cand_id, cd.headline, cd.skills, cd.education, cd.experience,
            cd.location AS cand_location, cd.phone, cd.linkedin, cd.portfolio, cd.resume AS profile_resume
     FROM applications a
     JOIN jobs j ON a.job_id = j.id
     JOIN candidates cd ON a.candidate_id = cd.id
     JOIN users u ON cd.user_id = u.id
     WHERE a.id = ? AND j.company_id = ?",
    [$appId, $company['id']]
);

if (!$app) { setFlash('danger', 'Application not found.'); redirect('/company/applications.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $status = $_POST['status'] ?? '';
    $notes  = trim($_POST['notes'] ?? '');
    $allowed = ['pending','reviewed','shortlisted','rejected','hired'];

    if (in_array($status, $allowed)) {
        Database::query("UPDATE applications SET status=?, notes=? WHERE id=?", [$status, $notes, $appId]);

        // Notify candidate
        $msgMap = [
            'reviewed'    => 'Your application for ' . $app['job_title'] . ' has been reviewed.',
            'shortlisted' => 'Great news! You have been shortlisted for ' . $app['job_title'] . '.',
            'rejected'    => 'Your application for ' . $app['job_title'] . ' was not selected this time.',
            'hired'       => 'Congratulations! You have been selected for ' . $app['job_title'] . '!',
        ];
        if (isset($msgMap[$status])) {
            $cUser = Database::fetch("SELECT u.id FROM users u JOIN candidates c ON c.user_id=u.id WHERE c.id=?", [$app['cand_id']]);
            if ($cUser) createNotification($cUser['id'], 'application_status', ucfirst($status).': '.$app['job_title'], $msgMap[$status], '/candidate/applications.php');
        }
        setFlash('success', 'Application updated.');
        redirect('/company/application_detail.php?id=' . $appId);
    }
}

// Reload after POST
$app = Database::fetch(
    "SELECT a.*, j.title AS job_title, j.type AS job_type, j.location AS job_loc, j.id AS job_id,
            u.name AS candidate_name, u.email AS candidate_email, u.avatar AS candidate_avatar,
            cd.id AS cand_id, cd.headline, cd.skills, cd.education, cd.experience,
            cd.location AS cand_location, cd.phone, cd.linkedin, cd.portfolio, cd.resume AS profile_resume
     FROM applications a JOIN jobs j ON a.job_id=j.id JOIN candidates cd ON a.candidate_id=cd.id JOIN users u ON cd.user_id=u.id
     WHERE a.id=? AND j.company_id=?", [$appId, $company['id']]);

$pageTitle  = 'Application Detail';
$activePage = 'applications';
include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/company/applications.php?job_id=<?= $app['job_id'] ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Applications
  </a>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <!-- Candidate card -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex gap-4 align-items-start">
          <img src="<?= avatarUrl($app['candidate_avatar'], $app['candidate_name']) ?>"
               alt="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;flex-shrink:0">
          <div class="flex-1">
            <h4 class="fw-800 mb-1"><?= e($app['candidate_name']) ?></h4>
            <?php if ($app['headline']): ?>
              <p class="text-muted mb-2"><?= e($app['headline']) ?></p>
            <?php endif; ?>
            <div class="d-flex gap-3 flex-wrap" style="font-size:13.5px">
              <?php if ($app['cand_location']): ?>
                <span class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($app['cand_location']) ?></span>
              <?php endif; ?>
              <?php if ($app['phone']): ?>
                <span class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($app['phone']) ?></span>
              <?php endif; ?>
              <span class="text-muted"><i class="bi bi-envelope me-1"></i><?= e($app['candidate_email']) ?></span>
            </div>
            <div class="d-flex gap-2 mt-2">
              <?php if ($app['linkedin']): ?>
                <a href="<?= e($app['linkedin']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-linkedin me-1"></i> LinkedIn
                </a>
              <?php endif; ?>
              <?php if ($app['portfolio']): ?>
                <a href="<?= e($app['portfolio']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-globe me-1"></i> Portfolio
                </a>
              <?php endif; ?>
              <?php $resume = $app['resume'] ?? $app['profile_resume']; if ($resume): ?>
                <a href="<?= UPLOAD_URL . e($resume) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                  <i class="bi bi-download me-1"></i> Download Resume
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Skills -->
    <?php if ($app['skills']): ?>
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title">Skills</h5></div>
      <div class="card-body d-flex flex-wrap gap-2">
        <?php foreach (explode(',', $app['skills']) as $skill): ?>
          <span class="skill-tag"><?= e(trim($skill)) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Experience -->
    <?php if ($app['experience']): ?>
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title">Work Experience</h5></div>
      <div class="card-body" style="white-space:pre-line;line-height:1.8"><?= e($app['experience']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Education -->
    <?php if ($app['education']): ?>
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title">Education</h5></div>
      <div class="card-body" style="white-space:pre-line;line-height:1.8"><?= e($app['education']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Cover Letter -->
    <?php if ($app['cover_letter']): ?>
    <div class="card">
      <div class="card-header"><h5 class="card-title">Cover Letter</h5></div>
      <div class="card-body" style="line-height:1.8"><?= nl2br(e($app['cover_letter'])) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h5 class="card-title">Application for</h5></div>
      <div class="card-body">
        <div class="fw-700 mb-1"><?= e($app['job_title']) ?></div>
        <div class="d-flex gap-2 mb-3">
          <?= jobTypeBadge($app['job_type']) ?>
          <?php if ($app['job_loc']): ?>
            <span class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= e($app['job_loc']) ?></span>
          <?php endif; ?>
        </div>
        <div class="text-muted small mb-2">Applied <?= timeAgo($app['applied_at']) ?></div>
        <div>Current status: <?= badgeStatus($app['status']) ?></div>
      </div>
    </div>

    <!-- Update status form -->
    <div class="card">
      <div class="card-header"><h5 class="card-title">Update Status</h5></div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <?php foreach (['pending','reviewed','shortlisted','rejected','hired'] as $s): ?>
                <option value="<?= $s ?>" <?= $app['status']===$s ? 'selected' : '' ?> class="text-capitalize"><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Internal Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Notes visible only to your team…"><?= e($app['notes'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">Update Application</button>
        </form>
      </div>
    </div>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
