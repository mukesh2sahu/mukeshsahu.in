<?php
/**
 * Admin User Management - Edit User
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Edit User';
$pageHeading = 'Edit User Account';
require_once __DIR__ . '/../includes/header.php';

$userId = (int)($_GET['id'] ?? 0);
$currentAdminId = current_user_id();
$db = get_db();
$errors = [];

// Fetch user
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    header('Location: ' . ADMIN_URL . '/users/index.php');
    exit;
}

$roles = $db->query("SELECT * FROM user_roles ORDER BY id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? $user['role_id']);
    $status = $_POST['status'] ?? $user['status'];
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($name)) $errors[] = 'Name is required.';

    if (empty($errors)) {
        // Prevent admin from demoting or banning themselves
        if ($userId === $currentAdminId) {
            $roleId = (int)$user['role_id'];
            $status = 'active';
        }

        // Build query
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $upd = $db->prepare("UPDATE users SET name = :name, mobile = :mobile, role_id = :role_id, status = :status, password = :pwd WHERE id = :id");
                $upd->execute([
                    ':name'    => $name,
                    ':mobile'  => $mobile,
                    ':role_id' => $roleId,
                    ':status'  => $status,
                    ':pwd'     => $hash,
                    ':id'      => $userId
                ]);
            }
        } else {
            $upd = $db->prepare("UPDATE users SET name = :name, mobile = :mobile, role_id = :role_id, status = :status WHERE id = :id");
            $upd->execute([
                ':name'    => $name,
                ':mobile'  => $mobile,
                ':role_id' => $roleId,
                ':status'  => $status,
                ':id'      => $userId
            ]);
        }

        if (empty($errors)) {
            log_activity('Edit User', 'users', "Updated details for user #$userId (" . $user['email'] . ")");
            set_flash('success', "User account for $name updated successfully.");
            header('Location: ' . ADMIN_URL . '/users/index.php');
            exit;
        }
    }
}
?>

<div class="custom-card p-4 w-100" style="height: auto;">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Edit Account: <?= e($user['name']) ?></h4>
            <span class="text-muted small">Email: <code><?= e($user['email']) ?></code></span>
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

    <form method="POST" action="<?= ADMIN_URL ?>/users/edit.php?id=<?= $user['id'] ?>" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold small">Email Address</label>
                <input type="email" class="form-control bg-light" id="email" value="<?= e($user['email']) ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="mobile" class="form-label fw-semibold small">Mobile Number</label>
                <input type="tel" class="form-control" id="mobile" name="mobile" value="<?= e($user['mobile']) ?>">
            </div>
            <div class="col-md-6">
                <label for="role_id" class="form-label fw-semibold small">User Role</label>
                <select class="form-select" id="role_id" name="role_id" <?= $user['id'] === $currentAdminId ? 'disabled' : '' ?>>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (int)$user['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                            <?= e($r['display_name']) ?> (<?= e($r['role_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($user['id'] === $currentAdminId): ?>
                    <input type="hidden" name="role_id" value="<?= $user['role_id'] ?>">
                    <div class="form-text small text-warning">You cannot change your own role.</div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label fw-semibold small">Account Status</label>
                <select class="form-select" id="status" name="status" <?= $user['id'] === $currentAdminId ? 'disabled' : '' ?>>
                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="banned" <?= $user['status'] === 'banned' ? 'selected' : '' ?>>Banned / Suspended</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="new_password" class="form-label fw-semibold small">Reset Password (leave blank to keep current)</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 8 chars to change">
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="fa-solid fa-floppy-disk me-2"></i> Update Account
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
