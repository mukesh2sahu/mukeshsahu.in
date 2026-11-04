<?php
/**
 * AJAX Endpoint: Toggle Course Lecture Progress
 * MukeshSahu Platform
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/app.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to save your course progress.']);
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

$courseId = (int)($_POST['course_id'] ?? 0);
$lectureId = (int)($_POST['lecture_id'] ?? 0);
$userId = current_user_id();

if ($courseId <= 0 || $lectureId <= 0 || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$db = get_db();

// Auto-enroll if not already enrolled
$checkEnroll = $db->prepare("SELECT id FROM course_enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
$checkEnroll->execute([':uid' => $userId, ':cid' => $courseId]);
if (!$checkEnroll->fetch()) {
    $insEnroll = $db->prepare("INSERT INTO course_enrollments (user_id, course_id, progress_percentage, last_lecture_id, enrolled_at) VALUES (:uid, :cid, 0, :lid, NOW())");
    $insEnroll->execute([':uid' => $userId, ':cid' => $courseId, ':lid' => $lectureId]);
} else {
    // Update last lecture
    $updLecture = $db->prepare("UPDATE course_enrollments SET last_lecture_id = :lid, last_accessed_at = NOW() WHERE user_id = :uid AND course_id = :cid");
    $updLecture->execute([':lid' => $lectureId, ':uid' => $userId, ':cid' => $courseId]);
}

// Check if currently marked completed
$checkProgress = $db->prepare("SELECT id, is_completed FROM lecture_progress WHERE user_id = :uid AND course_id = :cid AND lecture_id = :lid LIMIT 1");
$checkProgress->execute([':uid' => $userId, ':cid' => $courseId, ':lid' => $lectureId]);
$progress = $checkProgress->fetch();

if ($progress && $progress['is_completed']) {
    // Unmark
    $del = $db->prepare("DELETE FROM lecture_progress WHERE id = :id");
    $del->execute([':id' => $progress['id']]);
    $isCompleted = false;
} else {
    // Mark completed
    $ins = $db->prepare("
        INSERT INTO lecture_progress (user_id, course_id, lecture_id, is_completed, completed_at)
        VALUES (:uid, :cid, :lid, 1, NOW())
        ON DUPLICATE KEY UPDATE is_completed = 1, completed_at = NOW()
    ");
    $ins->execute([':uid' => $userId, ':cid' => $courseId, ':lid' => $lectureId]);
    $isCompleted = true;
}

// Recalculate course percentage
$newPercentage = recalculate_course_progress($userId, $courseId);

echo json_encode([
    'success'             => true,
    'is_completed'        => $isCompleted,
    'progress_percentage' => $newPercentage
]);
exit;
