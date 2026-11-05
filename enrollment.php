<?php
/**
 * Direct Shortcut Alias: User/Admin Enrollment Gateway
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

if (is_admin()) {
    header('Location: ' . ADMIN_URL . '/courses/enrollments.php');
    exit;
} elseif (is_logged_in()) {
    header('Location: ' . APP_URL . '/user/my-courses.php');
    exit;
} else {
    header('Location: ' . APP_URL . '/login.php?redirect=' . urlencode('/user/my-courses.php'));
    exit;
}
