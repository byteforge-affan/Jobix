<?php
require_once __DIR__ . '/../core/functions.php';

if (isLoggedIn()) redirect('/' . $_SESSION['user_role'] . '/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');
    $role     = $_POST['role'] ?? 'candidate';

    if (!$name)   $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['company','candidate'])) $role = 'candidate';

    if (Database::count("SELECT COUNT(*) FROM users WHERE email = ?", [$email])) {
        $errors[] = 'This email is already registered.';
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $userId = Database::insert(
            "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)",
            [$name, $email, $hashed, $role]
        );
        // Create profile row
        if ($role === 'company') {
            Database::query("INSERT INTO companies (user_id, company_name) VALUES (?,?)", [$userId, $name]);
        } else {
            Database::query("INSERT INTO candidates (user_id) VALUES (?)", [$userId]);
        }

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_name'] = $name;
        setFlash('success', 'Account created! Complete your profile to get started.');
        redirect('/' . $role . '/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account – HireHub</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/app.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:480px">
    <a href="<?= BASE_URL ?>/" class="auth-logo">
      <div class="auth-logo-icon"><i class="bi bi-briefcase-fill"></i></div>
      <span class="auth-logo-text">Hire<span>Hub</span></span>
    </a>

    <h2 class="auth-title">Create your account</h2>
    <p class="auth-subtitle">Join thousands of companies and candidates on HireHub</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST">
      <?= csrf_field() ?>

      <!-- Role selection -->
      <div class="mb-4">
        <label class="form-label">I am a…</label>
        <div class="row g-2">
          <div class="col-6">
            <input type="radio" class="btn-check" name="role" id="roleCandidate" value="candidate"
              <?= (($_POST['role'] ?? 'candidate') === 'candidate') ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1" for="roleCandidate">
              <i class="bi bi-person-circle fs-4"></i>
              <span class="fw-600">Job Seeker</span>
              <small class="text-muted fw-normal">Find & apply for jobs</small>
            </label>
          </div>
          <div class="col-6">
            <input type="radio" class="btn-check" name="role" id="roleCompany" value="company"
              <?= (($_POST['role'] ?? '') === 'company') ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1" for="roleCompany">
              <i class="bi bi-building fs-4"></i>
              <span class="fw-600">Employer</span>
              <small class="text-muted fw-normal">Post jobs & hire talent</small>
            </label>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="Ahmed Khan"
               value="<?= e($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com"
               value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="row g-3 mb-4">
        <div class="col">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
        </div>
        <div class="col">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
        <i class="bi bi-person-plus me-1"></i> Create Account
      </button>
    </form>

    <p class="text-center text-muted small mt-3 mb-0">
      Already have an account? <a href="<?= BASE_URL ?>/auth/login.php" class="fw-600">Sign in</a>
    </p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
