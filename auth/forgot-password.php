<?php
/**
 * Forgot Password
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

guest_only();

$errors = [];
$successMessage = '';
$resetUrl = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token invalid or expired. Please refresh and try again.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Remove old tokens for this email
            $del = $db->prepare("DELETE FROM password_resets WHERE email = :email");
            $del->execute([':email' => $email]);

            // Insert new token
            $ins = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
            $ins->execute([
                ':email'   => $email,
                ':token'   => $resetToken,
                ':expires' => $expiresAt
            ]);

            $resetUrl = APP_URL . '/auth/reset-password.php?token=' . $resetToken;
            $successMessage = 'A password reset link has been generated. For local/development environments, click the button below to proceed to the password reset form.';
        } else {
            // Ambiguous message for security
            $successMessage = 'If an account exists with this email address, password reset instructions have been generated.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="custom-card p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary mx-auto mb-3" style="width: 56px; height: 56px;">
                        <i class="fa-solid fa-key fs-4"></i>
                    </div>
                    <h2 class="fs-4 fw-bold text-dark">Reset Password</h2>
                    <p class="text-muted small">Enter your account email to receive a password recovery link.</p>
                </div>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i>
                        <div><?= e($successMessage) ?></div>
                        <?php if ($resetUrl): ?>
                            <div class="mt-3">
                                <a href="<?= $resetUrl ?>" class="btn btn-sm btn-success fw-semibold">
                                    <i class="fa-solid fa-unlock-keyhole me-1"></i> Proceed to Reset Password Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

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

                <?php if (!$resetUrl): ?>
                    <form method="POST" action="<?= APP_URL ?>/auth/forgot-password.php" novalidate>
                        <?= csrf_field(); ?>

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold small">Registered Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. user@mukeshsahu.in" required autofocus>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-custom w-100 py-2 justify-content-center">
                            <i class="fa-solid fa-paper-plane me-2"></i> Send Reset Link
                        </button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-4 pt-3 border-top small text-secondary">
                    Remember your password? 
                    <a href="<?= APP_URL ?>/auth/login.php" class="fw-bold text-primary">Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
