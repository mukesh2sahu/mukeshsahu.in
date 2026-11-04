<?php
/**
 * Course Video Player & Watch Interface (YouTube Integration)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth-check.php';

require_login();

$courseId = (int)($_GET['course_id'] ?? 0);
$lectureId = (int)($_GET['lecture_id'] ?? 0);
$userId = current_user_id();
$db = get_db();

if ($courseId <= 0) {
    set_flash('danger', 'Please choose a valid course.');
    header('Location: ' . APP_URL . '/courses.php');
    exit;
}

// 1. Fetch Course
$courseStmt = $db->prepare("
    SELECT c.*, cat.name AS category_name 
    FROM courses c 
    JOIN course_categories cat ON c.category_id = cat.id 
    WHERE c.id = :id AND c.status = 'published' 
    LIMIT 1
");
$courseStmt->execute([':id' => $courseId]);
$course = $courseStmt->fetch();

if (!$course) {
    set_flash('danger', 'Course not found.');
    header('Location: ' . APP_URL . '/courses.php');
    exit;
}

// 2. Ensure User is Enrolled (Auto-enroll if needed)
$enrollStmt = $db->prepare("SELECT * FROM course_enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
$enrollStmt->execute([':uid' => $userId, ':cid' => $courseId]);
$enrollment = $enrollStmt->fetch();

if (!$enrollment) {
    $ins = $db->prepare("INSERT INTO course_enrollments (user_id, course_id, progress_percentage, enrolled_at) VALUES (:uid, :cid, 0, NOW())");
    $ins->execute([':uid' => $userId, ':cid' => $courseId]);
    $progressPercentage = 0.0;
} else {
    $progressPercentage = (float)$enrollment['progress_percentage'];
}

// 3. Fetch All Modules and Lectures
$modulesStmt = $db->prepare("SELECT * FROM course_modules WHERE course_id = :cid ORDER BY order_index ASC");
$modulesStmt->execute([':cid' => $courseId]);
$modules = $modulesStmt->fetchAll();

$allLecturesStmt = $db->prepare("SELECT * FROM course_lectures WHERE course_id = :cid ORDER BY order_index ASC");
$allLecturesStmt->execute([':cid' => $courseId]);
$allLectures = $allLecturesStmt->fetchAll();

if (empty($allLectures)) {
    set_flash('warning', 'No lectures have been added to this course yet.');
    header('Location: ' . APP_URL . '/course-detail.php?id=' . $courseId);
    exit;
}

// 4. Select current lecture (or default to specified or first or last accessed)
$currentLecture = null;
if ($lectureId > 0) {
    foreach ($allLectures as $lec) {
        if ((int)$lec['id'] === $lectureId) {
            $currentLecture = $lec;
            break;
        }
    }
}

if (!$currentLecture) {
    $currentLecture = $allLectures[0];
    $lectureId = (int)$currentLecture['id'];
}

// Update last accessed lecture in enrollment
$updLast = $db->prepare("UPDATE course_enrollments SET last_lecture_id = :lid, last_accessed_at = NOW() WHERE user_id = :uid AND course_id = :cid");
$updLast->execute([':lid' => $lectureId, ':uid' => $userId, ':cid' => $courseId]);

// 5. Determine Previous and Next Lectures
$prevLecture = null;
$nextLecture = null;
$lecCount = count($allLectures);
for ($i = 0; $i < $lecCount; $i++) {
    if ((int)$allLectures[$i]['id'] === $lectureId) {
        if ($i > 0) $prevLecture = $allLectures[$i - 1];
        if ($i < $lecCount - 1) $nextLecture = $allLectures[$i + 1];
        break;
    }
}

// 6. Fetch Completed Lecture IDs for user
$progStmt = $db->prepare("SELECT lecture_id FROM lecture_progress WHERE user_id = :uid AND course_id = :cid AND is_completed = 1");
$progStmt->execute([':uid' => $userId, ':cid' => $courseId]);
$completedLectureIds = $progStmt->fetchAll(PDO::FETCH_COLUMN);

$isCurrentCompleted = in_array($lectureId, $completedLectureIds);

// Extract clean YouTube ID
$youtubeId = extract_youtube_id($currentLecture['youtube_video_id']);

$pageTitle = $currentLecture['title'] . ' | ' . $course['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4 px-lg-5">
    <!-- Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/courses.php">Courses</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/course-detail.php?id=<?= $course['id'] ?>"><?= e($course['title']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($currentLecture['title']) ?></li>
            </ol>
        </nav>
        <a href="<?= APP_URL ?>/user/my-courses.php" class="small fw-semibold text-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to My Courses
        </a>
    </div>

    <div class="row g-4">
        <!-- Main Video Player Section -->
        <div class="col-lg-8">
            <!-- 16:9 YouTube Video Embed -->
            <div class="video-player-container mb-3 shadow">
                <iframe 
                    id="youtube-player"
                    src="https://www.youtube.com/embed/<?= e($youtubeId) ?>?enablejsapi=1&rel=0&modestbranding=1" 
                    title="<?= e($currentLecture['title']) ?>" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                </iframe>
            </div>

            <!-- Lecture Actions & Details -->
            <div class="custom-card p-4 mb-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3 pb-3 border-bottom">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="card-tag tag-blue"><?= e($course['category_name']) ?></span>
                            <span class="small text-muted"><i class="fa-regular fa-clock me-1"></i> <?= (int)$currentLecture['duration_minutes'] ?> mins</span>
                        </div>
                        <h2 class="fs-4 fw-bold text-dark mb-0"><?= e($currentLecture['title']) ?></h2>
                    </div>

                    <!-- Mark as Completed Button -->
                    <button class="btn <?= $isCurrentCompleted ? 'btn-success' : 'btn-outline-success' ?> btn-toggle-progress flex-shrink-0" 
                            data-course-id="<?= $courseId ?>" 
                            data-lecture-id="<?= $lectureId ?>" 
                            data-csrf="<?= csrf_token() ?>">
                        <i class="fa-solid <?= $isCurrentCompleted ? 'fa-check-double' : 'fa-circle-check' ?> me-2"></i>
                        <span class="btn-label"><?= $isCurrentCompleted ? 'Completed' : 'Mark as Completed' ?></span>
                    </button>
                </div>

                <!-- Navigation Controls -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <?php if ($prevLecture): ?>
                        <a href="<?= APP_URL ?>/watch.php?course_id=<?= $courseId ?>&lecture_id=<?= $prevLecture['id'] ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-chevron-left me-1"></i> Previous Lecture
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if ($nextLecture): ?>
                        <a href="<?= APP_URL ?>/watch.php?course_id=<?= $courseId ?>&lecture_id=<?= $nextLecture['id'] ?>" class="btn btn-primary-custom btn-sm">
                            Next Lecture <i class="fa-solid fa-chevron-right ms-1"></i>
                        </a>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success p-2">Course Completed!</span>
                    <?php endif; ?>
                </div>

                <!-- Lecture Description -->
                <h5 class="fs-6 fw-bold text-dark mb-2">Lecture Notes & Overview</h5>
                <div class="text-secondary small" style="line-height: 1.8;">
                    <?= !empty($currentLecture['description']) ? nl2br(e($currentLecture['description'])) : 'No additional lecture notes provided. Follow along with the video code demonstrations.' ?>
                </div>
            </div>
        </div>

        <!-- Sidebar / Course Playlist & Progress -->
        <div class="col-lg-4">
            <div class="custom-card p-4">
                <!-- Progress Header -->
                <div class="mb-4 pb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fs-6 fw-bold text-dark mb-0">Course Progress</h5>
                        <span class="badge bg-primary fs-6" id="course-progress-text"><?= round($progressPercentage) ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div id="course-progress-bar" class="progress-bar bg-success" role="progressbar" style="width: <?= $progressPercentage ?>%;" aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Course Modules & Lectures Playlist -->
                <h6 class="text-muted text-uppercase fw-bold small mb-3">Course Lectures</h6>
                <div class="accordion" id="watchAccordion">
                    <?php 
                    $currModuleIndex = 1;
                    foreach ($modules as $mIdx => $mod): 
                        // Lectures in this module
                        $modLecs = array_filter($allLectures, function ($l) use ($mod) {
                            return (int)$l['module_id'] === (int)$mod['id'];
                        });
                        $isOpen = false;
                        foreach ($modLecs as $ml) {
                            if ((int)$ml['id'] === $lectureId) {
                                $isOpen = true;
                                break;
                            }
                        }
                    ?>
                        <div class="accordion-item border mb-2 rounded-3 overflow-hidden">
                            <h2 class="accordion-header" id="watchHeading<?= $mod['id'] ?>">
                                <button class="accordion-button <?= $isOpen ? '' : 'collapsed' ?> fw-semibold py-2 px-3 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#watchCollapse<?= $mod['id'] ?>" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                                    <span class="small text-truncate"><?= e($mod['title']) ?></span>
                                </button>
                            </h2>
                            <div id="watchCollapse<?= $mod['id'] ?>" class="accordion-collapse collapse <?= $isOpen ? 'show' : '' ?>" data-bs-parent="#watchAccordion">
                                <div class="accordion-body p-0">
                                    <div class="list-group list-group-flush lecture-list-group">
                                        <?php foreach ($modLecs as $lec): 
                                            $isThisLecActive = (int)$lec['id'] === $lectureId;
                                            $isThisLecDone = in_array((int)$lec['id'], $completedLectureIds);
                                        ?>
                                            <a href="<?= APP_URL ?>/watch.php?course_id=<?= $courseId ?>&lecture_id=<?= $lec['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 <?= $isThisLecActive ? 'active-lecture' : '' ?>">
                                                <div class="d-flex align-items-center text-truncate pe-2">
                                                    <i id="lecture-icon-<?= $lec['id'] ?>" class="<?= $isThisLecDone ? 'fa-solid fa-circle-check text-success' : ($isThisLecActive ? 'fa-solid fa-play text-primary' : 'fa-regular fa-circle-play text-muted') ?> me-2"></i>
                                                    <span class="small text-truncate"><?= e($lec['title']) ?></span>
                                                </div>
                                                <span class="badge bg-light text-secondary border small flex-shrink-0"><?= (int)$lec['duration_minutes'] ?>m</span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Playlist link if present -->
                <?php if (!empty($course['playlist_url'])): ?>
                    <div class="mt-4 pt-3 border-top text-center">
                        <a href="<?= e($course['playlist_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fa-brands fa-youtube me-1"></i> Open YouTube Playlist
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
