<?php
/**
 * User Login
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Login';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

guest_only();

$errors = [];
$email = '';

// Capture redirect URL if present in query param
if (!empty($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token invalid or expired. Please refresh and try again.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (empty($password)) {
        $errors[] = 'Please provide your password.';
    }

    if (empty($errors)) {
        // Check rate limit / brute force lock
        if (!check_login_attempts($email)) {
            $errors[] = 'Too many failed login attempts. Your account has been temporarily locked for 15 minutes. Please try again later.';
        } else {
            $db = get_db();
            $stmt = $db->prepare("
                SELECT u.*, r.role_name, r.display_name AS role_display, p.avatar 
                FROM users u 
                JOIN user_roles r ON u.role_id = r.id 
                LEFT JOIN user_profiles p ON u.id = p.user_id 
                WHERE u.email = :email 
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'banned') {
                    $errors[] = 'Your account has been suspended. Please contact platform administration.';
                } elseif ($user['status'] === 'inactive') {
                    $errors[] = 'Your account is deactivated. Please verify your email or contact support.';
                } else {
                    // Successful login
                    reset_login_attempts($email);

                    // Set user session
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role_name'];
                    $_SESSION['user_role_id'] = (int)$user['role_id'];
                    $_SESSION['user_role_display'] = $user['role_display'];
                    $_SESSION['user_avatar'] = $user['avatar'];

                    // Remember Me Cookie
                    if ($remember) {
                        $rememberToken = bin2hex(random_bytes(32));
                        $upd = $db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                        $upd->execute([':token' => $rememberToken, ':id' => $user['id']]);
                        setcookie('ms_remember', $user['id'] . ':' . $rememberToken, [
                            'expires'  => time() + (30 * 24 * 60 * 60),
                            'path'     => '/',
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                    }

                    // Log activity
                    if (in_array($user['role_name'], ['admin', 'super_admin'])) {
                        log_activity('Admin Login', 'auth', 'Admin signed in successfully.');
                    }

                    set_flash('success', 'Welcome back, ' . e($user['name']) . '!');

                    // Redirect to originally requested page or dashboard
                    $redirect = $_SESSION['redirect_url'] ?? null;
                    unset($_SESSION['redirect_url']);

                    if ($redirect) {
                        header('Location: ' . $redirect);
                    } elseif (in_array($user['role_name'], ['admin', 'super_admin'])) {
                        header('Location: ' . ADMIN_URL . '/dashboard.php');
                    } else {
                        header('Location: ' . APP_URL . '/user/dashboard.php');
                    }
                    exit;
                }
            } else {
                record_failed_login($email);
                $errors[] = 'Invalid email address or password. Please verify your credentials.';
            }
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
                    <span class="brand-badge mb-2">MS</span>
                    <h2 class="fs-3 fw-bold text-dark">Sign In</h2>
                    <p class="text-muted small">Access your account, courses, saved jobs, and AI tools</p>
                </div>

                <!-- Demo Credentials Quick Pill -->
                <div class="alert alert-info py-2 px-3 small border-0 mb-4" style="background-color: #f0fdf4; border-left: 4px solid #16a34a !important;">
                    <div class="fw-bold text-success mb-1"><i class="fa-solid fa-key me-1"></i> Demo Credentials:</div>
                    <div class="d-flex flex-column gap-1 text-secondary" style="font-size: 0.82rem;">
                        <div><strong>Admin:</strong> <code>admin@mukeshsahu.in</code> | <code>Admin@12345</code></div>
                        <div><strong>User:</strong> <code>user@mukeshsahu.in</code> | <code>User@12345</code></div>
                    </div>
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

                <form method="POST" action="<?= APP_URL ?>/auth/login.php" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. admin@mukeshsahu.in" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label fw-semibold small mb-0">Password</label>
                            <a href="<?= APP_URL ?>/auth/forgot-password.php" class="small text-primary fw-semibold">Forgot Password?</a>
                        </div>
                        <div class="input-group mt-1">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label small text-secondary" for="remember">
                            Remember me on this browser
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 justify-content-center">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Sign In
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-secondary">
                    Don't have an account yet? 
                    <a href="<?= APP_URL ?>/auth/register.php" class="fw-bold text-primary">Create an account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
