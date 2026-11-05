<?php
/**
 * User My Enrolled Courses
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'My Enrolled Courses';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_login();

$userId = current_user_id();
$db = get_db();

// Fetch all enrolled courses
$stmt = $db->prepare("
    SELECT e.*, c.title, c.slug, c.thumbnail, c.instructor_name, c.duration, c.total_lectures, c.skill_level, cat.name AS category_name,
           (SELECT cl.id FROM course_lectures cl WHERE cl.course_id = c.id ORDER BY cl.order_index ASC LIMIT 1) AS first_lecture_id
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN course_categories cat ON c.category_id = cat.id
    WHERE e.user_id = :uid
    ORDER BY e.last_accessed_at DESC
");
$stmt->execute([':uid' => $userId]);
$myCourses = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-3 col-xxl-2">
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Enrolled Courses Content -->
        <div class="col-lg-9 col-xl-9 col-xxl-10">
            <div class="custom-card card-static p-4 p-md-5 w-100">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-3 border-bottom gap-3">
                    <div>
                        <h3 class="fs-4 fw-bold mb-1">My Enrolled Courses</h3>
                        <p class="text-muted small mb-0">Track your video progress and continue watching lectures</p>
                    </div>
                    <a href="<?= APP_URL ?>/courses.php" class="btn btn-outline-custom btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> Explore More Courses
                    </a>
                </div>

                <?php if (!empty($myCourses)): ?>
                    <div class="row g-4">
                        <?php foreach ($myCourses as $course): 
                            $targetLectureId = $course['last_lecture_id'] ?: $course['first_lecture_id'];
                            $perc = (float)$course['progress_percentage'];
                            $isComplete = $perc >= 100.0;
                        ?>
                            <div class="col-md-6">
                                <div class="custom-card h-100">
                                    <div class="course-thumb-wrapper">
                                        <?php 
                                            $thumbUrl = !empty($course['thumbnail']) && file_exists(APP_ROOT . '/assets/uploads/thumbnails/' . $course['thumbnail'])
                                                ? ASSETS_URL . '/uploads/thumbnails/' . e($course['thumbnail'])
                                                : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&auto=format&fit=crop&q=80';
                                        ?>
                                        <img src="<?= $thumbUrl ?>" alt="<?= e($course['title']) ?>" class="course-thumb-img">
                                        <span class="course-duration-badge">
                                            <i class="fa-regular fa-clock me-1"></i> <?= e($course['duration']) ?>
                                        </span>
                                    </div>

                                    <div class="p-3 d-flex flex-column flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="card-tag tag-blue"><?= e($course['category_name']) ?></span>
                                            <?php if ($isComplete): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Completed
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border"><?= e($course['skill_level']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <h5 class="fs-6 fw-bold mb-2 text-dark">
                                            <?= e($course['title']) ?>
                                        </h5>

                                        <div class="small text-muted mb-3">
                                            Instructor: <strong><?= e($course['instructor_name']) ?></strong> &bull; <?= (int)$course['total_lectures'] ?> Lectures
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="mb-3 mt-auto">
                                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                                <span class="text-secondary fw-semibold">Course Progress</span>
                                                <span class="fw-bold <?= $isComplete ? 'text-success' : 'text-primary' ?>"><?= round($perc) ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 7px;">
                                                <div class="progress-bar <?= $isComplete ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $perc ?>%;" aria-valuenow="<?= $perc ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                            <span class="small text-muted">
                                                Last viewed: <?= time_ago($course['last_accessed_at']) ?>
                                            </span>
                                            <a href="<?= APP_URL ?>/watch.php?course_id=<?= $course['course_id'] ?><?= $targetLectureId ? '&lecture_id=' . $targetLectureId : '' ?>" class="btn btn-sm btn-primary-custom">
                                                <?= $perc > 0 ? 'Continue' : 'Start' ?> <i class="fa-solid fa-play ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="stat-icon-wrapper bg-light text-muted mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="fa-solid fa-graduation-cap fs-2"></i>
                        </div>
                        <h4 class="fs-5 fw-bold text-dark mb-2">No Enrolled Courses Yet</h4>
                        <p class="text-muted small mb-4" style="max-width: 450px; margin: 0 auto;">
                            Browse our course library to enroll in Core PHP, Python, Modern React, and Autonomous AI Agent masterclasses.
                        </p>
                        <a href="<?= APP_URL ?>/courses.php" class="btn btn-primary-custom">
                            <i class="fa-solid fa-compass me-2"></i> Browse Courses Catalog
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
