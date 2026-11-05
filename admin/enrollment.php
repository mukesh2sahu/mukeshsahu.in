<?php
/**
 * Direct Shortcut Alias: Admin Enrollment -> Course Enrollments
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ' . ADMIN_URL . '/courses/enrollments.php' . $query);
exit;
