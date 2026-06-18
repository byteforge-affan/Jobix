<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('admin');

$user = currentUser();
$pageTitle  = 'All Applications';
$activePage = 'applications';

$status = $_GET['status'] ?? '';
$params = [];
$where  = ['1=1'];
if ($status) { $where[] = "a.status = ?"; $params[] = $status; }

$apps = Database::fetchAll("
  SELECT a.*, j.title AS job_title, c.company_name, u.name AS candidate_name
  FROM applications a
  JOIN jobs j ON a.job_id = j.id
  JOIN companies c ON j.company_id = c.id
  JOIN candidates cd ON a.candidate_id = cd.id
  JOIN users u ON cd.user_id = u.id
  WHERE " . implode(' AND ', $where) . "
  ORDER BY a.applied_at DESC", $params);

$statuses = ['pending','reviewed','shortlisted','rejected','hired'];

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<div class="page-header">
  <div>
    <h2>Applications</h2>
    <p class="text-muted mb-0" style="font-size:13.5px"><?= count($apps) ?> applications</p>
  </div>
</div>

<!-- Status filter tabs -->
<div class="d-flex gap-2 mb-4 flex-wrap">
  <a href="<?= BASE_URL ?>/admin/applications.php" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline-secondary' ?>">
    All
  </a>
  <?php foreach ($statuses as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status === $s ? 'btn-primary' : 'btn-outline-secondary' ?> text-capitalize">
      <?= $s ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="table-wrapper">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Candidate</th>
          <th>Job</th>
          <th>Company</th>
          <th>Status</th>
          <th>Applied</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($apps)): ?>
          <tr><td colspan="6">
            <div class="empty-state py-5">
              <div class="empty-state-icon"><i class="bi bi-file-text"></i></div>
              <h5>No applications</h5>
            </div>
          </td></tr>
        <?php else: ?>
          <?php foreach ($apps as $i => $a): ?>
          <tr>
            <td class="text-muted"><?= $i + 1 ?></td>
            <td><div class="fw-600"><?= e($a['candidate_name']) ?></div></td>
            <td><?= e($a['job_title']) ?></td>
            <td><?= e($a['company_name']) ?></td>
            <td><?= badgeStatus($a['status']) ?></td>
            <td class="text-muted"><?= timeAgo($a['applied_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
