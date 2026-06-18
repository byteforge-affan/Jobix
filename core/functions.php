<?php
// ─── Bootstrap ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

session_start();

// ─── CSRF ─────────────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token mismatch. Please go back and try again.');
        }
    }
}

// ─── Auth helpers ─────────────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return Database::fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function requireAuth(string $role = ''): void {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
    if ($role && $_SESSION['user_role'] !== $role) {
        redirect('/auth/login.php');
    }
}

function requireAnyAuth(): void {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

// ─── Redirect ─────────────────────────────────────────────────────────────────
function redirect(string $path): never {
    header('Location: ' . BASE_URL . $path);
    exit;
}

// ─── Flash messages ───────────────────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $icons = ['success' => 'check-circle', 'danger' => 'x-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
    $icon = $icons[$flash['type']] ?? 'info-circle';
    return '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-' . $icon . '"></i>
        <span>' . e($flash['message']) . '</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// ─── XSS ──────────────────────────────────────────────────────────────────────
function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// ─── File upload ──────────────────────────────────────────────────────────────
function uploadFile(array $file, string $subdir, array $allowedTypes, int $maxSize = 5242880): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxSize) return false;
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) return false;
    
    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = UPLOAD_PATH . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return $subdir . '/' . $filename;
    }
    return false;
}

// ─── Notifications ────────────────────────────────────────────────────────────
function createNotification(int $userId, string $type, string $title, string $message, string $link = ''): void {
    Database::query(
        "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)",
        [$userId, $type, $title, $message, $link]
    );
}

function getUnreadNotifications(int $userId): array {
    return Database::fetchAll(
        "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10",
        [$userId]
    );
}

function markNotificationsRead(int $userId): void {
    Database::query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
}

// ─── Formatting ───────────────────────────────────────────────────────────────
function formatSalary(?float $min, ?float $max, string $currency = 'PKR'): string {
    if (!$min && !$max) return 'Negotiable';
    if ($min && $max) return $currency . ' ' . number_format($min/1000) . 'k – ' . number_format($max/1000) . 'k';
    if ($min) return $currency . ' ' . number_format($min/1000) . 'k+';
    return $currency . ' Up to ' . number_format($max/1000) . 'k';
}

function timeAgo(string $datetime): string {
    $time = time() - strtotime($datetime);
    $units = [
        31536000 => 'year', 2592000 => 'month', 604800 => 'week',
        86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second'
    ];
    foreach ($units as $secs => $label) {
        if ($time >= $secs) {
            $val = floor($time / $secs);
            return $val . ' ' . $label . ($val > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

function badgeStatus(string $status): string {
    $map = [
        'pending'     => 'warning',
        'reviewed'    => 'info',
        'shortlisted' => 'primary',
        'rejected'    => 'danger',
        'hired'       => 'success',
        'active'      => 'success',
        'inactive'    => 'secondary',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . ' text-capitalize">' . e($status) . '</span>';
}

function jobTypeBadge(string $type): string {
    $map = [
        'full-time'  => 'primary',
        'part-time'  => 'info',
        'remote'     => 'success',
        'internship' => 'warning',
        'contract'   => 'secondary',
    ];
    $color = $map[$type] ?? 'secondary';
    return '<span class="badge bg-' . $color . '-subtle text-' . $color . ' text-capitalize fw-medium">' . e($type) . '</span>';
}

function avatarUrl(?string $avatar, string $name = 'User'): string {
    if ($avatar && file_exists(UPLOAD_PATH . $avatar)) {
        return UPLOAD_URL . e($avatar);
    }
    $initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $name), 0, 2)));
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=4f46e5&color=fff&size=100';
}

function logoUrl(?string $logo, string $name = 'C'): string {
    if ($logo && file_exists(UPLOAD_PATH . $logo)) {
        return UPLOAD_URL . e($logo);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0f172a&color=fff&size=80&font-size=0.4&rounded=true';
}
