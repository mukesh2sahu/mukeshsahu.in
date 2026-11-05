<?php
/**
 * Course Detail Landing Page (Login Required)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth-check.php';

require_login();

$courseId = (int)($_GET['id'] ?? 0);
$db = get_db();
$userId = current_user_id();

$stmt = $db->prepare("
    SELECT c.*, cat.name AS category_name, cat.slug AS category_slug, cat.icon AS category_icon
    FROM courses c
    JOIN course_categories cat ON c.category_id = cat.id
    WHERE c.id = :id AND c.status = 'published'
    LIMIT 1
");
$stmt->execute([':id' => $courseId]);
$course = $stmt->fetch();

if (!$course) {
    set_flash('danger', 'The requested course does not exist or has been unpublished.');
    header('Location: ' . APP_URL . '/courses.php');
    exit;
}

$pageTitle = $course['title'];
$metaDesc = truncate_text($course['description'], 160);

// Check if user is enrolled
$enrollStmt = $db->prepare("SELECT * FROM course_enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
$enrollStmt->execute([':uid' => $userId, ':cid' => $courseId]);
$enrollment = $enrollStmt->fetch();

// Fetch modules and lectures
$modStmt = $db->prepare("
    SELECT * FROM course_modules 
    WHERE course_id = :cid 
    ORDER BY order_index ASC
");
$modStmt->execute([':cid' => $courseId]);
$modules = $modStmt->fetchAll();

// First lecture
$firstLecStmt = $db->prepare("SELECT id FROM course_lectures WHERE course_id = :cid ORDER BY order_index ASC LIMIT 1");
$firstLecStmt->execute([':cid' => $courseId]);
$firstLectureId = $firstLecStmt->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/courses.php">Courses</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/courses.php?category=<?= e($course['category_slug']) ?>"><?= e($course['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($course['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Course Content -->
        <div class="col-lg-8">
            <div class="custom-card p-4 p-md-5 mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="card-tag tag-blue"><?= e($course['category_name']) ?></span>
                    <span class="badge bg-light text-dark border"><?= e($course['skill_level']) ?></span>
                    <span class="badge bg-danger-subtle text-danger"><i class="fa-brands fa-youtube me-1"></i> Video Integrated</span>
                </div>

                <h1 class="fs-2 fw-bold text-dark mb-3"><?= e($course['title']) ?></h1>

                <div class="d-flex flex-wrap gap-4 text-muted small mb-4 pb-3 border-bottom">
                    <div><i class="fa-solid fa-chalkboard-user text-primary me-1"></i> Instructor: <strong><?= e($course['instructor_name']) ?></strong></div>
                    <div><i class="fa-regular fa-clock text-primary me-1"></i> Duration: <strong><?= e($course['duration']) ?></strong></div>
                    <div><i class="fa-solid fa-layer-group text-primary me-1"></i> Lectures: <strong><?= (int)$course['total_lectures'] ?></strong></div>
                    <div><i class="fa-solid fa-language text-primary me-1"></i> <strong><?= e($course['language']) ?></strong></div>
                </div>

                <h5 class="fs-5 fw-bold text-dark mb-3">About This Course</h5>
                <div class="text-secondary mb-4" style="line-height: 1.8;">
                    <?= nl2br(e($course['description'])) ?>
                </div>

                <!-- Course Syllabus Breakdown -->
                <h5 class="fs-5 fw-bold text-dark mb-3">Course Curriculum & Syllabus</h5>
                <div class="accordion mb-4" id="courseAccordion">
                    <?php if (!empty($modules)): ?>
                        <?php foreach ($modules as $idx => $mod): 
                            // Fetch lectures for this module
                            $lecs = $db->prepare("SELECT * FROM course_lectures WHERE module_id = :mid ORDER BY order_index ASC");
                            $lecs->execute([':mid' => $mod['id']]);
                            $moduleLectures = $lecs->fetchAll();
                        ?>
                            <div class="accordion-item border mb-2 rounded-3 overflow-hidden">
                                <h2 class="accordion-header" id="heading<?= $mod['id'] ?>">
                                    <button class="accordion-button <?= $idx === 0 ? '' : 'collapsed' ?> fw-bold bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $mod['id'] ?>" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                                        <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                            <span><i class="fa-solid fa-folder-closed me-2 text-primary"></i> <?= e($mod['title']) ?></span>
                                            <span class="badge bg-white text-secondary border small"><?= count($moduleLectures) ?> Lectures</span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?= $mod['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#courseAccordion">
                                    <div class="accordion-body p-0">
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($moduleLectures as $lec): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fa-regular fa-circle-play text-muted"></i>
                                                        <span class="text-dark fw-semibold"><?= e($lec['title']) ?></span>
                                                        <?php if ($lec['is_free_preview']): ?>
                                                            <span class="badge bg-success-subtle text-success small ms-2">Free Preview</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?= (int)$lec['duration_minutes'] ?> mins</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small">Curriculum details are being finalized.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions Card -->
        <div class="col-lg-4">
            <div class="custom-card p-4 sticky-top" style="top: 100px;">
                <div class="course-thumb-wrapper rounded-3 mb-3">
                    <?php 
                        $thumbUrl = !empty($course['thumbnail']) && file_exists(APP_ROOT . '/assets/uploads/thumbnails/' . $course['thumbnail'])
                            ? ASSETS_URL . '/uploads/thumbnails/' . e($course['thumbnail'])
                            : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&auto=format&fit=crop&q=80';
                    ?>
                    <img src="<?= $thumbUrl ?>" alt="<?= e($course['title']) ?>" class="course-thumb-img">
                </div>

                <?php if ($enrollment): 
                    $perc = (float)$enrollment['progress_percentage'];
                    $targetLec = $enrollment['last_lecture_id'] ?: $firstLectureId;
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="fw-semibold text-secondary">Your Course Progress</span>
                            <span class="fw-bold text-primary"><?= round($perc) ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $perc ?>%;"></div>
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>/watch.php?course_id=<?= $course['id'] ?><?= $targetLec ? '&lecture_id=' . $targetLec : '' ?>" class="btn btn-primary-custom w-100 py-2 justify-content-center mb-3">
                        <i class="fa-solid fa-play me-2"></i> <?= $perc > 0 ? 'Resume Watching' : 'Start Course Now' ?>
                    </a>
                <?php else: ?>
                    <div class="text-center mb-3">
                        <span class="badge bg-success-subtle text-success fs-6 px-3 py-1 mb-2">100% Free for Members</span>
                        <div class="text-muted small">Full video access included with your registered account.</div>
                    </div>
                    <a href="<?= APP_URL ?>/modules/courses/enroll.php?id=<?= $course['id'] ?>" class="btn btn-primary-custom w-100 py-2 justify-content-center mb-3">
                        <i class="fa-solid fa-graduation-cap me-2"></i> Enroll in Course
                    </a>
                <?php endif; ?>

                <ul class="list-unstyled small text-secondary mb-0 pt-2 border-top">
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Full HD YouTube video streaming</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Real-time lecture progress tracking</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Lifetime access from user dashboard</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i> Practical real-world code assignments</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
