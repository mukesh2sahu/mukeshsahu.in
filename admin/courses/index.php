<?php
/**
 * Admin Course Management - Index
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage Courses';
$pageHeading = 'Course & Video Curriculum Management';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Delete Course
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $courseId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $courseId > 0) {
        $del = $db->prepare("DELETE FROM courses WHERE id = :id");
        $del->execute([':id' => $courseId]);
        log_activity('Delete Course', 'courses', 'Deleted course #' . $courseId);
        set_flash('success', 'Course and its curriculum modules deleted successfully.');
    } else {
        set_flash('danger', 'Invalid security token.');
    }
    header('Location: ' . ADMIN_URL . '/courses/index.php');
    exit;
}

// Fetch all courses with lectures count and enrollment count
$stmt = $db->query("
    SELECT c.*, cat.name AS category_name,
           (SELECT COUNT(*) FROM course_lectures cl WHERE cl.course_id = c.id) as actual_lectures_count,
           (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) as enroll_count
    FROM courses c
    JOIN course_categories cat ON c.category_id = cat.id
    ORDER BY c.id DESC
");
$courses = $stmt->fetchAll();
?>

<div class="custom-card p-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-1">Courses & Video Modules</h4>
            <p class="text-muted small mb-0">Total <?= count($courses) ?> online courses managed</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= ADMIN_URL ?>/courses/enrollments.php" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-user-graduate me-1"></i> View All Enrollments
            </a>
            <a href="<?= ADMIN_URL ?>/courses/add.php" class="btn btn-primary-custom btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Add New Course
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#ID</th>
                    <th>Thumbnail & Course Title</th>
                    <th>Category</th>
                    <th>Level</th>
                    <th>Duration</th>
                    <th>Lectures</th>
                    <th>Enrolled</th>
                    <th>Status</th>
                    <th class="text-end">Curriculum & Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): 
                    $thumbUrl = !empty($c['thumbnail']) && file_exists(APP_ROOT . '/assets/uploads/thumbnails/' . $c['thumbnail'])
                        ? ASSETS_URL . '/uploads/thumbnails/' . e($c['thumbnail'])
                        : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=100&auto=format&fit=crop&q=80';
                ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $c['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= $thumbUrl ?>" alt="<?= e($c['title']) ?>" class="rounded-2 border" style="width: 60px; height: 38px; object-fit: cover;">
                                <div>
                                    <div class="fw-bold text-dark">
                                        <a href="<?= APP_URL ?>/course-detail.php?id=<?= $c['id'] ?>" target="_blank" class="text-dark hover-primary">
                                            <?= e($c['title']) ?>
                                        </a>
                                    </div>
                                    <span class="small text-muted">By: <?= e($c['instructor_name']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border small"><?= e($c['category_name']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border small"><?= e($c['skill_level']) ?></span>
                        </td>
                        <td>
                            <span class="small text-secondary"><?= e($c['duration']) ?></span>
                        </td>
                        <td>
                            <a href="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $c['id'] ?>" class="badge bg-info-subtle text-info border text-decoration-none">
                                <i class="fa-solid fa-play me-1"></i> <?= (int)$c['actual_lectures_count'] ?> Lectures
                            </a>
                        </td>
                        <td>
                            <a href="<?= ADMIN_URL ?>/courses/enrollments.php?course_id=<?= $c['id'] ?>" class="badge bg-success-subtle text-success border text-decoration-none">
                                <i class="fa-solid fa-users me-1"></i> <?= (int)$c['enroll_count'] ?> Students
                            </a>
                        </td>
                        <td>
                            <?php if ($c['status'] === 'published'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                            <?php elseif ($c['status'] === 'draft'): ?>
                                <span class="badge bg-secondary-subtle text-secondary border">Draft</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border">Archived</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= ADMIN_URL ?>/courses/enrollments.php?course_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info" title="View Students Enrolled">
                                    <i class="fa-solid fa-user-graduate"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Manage Modules & YouTube Lectures">
                                    <i class="fa-solid fa-list-check"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/courses/edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Course Details">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/courses/index.php?action=delete&id=<?= $c['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" title="Delete Course" onclick="return confirm('Delete course and all associated lectures?');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
