<?php
/**
 * Admin Reports CSV Export Endpoint
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth-check.php';

require_admin();

$type = $_GET['type'] ?? 'users';
$db = get_db();

$filename = 'report_' . $type . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

if ($type === 'users') {
    fputcsv($output, ['ID', 'Name', 'Email', 'Mobile', 'Role', 'Status', 'Email Verified', 'Created At']);
    $stmt = $db->query("
        SELECT u.id, u.name, u.email, u.mobile, r.display_name, u.status, u.is_email_verified, u.created_at
        FROM users u
        JOIN user_roles r ON u.role_id = r.id
        ORDER BY u.id ASC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'enrollments') {
    fputcsv($output, ['Enrollment ID', 'Student Name', 'Email', 'Course Title', 'Progress %', 'Enrolled At', 'Last Accessed']);
    $stmt = $db->query("
        SELECT e.id, u.name, u.email, c.title, e.progress_percentage, e.enrolled_at, e.last_accessed_at
        FROM course_enrollments e
        JOIN users u ON e.user_id = u.id
        JOIN courses c ON e.course_id = c.id
        ORDER BY e.enrolled_at DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'jobs') {
    fputcsv($output, ['Job ID', 'Title', 'Company', 'Category', 'Mode', 'Location', 'Salary Range', 'Status', 'Posted At']);
    $stmt = $db->query("
        SELECT j.id, j.title, j.company_name, c.name, j.work_mode, j.location, j.salary_range, j.status, j.created_at
        FROM jobs j
        JOIN job_categories c ON j.category_id = c.id
        ORDER BY j.id DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} elseif ($type === 'contacts') {
    fputcsv($output, ['Message ID', 'Sender Name', 'Email', 'Mobile', 'Subject', 'Message', 'Is Read', 'Date']);
    $stmt = $db->query("
        SELECT id, name, email, mobile, subject, message, is_read, created_at
        FROM contact_messages
        ORDER BY id DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
