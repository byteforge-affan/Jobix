<?php
// views/partials/navbar.php
// Expects: $pageTitle (string), $user (array)
$notifications = isLoggedIn() ? getUnreadNotifications((int)$_SESSION['user_id']) : [];
$notifCount = count($notifications);
?>

<nav class="top-navbar">
  <!-- Mobile toggle -->
  <button id="sidebarToggle" class="btn-icon btn btn-outline-secondary border-0 d-lg-none me-1">
    <i class="bi bi-list fs-5"></i>
  </button>

  <h1 class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>

  <!-- Navbar actions -->
  <div class="navbar-actions">
    <!-- Notifications -->
    <div class="dropdown">
      <button class="navbar-btn" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell"></i>
        <?php if ($notifCount > 0): ?>
          <span class="notif-dot"></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end notif-dropdown shadow-lg border-0 p-0" style="border-radius:12px;overflow:hidden;">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
          <span class="fw-700 fs-6">Notifications</span>
          <?php if ($notifCount > 0): ?>
            <a href="<?= BASE_URL ?>/api/mark_all_notifications.php" class="small text-primary fw-600">Mark all read</a>
          <?php endif; ?>
        </div>
        <?php if (empty($notifications)): ?>
          <div class="text-center py-4 text-muted small">
            <i class="bi bi-bell-slash d-block fs-3 mb-2"></i>
            No new notifications
          </div>
        <?php else: ?>
          <?php foreach ($notifications as $n): ?>
            <a href="<?= BASE_URL . e($n['link'] ?? '#') ?>" class="notif-item unread text-decoration-none" data-id="<?= (int)$n['id'] ?>">
              <div class="notif-dot"></div>
              <div>
                <div class="fw-600 text-dark" style="font-size:13px"><?= e($n['title']) ?></div>
                <div class="text-muted" style="font-size:12px"><?= e($n['message']) ?></div>
                <div class="text-muted mt-1" style="font-size:11px"><?= timeAgo($n['created_at']) ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Avatar dropdown -->
    <div class="dropdown">
      <button class="d-flex align-items-center gap-2 btn btn-sm border-0 bg-transparent p-1"
              data-bs-toggle="dropdown">
        <img src="<?= avatarUrl($user['avatar'] ?? null, $user['name']) ?>"
             alt="" class="avatar-sm" style="border:2px solid var(--gray-200);">
        <span class="fw-600 text-dark d-none d-md-inline" style="font-size:13px"><?= e($user['name']) ?></span>
        <i class="bi bi-chevron-down text-muted" style="font-size:10px"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:10px;min-width:180px">
        <li class="px-3 py-2 border-bottom">
          <div class="fw-700" style="font-size:13px"><?= e($user['name']) ?></div>
          <div class="text-muted text-capitalize" style="font-size:12px"><?= e($user['role']) ?></div>
        </li>
        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/<?= $user['role'] ?>/profile.php">
          <i class="bi bi-person me-2 text-muted"></i> My Profile
        </a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/auth/logout.php">
          <i class="bi bi-box-arrow-left me-2"></i> Sign Out
        </a></li>
      </ul>
    </div>
  </div>
</nav>
