<?php
/**
 * AJAX Endpoint: Toggle Save Job
 * MukeshSahu Platform
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/app.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please sign in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

$jobId = (int)($_POST['job_id'] ?? 0);
$userId = current_user_id();

if ($jobId <= 0 || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$db = get_db();

// Verify job exists
$checkJob = $db->prepare("SELECT id FROM jobs WHERE id = :id AND status = 'published' LIMIT 1");
$checkJob->execute([':id' => $jobId]);
if (!$checkJob->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Job not found or inactive.']);
    exit;
}

// Check if currently saved
$checkSaved = $db->prepare("SELECT id FROM saved_jobs WHERE user_id = :uid AND job_id = :jid LIMIT 1");
$checkSaved->execute([':uid' => $userId, ':jid' => $jobId]);
$saved = $checkSaved->fetch();

if ($saved) {
    // Remove bookmark
    $del = $db->prepare("DELETE FROM saved_jobs WHERE user_id = :uid AND job_id = :jid");
    $del->execute([':uid' => $userId, ':jid' => $jobId]);
    $isSaved = false;
} else {
    // Add bookmark
    $ins = $db->prepare("INSERT INTO saved_jobs (user_id, job_id, created_at) VALUES (:uid, :jid, NOW())");
    $ins->execute([':uid' => $userId, ':jid' => $jobId]);
    $isSaved = true;
}

// Get total saved count for user
$countStmt = $db->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = :uid");
$countStmt->execute([':uid' => $userId]);
$totalSaved = (int)$countStmt->fetchColumn();

echo json_encode([
    'success'     => true,
    'is_saved'    => $isSaved,
    'total_saved' => $totalSaved
]);
exit;
