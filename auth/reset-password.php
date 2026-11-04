<?php
/**
 * Reset Password Form
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Choose New Password';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

guest_only();

$errors = [];
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

if (empty($token)) {
    set_flash('danger', 'Password reset token is missing.');
    header('Location: ' . APP_URL . '/auth/forgot-password.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT email, expires_at FROM password_resets WHERE token = :token LIMIT 1");
$stmt->execute([':token' => $token]);
$resetRecord = $stmt->fetch();

if (!$resetRecord || strtotime($resetRecord['expires_at']) < time()) {
    set_flash('danger', 'This password reset link is invalid or has expired. Please request a new one.');
    header('Location: ' . APP_URL . '/auth/forgot-password.php');
    exit;
}

$email = $resetRecord['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security token invalid or expired. Please try again.';
    }

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update user password and clear reset attempts
        $upd = $db->prepare("UPDATE users SET password = :pwd, failed_login_attempts = 0, locked_until = NULL WHERE email = :email");
        $upd->execute([':pwd' => $hash, ':email' => $email]);

        // Remove used token
        $del = $db->prepare("DELETE FROM password_resets WHERE email = :email");
        $del->execute([':email' => $email]);

        set_flash('success', 'Your password has been successfully updated! You can now log in with your new password.');
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="custom-card p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="stat-icon-wrapper bg-success-subtle text-success mx-auto mb-3" style="width: 56px; height: 56px;">
                        <i class="fa-solid fa-lock fs-4"></i>
                    </div>
                    <h2 class="fs-4 fw-bold text-dark">Create New Password</h2>
                    <p class="text-muted small">Enter a strong, secure password for <strong><?= e($email) ?></strong></p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/auth/reset-password.php" novalidate>
                    <?= csrf_field(); ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 8 characters" required autofocus>
                            <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="new_password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold small">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                            <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="confirm_password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 justify-content-center">
                        <i class="fa-solid fa-check me-2"></i> Set New Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
