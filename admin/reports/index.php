<?php
/**
 * Admin Reports & Analytics Dashboard
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Reports & Data Export';
$pageHeading = 'Platform Reports & Analytics';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// 1. User Summary Stats
$userStats = $db->query("
    SELECT r.display_name, COUNT(u.id) as count
    FROM user_roles r
    LEFT JOIN users u ON r.id = u.role_id
    GROUP BY r.id
")->fetchAll();

// 2. Course Enrollment Stats
$courseStats = $db->query("
    SELECT c.title, COUNT(e.id) as enrollments,
           SUM(CASE WHEN e.progress_percentage >= 100 THEN 1 ELSE 0 END) as completions,
           ROUND(AVG(e.progress_percentage), 1) as avg_progress
    FROM courses c
    LEFT JOIN course_enrollments e ON c.id = e.course_id
    GROUP BY c.id
    ORDER BY enrollments DESC
")->fetchAll();

// 3. Job Distribution Stats
$jobStats = $db->query("
    SELECT cat.name, COUNT(j.id) as total_jobs,
           SUM(CASE WHEN j.work_mode = 'Remote' THEN 1 ELSE 0 END) as remote_jobs,
           SUM(CASE WHEN j.status = 'published' THEN 1 ELSE 0 END) as published_jobs
    FROM job_categories cat
    LEFT JOIN jobs j ON cat.id = j.category_id
    GROUP BY cat.id
    HAVING total_jobs > 0
    ORDER BY total_jobs DESC
")->fetchAll();

// 4. Contact Inquiries Stats
$msgTotal = (int)$db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$msgUnread = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
?>

<div class="row g-4 mb-4">
    <!-- Report 1: Users -->
    <div class="col-lg-6">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fs-6 fw-bold text-dark mb-0"><i class="fa-solid fa-users text-primary me-2"></i> User Registration Report</h5>
                    <span class="small text-muted">Role breakdown</span>
                </div>
                <a href="<?= ADMIN_URL ?>/reports/export.php?type=users" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> Export Users CSV
                </a>
            </div>

            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Role Name</th>
                        <th class="text-end">Registered Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userStats as $us): ?>
                        <tr>
                            <td><?= e($us['display_name']) ?></td>
                            <td class="text-end fw-bold"><?= (int)$us['count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report 2: Jobs -->
    <div class="col-lg-6">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fs-6 fw-bold text-dark mb-0"><i class="fa-solid fa-briefcase text-success me-2"></i> IT Job Posting Report</h5>
                    <span class="small text-muted">Category and remote distribution</span>
                </div>
                <a href="<?= ADMIN_URL ?>/reports/export.php?type=jobs" class="btn btn-outline-success btn-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> Export Jobs CSV
                </a>
            </div>

            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Category</th>
                        <th class="text-center">Remote</th>
                        <th class="text-center">Live</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobStats as $js): ?>
                        <tr>
                            <td><?= e($js['name']) ?></td>
                            <td class="text-center text-muted"><?= (int)$js['remote_jobs'] ?></td>
                            <td class="text-center text-success fw-bold"><?= (int)$js['published_jobs'] ?></td>
                            <td class="text-end fw-bold"><?= (int)$js['total_jobs'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report 3: Course Enrollments -->
    <div class="col-lg-8">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fs-6 fw-bold text-dark mb-0"><i class="fa-solid fa-graduation-cap text-purple me-2"></i> Course Progress & Completion Report</h5>
                    <span class="small text-muted">Student enrollment velocity</span>
                </div>
                <a href="<?= ADMIN_URL ?>/reports/export.php?type=enrollments" class="btn btn-outline-custom btn-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> Export Enrollments CSV
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Course Title</th>
                            <th class="text-center">Enrolled</th>
                            <th class="text-center">Completed</th>
                            <th class="text-end">Avg Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courseStats as $cs): ?>
                            <tr>
                                <td><?= e($cs['title']) ?></td>
                                <td class="text-center fw-bold"><?= (int)$cs['enrollments'] ?></td>
                                <td class="text-center text-success"><?= (int)$cs['completions'] ?></td>
                                <td class="text-end fw-bold text-primary"><?= (float)($cs['avg_progress'] ?? 0) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Report 4: Contact Messages -->
    <div class="col-lg-4">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fs-6 fw-bold text-dark mb-0"><i class="fa-solid fa-envelope text-danger me-2"></i> Contact Inquiries</h5>
                    <span class="small text-muted">Message volume</span>
                </div>
                <a href="<?= ADMIN_URL ?>/reports/export.php?type=contacts" class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                </a>
            </div>

            <div class="p-3 bg-light rounded-3 border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-secondary">Total Inquiries Received:</span>
                    <strong class="fs-5 text-dark"><?= $msgTotal ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary">Awaiting Follow-up:</span>
                    <span class="badge bg-danger"><?= $msgUnread ?> Unread</span>
                </div>
            </div>

            <a href="<?= ADMIN_URL ?>/contacts/index.php" class="btn btn-outline-secondary w-100 btn-sm">
                Open Contact Inbox &rarr;
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
