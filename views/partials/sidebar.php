<?php
// views/partials/sidebar.php
// Expects: $user (array), $activePage (string)

$role = $user['role'] ?? 'candidate';
$notifications = isLoggedIn() ? getUnreadNotifications((int)$_SESSION['user_id']) : [];
$notifCount = count($notifications);

// Build nav items by role
$navItems = [];

if ($role === 'admin') {
    $navItems = [
        ['label' => 'Main', 'section' => true],
        ['href' => '/admin/dashboard.php',    'icon' => 'grid-1x2',      'label' => 'Dashboard',     'key' => 'dashboard'],
        ['href' => '/admin/users.php',        'icon' => 'people',        'label' => 'Users',         'key' => 'users'],
        ['href' => '/admin/companies.php',    'icon' => 'building',      'label' => 'Companies',     'key' => 'companies'],
        ['href' => '/admin/jobs.php',         'icon' => 'briefcase',     'label' => 'Jobs',          'key' => 'jobs'],
        ['href' => '/admin/applications.php', 'icon' => 'file-text',     'label' => 'Applications',  'key' => 'applications'],
        ['href' => '/admin/categories.php',   'icon' => 'tag',           'label' => 'Categories',    'key' => 'categories'],
        ['label' => 'Settings', 'section' => true],
        ['href' => '/admin/profile.php',      'icon' => 'person-circle', 'label' => 'Profile',       'key' => 'profile'],
    ];
} elseif ($role === 'company') {
    $navItems = [
        ['label' => 'Main', 'section' => true],
        ['href' => '/company/dashboard.php',   'icon' => 'grid-1x2',      'label' => 'Dashboard',    'key' => 'dashboard'],
        ['href' => '/company/jobs.php',        'icon' => 'briefcase',     'label' => 'My Jobs',      'key' => 'jobs'],
        ['href' => '/company/post_job.php',    'icon' => 'plus-circle',   'label' => 'Post a Job',   'key' => 'post_job'],
        ['href' => '/company/applications.php','icon' => 'file-text',     'label' => 'Applications', 'key' => 'applications'],
        ['label' => 'Account', 'section' => true],
        ['href' => '/company/profile.php',     'icon' => 'building',      'label' => 'Company Profile', 'key' => 'profile'],
    ];
} else {
    $navItems = [
        ['label' => 'Main', 'section' => true],
        ['href' => '/candidate/dashboard.php',  'icon' => 'grid-1x2',    'label' => 'Dashboard',       'key' => 'dashboard'],
        ['href' => '/candidate/jobs.php',        'icon' => 'search',      'label' => 'Find Jobs',       'key' => 'jobs'],
        ['href' => '/candidate/applications.php','icon' => 'file-text',   'label' => 'My Applications', 'key' => 'applications', 'badge' => $notifCount > 0 ? $notifCount : null],
        ['href' => '/candidate/saved.php',       'icon' => 'bookmark',    'label' => 'Saved Jobs',      'key' => 'saved'],
        ['label' => 'Account', 'section' => true],
        ['href' => '/candidate/profile.php',     'icon' => 'person-circle','label' => 'My Profile',     'key' => 'profile'],
    ];
}
?>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebarOverlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none d-lg-none" style="z-index:999;"></div>

<aside class="sidebar" id="sidebar">
  <!-- Logo -->
  <a href="<?= BASE_URL ?>/<?= $role ?>/dashboard.php" class="sidebar-logo">
    <div class="sidebar-logo-icon">
      <i class="bi bi-briefcase-fill"></i>
    </div>
    <span class="sidebar-logo-text">Hire<span>Hub</span></span>
  </a>

  <!-- Nav -->
  <ul class="sidebar-nav">
    <?php foreach ($navItems as $item): ?>
      <?php if (!empty($item['section'])): ?>
        <li class="nav-section-title"><?= e($item['label']) ?></li>
      <?php else: ?>
        <li>
          <a href="<?= BASE_URL . $item['href'] ?>"
             class="<?= ($activePage ?? '') === $item['key'] ? 'active' : '' ?>">
            <span class="nav-icon"><i class="bi bi-<?= e($item['icon']) ?>"></i></span>
            <?= e($item['label']) ?>
            <?php if (!empty($item['badge'])): ?>
              <span class="nav-badge"><?= (int)$item['badge'] ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>
    <li>
      <a href="<?= BASE_URL ?>/auth/logout.php" style="color:rgba(255,255,255,.45)" class="mt-2">
        <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span>
        Sign Out
      </a>
    </li>
  </ul>

  <!-- User info at bottom -->
  <div class="sidebar-user">
    <img src="<?= avatarUrl($user['avatar'] ?? null, $user['name']) ?>" alt="<?= e($user['name']) ?>">
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= e($user['name']) ?></div>
      <div class="sidebar-user-role"><?= e($user['role']) ?></div>
    </div>
  </div>
</aside>
