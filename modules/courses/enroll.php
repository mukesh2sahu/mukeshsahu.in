<?php
/**
 * Enroll User in Course Endpoint
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';

require_login();

$courseId = (int)($_GET['id'] ?? ($_POST['course_id'] ?? 0));
$userId = current_user_id();

if ($courseId <= 0 || !$userId) {
    set_flash('danger', 'Invalid course specified.');
    header('Location: ' . APP_URL . '/courses.php');
    exit;
}

$db = get_db();

// Verify course exists
$stmt = $db->prepare("SELECT id, title FROM courses WHERE id = :id AND status = 'published' LIMIT 1");
$stmt->execute([':id' => $courseId]);
$course = $stmt->fetch();

if (!$course) {
    set_flash('danger', 'Course not found.');
    header('Location: ' . APP_URL . '/courses.php');
    exit;
}

// Check first lecture
$lecStmt = $db->prepare("SELECT id FROM course_lectures WHERE course_id = :cid ORDER BY order_index ASC LIMIT 1");
$lecStmt->execute([':cid' => $courseId]);
$firstLectureId = $lecStmt->fetchColumn();

// Check if already enrolled
$check = $db->prepare("SELECT id, last_lecture_id FROM course_enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
$check->execute([':uid' => $userId, ':cid' => $courseId]);
$enrollment = $check->fetch();

if (!$enrollment) {
    $ins = $db->prepare("
        INSERT INTO course_enrollments (user_id, course_id, progress_percentage, last_lecture_id, enrolled_at)
        VALUES (:uid, :cid, 0, :lid, NOW())
    ");
    $ins->execute([
        ':uid' => $userId,
        ':cid' => $courseId,
        ':lid' => $firstLectureId ?: null
    ]);
    set_flash('success', 'You have successfully enrolled in "' . e($course['title']) . '"! Enjoy your learning.');
}

$targetLecture = $enrollment['last_lecture_id'] ?? $firstLectureId;

header('Location: ' . APP_URL . '/watch.php?course_id=' . $courseId . ($targetLecture ? '&lecture_id=' . $targetLecture : ''));
exit;
