<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $updates = ['name = ?', 'email = ?'];
    $params  = [$name, $email];
    if ($password) { $updates[] = 'password = ?'; $params[] = password_hash($password, PASSWORD_DEFAULT); }
    $params[] = $_SESSION['user_id'];
    Database::query("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?", $params);
    $_SESSION['user_name'] = $name;
    setFlash('success', 'Profile updated successfully.');
    redirect('/admin/profile.php');
}

$user = currentUser();
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
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><h5 class="card-title">Admin Profile</h5></div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
          </div>
          <div class="mb-4">
            <label class="form-label">New Password <small class="text-muted fw-normal">(leave blank to keep current)</small></label>
            <input type="password" name="password" class="form-control" placeholder="••••••••">
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>
</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
