<?php
/**
 * Admin User Management - Add User
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Add New User';
$pageHeading = 'Create User Account';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$errors = [];

// Fetch available roles
$roles = $db->query("SELECT * FROM user_roles ORDER BY id ASC")->fetchAll();

$name = '';
$email = '';
$mobile = '';
$roleId = 3;
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId = (int)($_POST['role_id'] ?? 3);
    $status = $_POST['status'] ?? 'active';

    if (empty($name)) $errors[] = 'Name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        // Check duplicate email
        $check = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'This email address is already in use.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                INSERT INTO users (role_id, name, email, mobile, password, status, is_email_verified, created_at)
                VALUES (:role_id, :name, :email, :mobile, :password, :status, 1, NOW())
            ");
            $stmt->execute([
                ':role_id'  => $roleId,
                ':name'     => $name,
                ':email'    => $email,
                ':mobile'   => $mobile,
                ':password' => $hash,
                ':status'   => $status
            ]);
            $newId = (int)$db->lastInsertId();

            // Create initial profile
            $insProfile = $db->prepare("INSERT INTO user_profiles (user_id, headline, bio) VALUES (:uid, 'Member', 'Registered platform user')");
            $insProfile->execute([':uid' => $newId]);

            log_activity('Add User', 'users', "Created new user $email (#$newId)");
            set_flash('success', "User account for $name created successfully.");
            header('Location: ' . ADMIN_URL . '/users/index.php');
            exit;
        }
    }
}
?>

<div class="custom-card p-4 p-md-5" style="max-width: 750px;">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Create Platform Account</h4>
            <span class="text-muted small">Provide details for the new user or administrator</span>
        </div>
        <a href="<?= ADMIN_URL ?>/users/index.php" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Users
        </a>
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

    <form method="POST" action="<?= ADMIN_URL ?>/users/add.php" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="mobile" class="form-label fw-semibold small">Mobile Number</label>
                <input type="tel" class="form-control" id="mobile" name="mobile" value="<?= e($mobile) ?>" placeholder="+91 98765 43210">
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label fw-semibold small">Temporary Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Min. 8 characters" required>
            </div>
            <div class="col-md-6">
                <label for="role_id" class="form-label fw-semibold small">User Role <span class="text-danger">*</span></label>
                <select class="form-select" id="role_id" name="role_id" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $roleId === (int)$r['id'] ? 'selected' : '' ?>>
                            <?= e($r['display_name']) ?> (<?= e($r['role_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label fw-semibold small">Account Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned / Suspended</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="fa-solid fa-user-plus me-2"></i> Create Account
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
