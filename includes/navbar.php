<?php
/**
 * Sticky Navigation Bar Component
 * MukeshSahu Platform
 */

declare(strict_types=1);

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isUserLoggedIn = is_logged_in();
$user = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid px-3 px-md-4 px-xl-5">
        <!-- Brand Logo & Name -->
        <a class="navbar-brand navbar-brand-logo" href="<?= APP_URL ?>/index.php">
            <span class="brand-badge">MS</span>
            <span class="brand-name"><?= e(get_setting('site_name', 'MukeshSahu')) ?></span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainContent" aria-controls="navbarMainContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars-staggered fs-4 text-primary"></i>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMainContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php">
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/about.php">
                        About Me
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['jobs.php', 'job-detail.php']) ? 'active' : '' ?>" href="<?= APP_URL ?>/jobs.php">
                        IT Jobs
                        <?php if (!$isUserLoggedIn): ?>
                            <i class="fa-solid fa-lock badge-auth-lock" title="Login Required" data-bs-toggle="tooltip"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['courses.php', 'course-detail.php', 'watch.php']) ? 'active' : '' ?>" href="<?= APP_URL ?>/courses.php">
                        Courses
                        <?php if (!$isUserLoggedIn): ?>
                            <i class="fa-solid fa-lock badge-auth-lock" title="Login Required" data-bs-toggle="tooltip"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['ai-tools.php', 'ai-tool-detail.php']) ? 'active' : '' ?>" href="<?= APP_URL ?>/ai-tools.php">
                        AI Tools
                        <?php if (!$isUserLoggedIn): ?>
                            <i class="fa-solid fa-lock badge-auth-lock" title="Login Required" data-bs-toggle="tooltip"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['blogs.php', 'blog-detail.php']) ? 'active' : '' ?>" href="<?= APP_URL ?>/blogs.php">
                        Blogs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/contact.php">
                        Contact
                    </a>
                </li>
            </ul>

            <!-- Authentication Actions -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($isUserLoggedIn): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-custom dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user fs-5"></i>
                            <span class="d-none d-sm-inline fw-semibold"><?= e(explode(' ', $user['name'] ?? 'User')[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" aria-labelledby="userMenuButton" style="min-width: 230px; border-radius: var(--radius-md);">
                            <li class="px-3 py-2 border-bottom mb-1">
                                <div class="fw-bold text-dark"><?= e($user['name'] ?? '') ?></div>
                                <div class="small text-muted text-truncate"><?= e($user['email'] ?? '') ?></div>
                                <span class="badge bg-primary-subtle text-primary mt-1"><?= e($user['role_name'] ?? 'User') ?></span>
                            </li>
                            
                            <?php if (is_admin()): ?>
                                <li>
                                    <a class="dropdown-item py-2 fw-semibold text-primary" href="<?= ADMIN_URL ?>/dashboard.php">
                                        <i class="fa-solid fa-gauge-high me-2"></i> Admin Panel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 text-primary" href="<?= ADMIN_URL ?>/courses/enrollments.php">
                                        <i class="fa-solid fa-user-graduate me-2"></i> Course Enrollments
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>

                            <li>
                                <a class="dropdown-item py-2" href="<?= APP_URL ?>/user/dashboard.php">
                                    <i class="fa-solid fa-table-columns me-2 text-muted"></i> User Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= APP_URL ?>/user/my-courses.php">
                                    <i class="fa-solid fa-graduation-cap me-2 text-muted"></i> My Enrolled Courses
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= APP_URL ?>/user/saved-jobs.php">
                                    <i class="fa-solid fa-bookmark me-2 text-muted"></i> Saved IT Jobs
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= APP_URL ?>/user/saved-tools.php">
                                    <i class="fa-solid fa-heart me-2 text-muted"></i> Favorite AI Tools
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= APP_URL ?>/user/profile.php">
                                    <i class="fa-solid fa-user-gear me-2 text-muted"></i> Profile Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?= APP_URL ?>/auth/logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline-custom">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                    </a>
                    <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary-custom d-none d-sm-inline-flex">
                        <i class="fa-solid fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
