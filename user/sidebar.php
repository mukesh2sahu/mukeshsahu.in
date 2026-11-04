<?php
/**
 * User Dashboard Sidebar Component
 * MukeshSahu Platform
 */

declare(strict_types=1);

$user = current_user();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

$db = get_db();
// Get user profile details
$profileStmt = $db->prepare("SELECT headline, avatar FROM user_profiles WHERE user_id = :uid LIMIT 1");
$profileStmt->execute([':uid' => $user['id']]);
$profile = $profileStmt->fetch() ?: [];

$avatarUrl = !empty($profile['avatar']) && file_exists(APP_ROOT . '/assets/uploads/avatars/' . $profile['avatar'])
    ? ASSETS_URL . '/uploads/avatars/' . e($profile['avatar'])
    : ASSETS_URL . '/images/profile-mukesh.png';
?>

<div class="dashboard-sidebar shadow-sm">
    <div class="text-center pb-4 mb-3 border-bottom">
        <div class="position-relative d-inline-block mb-3">
            <img src="<?= $avatarUrl ?>" alt="<?= e($user['name']) ?>" class="rounded-circle border border-3 border-primary shadow-sm" style="width: 84px; height: 84px; object-fit: cover;">
            <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-1" title="Online"></span>
        </div>
        <h5 class="fw-bold mb-1 text-dark"><?= e($user['name']) ?></h5>
        <div class="small text-muted text-truncate mb-2"><?= e($user['email']) ?></div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
            <?= e($user['role_name']) ?>
        </span>
    </div>

    <ul class="nav flex-column dashboard-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/user/dashboard.php">
                <i class="fa-solid fa-table-columns"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentScript === 'my-courses.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/user/my-courses.php">
                <i class="fa-solid fa-graduation-cap"></i> My Courses
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentScript === 'saved-jobs.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/user/saved-jobs.php">
                <i class="fa-solid fa-bookmark"></i> Saved IT Jobs
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentScript === 'saved-tools.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/user/saved-tools.php">
                <i class="fa-solid fa-heart"></i> Favorite AI Tools
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/user/profile.php">
                <i class="fa-solid fa-user-gear"></i> Profile Settings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentScript === 'change-password.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/user/change-password.php">
                <i class="fa-solid fa-key"></i> Change Password
            </a>
        </li>

        <?php if (is_admin()): ?>
            <li class="nav-item mt-3 pt-3 border-top">
                <a class="nav-link text-primary fw-bold" href="<?= ADMIN_URL ?>/dashboard.php">
                    <i class="fa-solid fa-shield-halved"></i> Admin Console
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-item mt-2 pt-2 border-top">
            <a class="nav-link text-danger" href="<?= APP_URL ?>/auth/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </li>
    </ul>
</div>
