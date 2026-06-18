<?php
require_once __DIR__ . '/../core/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['ok' => false]); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    Database::query(
        "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
        [$id, $_SESSION['user_id']]
    );
}

echo json_encode(['ok' => true]);
