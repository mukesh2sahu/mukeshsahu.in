<?php
/**
 * Email Verification
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    set_flash('danger', 'Verification token is missing.');
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT user_id, expires_at FROM email_verifications WHERE token = :token LIMIT 1");
$stmt->execute([':token' => $token]);
$verif = $stmt->fetch();

if (!$verif || strtotime($verif['expires_at']) < time()) {
    set_flash('danger', 'Email verification token is invalid or has expired.');
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

// Update user email verified status
$upd = $db->prepare("UPDATE users SET is_email_verified = 1 WHERE id = :id");
$upd->execute([':id' => $verif['user_id']]);

// Delete token
$del = $db->prepare("DELETE FROM email_verifications WHERE token = :token");
$del->execute([':token' => $token]);

set_flash('success', 'Your email address has been successfully verified! You can now sign in.');
header('Location: ' . APP_URL . '/auth/login.php');
exit;
