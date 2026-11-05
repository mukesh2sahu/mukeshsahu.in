<?php
/**
 * User Change Password
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Change Password';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_login();

$userId = current_user_id();
$db = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token invalid or expired. Please refresh and try again.';
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword)) {
        $errors[] = 'Please enter your current password.';
    }
    if (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $existingHash = $stmt->fetchColumn();

        if ($existingHash && password_verify($currentPassword, $existingHash)) {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE users SET password = :pwd WHERE id = :uid");
            $upd->execute([':pwd' => $newHash, ':uid' => $userId]);

            set_flash('success', 'Your password has been changed successfully.');
            header('Location: ' . APP_URL . '/user/change-password.php');
            exit;
        } else {
            $errors[] = 'Your current password is incorrect.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-3 col-xxl-2">
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Change Password Content -->
        <div class="col-lg-9 col-xl-9 col-xxl-10">
            <div class="custom-card card-static p-4 p-md-5 w-100">
                <div class="mb-4 pb-3 border-bottom">
                    <h3 class="fs-4 fw-bold mb-1">Change Account Password</h3>
                    <p class="text-muted small mb-0">Ensure your account remains safe with a strong, distinct password</p>
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

                <form method="POST" action="<?= APP_URL ?>/user/change-password.php" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold small">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-key"></i></span>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="current_password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 8 characters" required>
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

                    <button type="submit" class="btn btn-primary-custom px-4 py-2">
                        <i class="fa-solid fa-shield-check me-2"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
