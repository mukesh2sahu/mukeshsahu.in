<?php
/**
 * AJAX Endpoint: Toggle Save AI Tool
 * MukeshSahu Platform
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/app.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in to save favorite AI tools.']);
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

$toolId = (int)($_POST['tool_id'] ?? 0);
$userId = current_user_id();

if ($toolId <= 0 || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$db = get_db();

// Check if currently saved
$check = $db->prepare("SELECT id FROM saved_ai_tools WHERE user_id = :uid AND tool_id = :tid LIMIT 1");
$check->execute([':uid' => $userId, ':tid' => $toolId]);
$saved = $check->fetch();

if ($saved) {
    // Remove
    $del = $db->prepare("DELETE FROM saved_ai_tools WHERE user_id = :uid AND tool_id = :tid");
    $del->execute([':uid' => $userId, ':tid' => $toolId]);
    $isSaved = false;
} else {
    // Add
    $ins = $db->prepare("INSERT INTO saved_ai_tools (user_id, tool_id, created_at) VALUES (:uid, :tid, NOW())");
    $ins->execute([':uid' => $userId, ':tid' => $toolId]);
    $isSaved = true;
}

// Total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM saved_ai_tools WHERE user_id = :uid");
$countStmt->execute([':uid' => $userId]);
$totalSaved = (int)$countStmt->fetchColumn();

echo json_encode([
    'success'     => true,
    'is_saved'    => $isSaved,
    'total_saved' => $totalSaved
]);
exit;
