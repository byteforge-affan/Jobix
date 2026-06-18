<?php
require_once __DIR__ . '/../core/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['user_role'] !== 'candidate') {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$jobId  = (int)($input['job_id'] ?? 0);

if (!$jobId) { echo json_encode(['error' => 'Invalid job']); exit; }

$candidate = Database::fetch("SELECT id FROM candidates WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$candidate) { echo json_encode(['error' => 'Not a candidate']); exit; }

$exists = Database::count(
    "SELECT COUNT(*) FROM saved_jobs WHERE candidate_id = ? AND job_id = ?",
    [$candidate['id'], $jobId]
);

if ($exists) {
    Database::query("DELETE FROM saved_jobs WHERE candidate_id = ? AND job_id = ?", [$candidate['id'], $jobId]);
    echo json_encode(['saved' => false]);
} else {
    Database::query("INSERT IGNORE INTO saved_jobs (candidate_id, job_id) VALUES (?,?)", [$candidate['id'], $jobId]);
    echo json_encode(['saved' => true]);
}
