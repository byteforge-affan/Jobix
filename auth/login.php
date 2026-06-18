<?php
require_once __DIR__ . '/../core/functions.php';

if (isLoggedIn()) {
    redirect('/' . $_SESSION['user_role'] . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $user = Database::fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('/' . $user['role'] . '/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In – HireHub</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/app.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <a href="<?= BASE_URL ?>/" class="auth-logo">
      <div class="auth-logo-icon"><i class="bi bi-briefcase-fill"></i></div>
      <span class="auth-logo-text">Hire<span>Hub</span></span>
    </a>

    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
        <i class="bi bi-exclamation-circle"></i> <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <div class="position-relative">
          <i class="bi bi-envelope position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
          <input type="email" name="email" class="form-control ps-5"
                 placeholder="you@example.com"
                 value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label d-flex justify-content-between">
          Password
          <a href="#" class="text-primary small fw-600">Forgot password?</a>
        </label>
        <div class="position-relative">
          <i class="bi bi-lock position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
          <input type="password" name="password" id="passwordInput" class="form-control ps-5"
                 placeholder="••••••••" required autocomplete="current-password">
          <button type="button" onclick="togglePass()" class="btn border-0 position-absolute top-50 translate-middle-y end-0 me-1 p-1 text-muted">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
      </button>
    </form>

    <hr class="my-3">
    <p class="text-center text-muted small mb-0">
      Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php" class="fw-600">Create one</a>
    </p>

    <!-- Demo credentials -->
    <div class="mt-4 p-3 rounded-3" style="background:var(--gray-50);border:1px solid var(--gray-200)">
      <p class="text-muted small fw-600 mb-2"><i class="bi bi-info-circle me-1"></i> Demo Credentials</p>
      <div class="row g-1" style="font-size:12px">
        <div class="col-12 col-sm-4">
          <div class="p-2 bg-white rounded border" onclick="fillLogin('admin@hirehub.com','password')" style="cursor:pointer">
            <div class="fw-700">Admin</div>
            <div class="text-muted">admin@hirehub.com</div>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="p-2 bg-white rounded border" onclick="fillLogin('hr@technova.com','password')" style="cursor:pointer">
            <div class="fw-700">Company</div>
            <div class="text-muted">hr@technova.com</div>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="p-2 bg-white rounded border" onclick="fillLogin('ahmed@email.com','password')" style="cursor:pointer">
            <div class="fw-700">Candidate</div>
            <div class="text-muted">ahmed@email.com</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
  const inp = document.getElementById('passwordInput');
  const icon = document.getElementById('eyeIcon');
  if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
function fillLogin(email, pass) {
  document.querySelector('[name=email]').value = email;
  document.querySelector('[name=password]').value = pass;
}
</script>
</body>
</html>
