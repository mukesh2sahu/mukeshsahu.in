<?php
/**
 * Admin Panel Header Component
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';

// Enforce admin privileges
require_admin();

$adminUser = current_user();
$pageTitle = isset($pageTitle) ? e($pageTitle) . ' | Admin Console' : 'Admin Console | MukeshSahu';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=<?= APP_VERSION ?>">

    <style>
        .admin-sidebar {
            background-color: #0f172a;
            min-height: 100vh;
            color: #94a3b8;
            padding: 1.5rem 1rem;
        }
        .admin-sidebar .nav-link {
            color: #94a3b8;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background-color: rgba(37, 99, 235, 0.2);
            color: #fff;
            border-left: 3px solid #3b82f6;
        }
        .admin-content {
            background-color: #f8fafc;
            min-height: 100vh;
            padding: 2rem;
        }
        .admin-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 2rem;
        }
    </style>
    <script>
        window.APP_URL = "<?= APP_URL ?>";
        window.ADMIN_URL = "<?= ADMIN_URL ?>";
    </script>
</head>
<body class="bg-light">

<div class="d-flex">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <!-- Top Navbar -->
        <header class="admin-topbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebarMenu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h5 class="fw-bold mb-0 text-dark"><?= isset($pageHeading) ? e($pageHeading) : 'Administration' ?></h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="<?= APP_URL ?>/index.php" target="_blank" class="btn btn-outline-custom btn-sm">
                    <i class="fa-solid fa-globe me-1"></i> View Live Site
                </a>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user-shield text-primary"></i>
                        <span class="small fw-bold"><?= e($adminUser['name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item py-2" href="<?= ADMIN_URL ?>/settings/index.php"><i class="fa-solid fa-gear me-2 text-muted"></i> Settings</a></li>
                        <li><a class="dropdown-item py-2" href="<?= APP_URL ?>/user/dashboard.php"><i class="fa-solid fa-table-columns me-2 text-muted"></i> User View</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="<?= APP_URL ?>/auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        <div class="px-4 pt-3">
            <?= display_flash(); ?>
        </div>

        <!-- Page body -->
        <main class="admin-content flex-grow-1">
