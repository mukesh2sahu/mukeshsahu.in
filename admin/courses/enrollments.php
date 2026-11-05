<?php
/**
 * Admin Course Enrollments Report & Tracking
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Course Enrollments';
$pageHeading = 'Student Course Enrollments & Progress Tracking';
require_once __DIR__ . '/../includes/header.php';

$courseId = (int)($_GET['course_id'] ?? 0);
$db = get_db();

$where = "1=1";
$params = [];
$courseTitle = 'All Courses';

if ($courseId > 0) {
    $where .= " AND e.course_id = :cid";
    $params[':cid'] = $courseId;

    $cStmt = $db->prepare("SELECT title FROM courses WHERE id = :id LIMIT 1");
    $cStmt->execute([':id' => $courseId]);
    $courseTitle = $cStmt->fetchColumn() ?: 'Selected Course';
}

$stmt = $db->prepare("
    SELECT e.*, u.name as user_name, u.email as user_email, u.mobile as user_mobile, c.title as course_title
    FROM course_enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE $where
    ORDER BY e.last_accessed_at DESC
");
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

$allCoursesList = $db->query("SELECT id, title FROM courses ORDER BY title ASC")->fetchAll();
?>

<div class="custom-card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-1">Enrollment List: <?= e($courseTitle) ?></h4>
            <p class="text-muted small mb-0">Total <?= count($enrollments) ?> students registered</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex align-items-center gap-1">
                <label for="courseFilterSelect" class="small text-muted fw-semibold me-1">Filter:</label>
                <select id="courseFilterSelect" class="form-select form-select-sm" style="min-width: 220px;" onchange="window.location.href='<?= ADMIN_URL ?>/courses/enrollments.php' + (this.value ? '?course_id=' + this.value : '')">
                    <option value="">All Courses</option>
                    <?php foreach ($allCoursesList as $cItem): ?>
                        <option value="<?= $cItem['id'] ?>" <?= $courseId === (int)$cItem['id'] ? 'selected' : '' ?>>
                            <?= e($cItem['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="<?= ADMIN_URL ?>/reports/export.php?type=enrollments" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </a>
            <a href="<?= ADMIN_URL ?>/courses/index.php" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Courses
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead class="table-light">
                <tr>
                    <th>Student Name</th>
                    <th>Course Title</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Enrolled On</th>
                    <th>Last Active</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrollments as $e): 
                    $perc = (float)$e['progress_percentage'];
                    $isDone = $perc >= 100.0;
                ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= e($e['user_name']) ?></div>
                            <div class="small text-muted"><?= e($e['user_email']) ?> &bull; <?= e($e['user_mobile']) ?></div>
                        </td>
                        <td>
                            <span class="fw-semibold text-secondary small"><?= e($e['course_title']) ?></span>
                        </td>
                        <td style="width: 180px;">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="fw-bold text-dark"><?= round($perc) ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar <?= $isDone ? 'bg-success' : 'bg-primary' ?>" style="width: <?= $perc ?>%;"></div>
                            </div>
                        </td>
                        <td>
                            <?php if ($isDone): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Learning</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="small text-muted"><?= format_date($e['enrolled_at']) ?></span>
                        </td>
                        <td>
                            <span class="small text-muted"><?= time_ago($e['last_accessed_at']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
