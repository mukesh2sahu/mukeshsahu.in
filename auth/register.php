<?php
/**
 * User Registration
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Register';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

guest_only();

$errors = [];
$name = '';
$email = '';
$mobile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token invalid or expired. Please reload and try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // Validations
    if (empty($name) || mb_strlen($name) < 3) {
        $errors[] = 'Full Name must be at least 3 characters.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (empty($mobile) || strlen($mobile) < 7) {
        $errors[] = 'Please provide a valid mobile number.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$terms) {
        $errors[] = 'You must accept the Terms and Conditions to register.';
    }

    if (empty($errors)) {
        $db = get_db();

        // Check duplicate email
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email address is already registered. Please sign in or use another email.';
        } else {
            try {
                $db->beginTransaction();

                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert into users (role_id 3 = registered user)
                $insertUser = $db->prepare("
                    INSERT INTO users (role_id, name, email, mobile, password, status, is_email_verified, created_at) 
                    VALUES (3, :name, :email, :mobile, :password, 'active', 1, NOW())
                ");
                $insertUser->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':mobile'   => $mobile,
                    ':password' => $hashedPassword
                ]);
                $newUserId = (int)$db->lastInsertId();

                // Create profile entry
                $insertProfile = $db->prepare("
                    INSERT INTO user_profiles (user_id, headline, bio, skills, location) 
                    VALUES (:user_id, 'Learner & Developer', 'Passionate learner on MukeshSahu platform.', 'Web Development, Programming', 'India')
                ");
                $insertProfile->execute([':user_id' => $newUserId]);

                // Create email verification token entry
                $verifToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $insertVerif = $db->prepare("
                    INSERT INTO email_verifications (user_id, token, expires_at) 
                    VALUES (:uid, :token, :expires)
                ");
                $insertVerif->execute([
                    ':uid'     => $newUserId,
                    ':token'   => $verifToken,
                    ':expires' => $expiresAt
                ]);

                $db->commit();

                // Auto login user
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_role_id'] = 3;
                $_SESSION['user_role_display'] = 'Registered User';

                set_flash('success', 'Account created successfully! Welcome to the MukeshSahu platform.');

                $redirect = $_SESSION['redirect_url'] ?? (APP_URL . '/user/dashboard.php');
                unset($_SESSION['redirect_url']);
                header('Location: ' . $redirect);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                error_log('Registration error: ' . $e->getMessage());
                $errors[] = 'Registration failed due to a system error. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="custom-card p-4 p-sm-5">
                <div class="text-center mb-4">
                    <span class="brand-badge mb-2">MS</span>
                    <h2 class="fs-3 fw-bold text-dark">Create Your Account</h2>
                    <p class="text-muted small">Join MukeshSahu to access IT Jobs, Video Courses, and AI Tools</p>
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

                <form method="POST" action="<?= APP_URL ?>/auth/register.php" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" placeholder="e.g. Mukesh Sahu" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. yourname@example.com" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="mobile" class="form-label fw-semibold small">Mobile Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-phone"></i></span>
                            <input type="tel" class="form-control" id="mobile" name="mobile" value="<?= e($mobile) ?>" placeholder="+91 98765 43210" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="password" class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Min. 8 chars" required>
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label for="confirm_password" class="form-label fw-semibold small">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="confirm_password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label small text-secondary" for="terms">
                            I accept the <a href="#" class="text-primary fw-semibold">Terms & Conditions</a> and <a href="#" class="text-primary fw-semibold">Privacy Policy</a>.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 justify-content-center">
                        <i class="fa-solid fa-user-plus me-2"></i> Register Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-secondary">
                    Already have an account? 
                    <a href="<?= APP_URL ?>/auth/login.php" class="fw-bold text-primary">Sign In here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
