<?php
require_once __DIR__ . '/../core/functions.php';

if (!isLoggedIn()) { redirect('/auth/login.php'); }

markNotificationsRead($_SESSION['user_id']);

$back = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/' . $_SESSION['user_role'] . '/dashboard.php';
header('Location: ' . $back);
exit;
