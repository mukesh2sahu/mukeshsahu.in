<?php
/**
 * Admin Panel Sidebar Component
 * MukeshSahu Platform
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="admin-sidebar collapse d-md-block" id="adminSidebarMenu" style="width: 260px; flex-shrink: 0;">
    <div class="px-3 pb-3 mb-3 border-bottom border-secondary border-opacity-25">
        <a class="navbar-brand navbar-brand-logo text-white" href="<?= ADMIN_URL ?>/dashboard.php">
            <span class="brand-badge">MS</span>
            <span class="brand-name text-white fs-5">Admin Panel</span>
        </a>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, 'dashboard.php') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/dashboard.php">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, '/users/') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/users/index.php">
                <i class="fa-solid fa-users"></i> Users Management
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, '/jobs/') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/jobs/index.php">
                <i class="fa-solid fa-briefcase"></i> IT Jobs Portal
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= (str_contains($currentUri, '/courses/') && !str_contains($currentUri, 'enrollments.php')) ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/courses/index.php">
                <i class="fa-solid fa-graduation-cap"></i> Courses & Videos
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, 'enrollments.php') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/courses/enrollments.php">
                <i class="fa-solid fa-user-graduate"></i> Course Enrollments
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, '/ai-tools/') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/ai-tools/index.php">
                <i class="fa-solid fa-robot"></i> AI Tools Directory
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, '/blogs/') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/blogs/index.php">
                <i class="fa-solid fa-newspaper"></i> Tech Blogs
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, '/contacts/') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/contacts/index.php">
                <i class="fa-solid fa-envelope"></i> Contact Messages
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, '/reports/') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/reports/index.php">
                <i class="fa-solid fa-chart-line"></i> Analytics & Reports
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= str_contains($currentUri, '/settings/') ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/settings/index.php">
                <i class="fa-solid fa-gears"></i> Website Settings
            </a>
        </li>

        <li class="nav-item mt-4 pt-3 border-top border-secondary border-opacity-25">
            <a class="nav-link text-danger" href="<?= APP_URL ?>/auth/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </a>
        </li>
    </ul>
</aside>
