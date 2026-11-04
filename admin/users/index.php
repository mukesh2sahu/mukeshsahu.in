<?php
/**
 * Admin User Management - Index
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage Users';
$pageHeading = 'User Management';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$currentAdminId = current_user_id();

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $deleteId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $deleteId > 0) {
        if ($deleteId === $currentAdminId) {
            set_flash('danger', 'You cannot delete your own logged-in administrator account.');
        } else {
            $del = $db->prepare("DELETE FROM users WHERE id = :id");
            $del->execute([':id' => $deleteId]);
            log_activity('Delete User', 'users', 'Deleted user account ID: ' . $deleteId);
            set_flash('success', 'User deleted successfully.');
        }
    } else {
        set_flash('danger', 'Invalid security token for deletion.');
    }
    header('Location: ' . ADMIN_URL . '/users/index.php');
    exit;
}

// Handle Status Toggle Action (Active / Inactive / Banned)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $targetId = (int)($_GET['id'] ?? 0);
    $newStatus = $_GET['status'] ?? 'active';
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && in_array($newStatus, ['active', 'inactive', 'banned'])) {
        if ($targetId !== $currentAdminId) {
            $upd = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
            $upd->execute([':status' => $newStatus, ':id' => $targetId]);
            log_activity('Update User Status', 'users', "Changed status of user #$targetId to $newStatus");
            set_flash('success', "User status updated to $newStatus.");
        }
    }
    header('Location: ' . ADMIN_URL . '/users/index.php');
    exit;
}

// Fetch all users
$stmt = $db->query("
    SELECT u.*, r.display_name AS role_display, r.role_name 
    FROM users u 
    JOIN user_roles r ON u.role_id = r.id 
    ORDER BY u.id DESC
");
$users = $stmt->fetchAll();
?>

<div class="custom-card p-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-1">Registered Users & Administrators</h4>
            <p class="text-muted small mb-0">Total <?= count($users) ?> registered platform members</p>
        </div>
        <a href="<?= ADMIN_URL ?>/users/add.php" class="btn btn-primary-custom btn-sm">
            <i class="fa-solid fa-user-plus me-1"></i> Add New User
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#ID</th>
                    <th>User Details</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Registered</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $u['id'] ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($u['name']) ?></div>
                            <div class="text-muted small"><?= e($u['email']) ?></div>
                        </td>
                        <td>
                            <span class="small text-secondary"><?= e($u['mobile']) ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $u['role_name'] === 'super_admin' ? 'bg-danger' : ($u['role_name'] === 'admin' ? 'bg-primary' : 'bg-secondary') ?>">
                                <?= e($u['role_display']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php elseif ($u['status'] === 'inactive'): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Inactive</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Banned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_email_verified']): ?>
                                <i class="fa-solid fa-circle-check text-success" title="Verified"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle-xmark text-muted" title="Unverified"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="small text-muted"><?= format_date($u['created_at']) ?></span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                    <li>
                                        <a class="dropdown-item" href="<?= ADMIN_URL ?>/users/edit.php?id=<?= $u['id'] ?>">
                                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit User
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php if ($u['id'] !== $currentAdminId): ?>
                                        <?php if ($u['status'] !== 'active'): ?>
                                            <li>
                                                <a class="dropdown-item text-success" href="<?= ADMIN_URL ?>/users/index.php?action=toggle_status&status=active&id=<?= $u['id'] ?>&token=<?= csrf_token() ?>">
                                                    <i class="fa-solid fa-circle-check me-2"></i> Activate
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($u['status'] !== 'banned'): ?>
                                            <li>
                                                <a class="dropdown-item text-warning" href="<?= ADMIN_URL ?>/users/index.php?action=toggle_status&status=banned&id=<?= $u['id'] ?>&token=<?= csrf_token() ?>">
                                                    <i class="fa-solid fa-ban me-2"></i> Suspend / Ban
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="<?= ADMIN_URL ?>/users/index.php?action=delete&id=<?= $u['id'] ?>&token=<?= csrf_token() ?>" onclick="return confirm('Are you sure you want to permanently delete this user? This cannot be undone.');">
                                                <i class="fa-solid fa-trash-can me-2"></i> Delete User
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
