<?php
/**
 * Admin Course Modules & YouTube Lectures Management
 * Manage Video Lectures Console - Compact, Space-Optimized Interface
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage Video Lectures';
$pageHeading = 'Curriculum & Video Integration';
require_once __DIR__ . '/../includes/header.php';

$courseId = (int)($_GET['course_id'] ?? 0);
$db = get_db();
$errors = [];

// Fetch course details with category
$courseStmt = $db->prepare("
    SELECT c.*, cat.name AS category_name, cat.icon AS category_icon
    FROM courses c
    LEFT JOIN course_categories cat ON c.category_id = cat.id
    WHERE c.id = :id 
    LIMIT 1
");
$courseStmt->execute([':id' => $courseId]);
$course = $courseStmt->fetch();

if (!$course) {
    set_flash('danger', 'Course not found.');
    header('Location: ' . ADMIN_URL . '/courses/index.php');
    exit;
}

// ---------------------------------------------------------
// POST HANDLERS (With Whitespace & Space Sanitization)
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $errors[] = 'Security verification failed (Invalid CSRF token). Please refresh and try again.';
    } else {
        // 1. Add New Module
        if ($action === 'add_module') {
            $modTitle = trim(preg_replace('/\s+/', ' ', (string)($_POST['module_title'] ?? '')));
            $modDesc = trim((string)($_POST['module_description'] ?? ''));
            $orderIdx = (int)($_POST['order_index'] ?? 1);

            if (empty($modTitle)) {
                $errors[] = 'Module title is required.';
            } else {
                $ins = $db->prepare("
                    INSERT INTO course_modules (course_id, title, description, order_index, created_at) 
                    VALUES (:cid, :title, :desc, :idx, NOW())
                ");
                $ins->execute([
                    ':cid'   => $courseId,
                    ':title' => $modTitle,
                    ':desc'  => $modDesc,
                    ':idx'   => $orderIdx
                ]);
                log_activity('Add Course Module', 'courses', "Added module '$modTitle' to course #$courseId");
                set_flash('success', "Module \"$modTitle\" created successfully.");
                header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
                exit;
            }
        }

        // 2. Edit Existing Module
        if ($action === 'edit_module') {
            $moduleId = (int)($_POST['module_id'] ?? 0);
            $modTitle = trim(preg_replace('/\s+/', ' ', (string)($_POST['module_title'] ?? '')));
            $modDesc = trim((string)($_POST['module_description'] ?? ''));
            $orderIdx = (int)($_POST['order_index'] ?? 1);

            if ($moduleId <= 0) {
                $errors[] = 'Invalid module ID.';
            } elseif (empty($modTitle)) {
                $errors[] = 'Module title cannot be empty.';
            } else {
                $upd = $db->prepare("
                    UPDATE course_modules 
                    SET title = :title, description = :desc, order_index = :idx 
                    WHERE id = :id AND course_id = :cid
                ");
                $upd->execute([
                    ':title' => $modTitle,
                    ':desc'  => $modDesc,
                    ':idx'   => $orderIdx,
                    ':id'    => $moduleId,
                    ':cid'   => $courseId
                ]);
                log_activity('Edit Course Module', 'courses', "Updated module #$moduleId in course #$courseId");
                set_flash('success', "Module \"$modTitle\" updated successfully.");
                header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
                exit;
            }
        }

        // 3. Add Video Lecture (Removes spaces from URL/ID)
        if ($action === 'add_lecture') {
            $moduleId = (int)($_POST['module_id'] ?? 0);
            $lecTitle = trim(preg_replace('/\s+/', ' ', (string)($_POST['lecture_title'] ?? '')));
            // Completely remove any internal/external spaces from video input
            $videoInput = preg_replace('/\s+/', '', (string)($_POST['youtube_video_id'] ?? ''));
            $duration = max(1, (int)($_POST['duration_minutes'] ?? 10));
            $orderIdx = max(1, (int)($_POST['order_index'] ?? 1));
            $isFree = isset($_POST['is_free_preview']) ? 1 : 0;
            $lecDesc = trim((string)($_POST['lecture_description'] ?? ''));

            $cleanVideoId = extract_youtube_id($videoInput);

            if (empty($lecTitle)) {
                $errors[] = 'Lecture title is required.';
            }
            if (empty($cleanVideoId)) {
                $errors[] = 'Valid YouTube Video ID or URL is required.';
            }
            if ($moduleId <= 0) {
                $errors[] = 'Please select a valid module.';
            }

            if (empty($errors)) {
                $insLec = $db->prepare("
                    INSERT INTO course_lectures (course_id, module_id, title, description, youtube_video_id, duration_minutes, order_index, is_free_preview, created_at)
                    VALUES (:cid, :mid, :title, :desc, :vid, :duration, :idx, :free, NOW())
                ");
                $insLec->execute([
                    ':cid'      => $courseId,
                    ':mid'      => $moduleId,
                    ':title'    => $lecTitle,
                    ':desc'     => $lecDesc,
                    ':vid'      => $cleanVideoId,
                    ':duration' => $duration,
                    ':idx'      => $orderIdx,
                    ':free'     => $isFree
                ]);

                // Synchronize total_lectures count
                $syncStmt = $db->prepare("
                    UPDATE courses 
                    SET total_lectures = (SELECT COUNT(*) FROM course_lectures WHERE course_id = :cid) 
                    WHERE id = :cid2
                ");
                $syncStmt->execute([':cid' => $courseId, ':cid2' => $courseId]);

                log_activity('Add Lecture', 'courses', "Added lecture '$lecTitle' (YouTube: $cleanVideoId) to course #$courseId");
                set_flash('success', "Lecture \"$lecTitle\" added successfully.");
                header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
                exit;
            }
        }

        // 4. Edit Existing Lecture (Removes spaces from URL/ID)
        if ($action === 'edit_lecture') {
            $lecId = (int)($_POST['lecture_id'] ?? 0);
            $moduleId = (int)($_POST['module_id'] ?? 0);
            $lecTitle = trim(preg_replace('/\s+/', ' ', (string)($_POST['lecture_title'] ?? '')));
            $videoInput = preg_replace('/\s+/', '', (string)($_POST['youtube_video_id'] ?? ''));
            $duration = max(1, (int)($_POST['duration_minutes'] ?? 10));
            $orderIdx = max(1, (int)($_POST['order_index'] ?? 1));
            $isFree = isset($_POST['is_free_preview']) ? 1 : 0;
            $lecDesc = trim((string)($_POST['lecture_description'] ?? ''));

            $cleanVideoId = extract_youtube_id($videoInput);

            if ($lecId <= 0) {
                $errors[] = 'Invalid lecture ID.';
            }
            if (empty($lecTitle)) {
                $errors[] = 'Lecture title is required.';
            }
            if (empty($cleanVideoId)) {
                $errors[] = 'Valid YouTube Video ID or URL is required.';
            }
            if ($moduleId <= 0) {
                $errors[] = 'Please select a valid module.';
            }

            if (empty($errors)) {
                $updLec = $db->prepare("
                    UPDATE course_lectures 
                    SET module_id = :mid, 
                        title = :title, 
                        description = :desc, 
                        youtube_video_id = :vid, 
                        duration_minutes = :duration, 
                        order_index = :idx, 
                        is_free_preview = :free,
                        updated_at = NOW()
                    WHERE id = :id AND course_id = :cid
                ");
                $updLec->execute([
                    ':mid'      => $moduleId,
                    ':title'    => $lecTitle,
                    ':desc'     => $lecDesc,
                    ':vid'      => $cleanVideoId,
                    ':duration' => $duration,
                    ':idx'      => $orderIdx,
                    ':free'     => $isFree,
                    ':id'       => $lecId,
                    ':cid'      => $courseId
                ]);

                // Synchronize count
                $syncStmt = $db->prepare("
                    UPDATE courses 
                    SET total_lectures = (SELECT COUNT(*) FROM course_lectures WHERE course_id = :cid) 
                    WHERE id = :cid2
                ");
                $syncStmt->execute([':cid' => $courseId, ':cid2' => $courseId]);

                log_activity('Edit Lecture', 'courses', "Updated lecture #$lecId '$lecTitle' in course #$courseId");
                set_flash('success', "Lecture \"$lecTitle\" updated successfully.");
                header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
                exit;
            }
        }

        // 5. Reorder Lecture (Quick Move Up / Down)
        if ($action === 'reorder_lecture') {
            $lecId = (int)($_POST['lecture_id'] ?? 0);
            $direction = $_POST['direction'] ?? '';

            if ($lecId > 0 && in_array($direction, ['up', 'down'])) {
                $currStmt = $db->prepare("SELECT id, module_id, order_index FROM course_lectures WHERE id = :id AND course_id = :cid LIMIT 1");
                $currStmt->execute([':id' => $lecId, ':cid' => $courseId]);
                $curr = $currStmt->fetch();

                if ($curr) {
                    $modId = (int)$curr['module_id'];
                    $curOrder = (int)$curr['order_index'];

                    if ($direction === 'up') {
                        $adjStmt = $db->prepare("
                            SELECT id, order_index FROM course_lectures 
                            WHERE module_id = :mid AND course_id = :cid AND order_index < :cur 
                            ORDER BY order_index DESC LIMIT 1
                        ");
                        $adjStmt->execute([':mid' => $modId, ':cid' => $courseId, ':cur' => $curOrder]);
                        $adj = $adjStmt->fetch();
                    } else {
                        $adjStmt = $db->prepare("
                            SELECT id, order_index FROM course_lectures 
                            WHERE module_id = :mid AND course_id = :cid AND order_index > :cur 
                            ORDER BY order_index ASC LIMIT 1
                        ");
                        $adjStmt->execute([':mid' => $modId, ':cid' => $courseId, ':cur' => $curOrder]);
                        $adj = $adjStmt->fetch();
                    }

                    if ($adj) {
                        $adjId = (int)$adj['id'];
                        $adjOrder = (int)$adj['order_index'];

                        $db->beginTransaction();
                        $db->prepare("UPDATE course_lectures SET order_index = :ord WHERE id = :id")->execute([':ord' => $adjOrder, ':id' => $lecId]);
                        $db->prepare("UPDATE course_lectures SET order_index = :ord WHERE id = :id")->execute([':ord' => $curOrder, ':id' => $adjId]);
                        $db->commit();

                        set_flash('success', 'Lecture order updated.');
                    }
                }
            }
            header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
            exit;
        }
    }
}

// ---------------------------------------------------------
// GET ACTION HANDLERS (Delete)
// ---------------------------------------------------------

// Delete Lecture
if (isset($_GET['action']) && $_GET['action'] === 'delete_lecture') {
    $lecId = (int)($_GET['lecture_id'] ?? 0);
    $token = $_GET['token'] ?? '';
    if (verify_csrf_token($token) && $lecId > 0) {
        $del = $db->prepare("DELETE FROM course_lectures WHERE id = :id AND course_id = :cid");
        $del->execute([':id' => $lecId, ':cid' => $courseId]);

        $db->query("UPDATE courses SET total_lectures = (SELECT COUNT(*) FROM course_lectures WHERE course_id = $courseId) WHERE id = $courseId");

        log_activity('Delete Lecture', 'courses', "Deleted lecture #$lecId from course #$courseId");
        set_flash('success', 'Lecture removed from curriculum.');
    } else {
        set_flash('danger', 'Invalid security token or lecture ID.');
    }
    header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
    exit;
}

// Delete Module
if (isset($_GET['action']) && $_GET['action'] === 'delete_module') {
    $modId = (int)($_GET['module_id'] ?? 0);
    $token = $_GET['token'] ?? '';
    if (verify_csrf_token($token) && $modId > 0) {
        $del = $db->prepare("DELETE FROM course_modules WHERE id = :id AND course_id = :cid");
        $del->execute([':id' => $modId, ':cid' => $courseId]);

        $db->query("UPDATE courses SET total_lectures = (SELECT COUNT(*) FROM course_lectures WHERE course_id = $courseId) WHERE id = $courseId");

        log_activity('Delete Module', 'courses', "Deleted module #$modId and all associated lectures from course #$courseId");
        set_flash('success', 'Module and all its video lectures were deleted successfully.');
    } else {
        set_flash('danger', 'Invalid security token or module ID.');
    }
    header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
    exit;
}

// ---------------------------------------------------------
// FETCH CURRICULUM DATA & COMPACT METRICS
// ---------------------------------------------------------

$modulesStmt = $db->prepare("SELECT * FROM course_modules WHERE course_id = :cid ORDER BY order_index ASC, id ASC");
$modulesStmt->execute([':cid' => $courseId]);
$modules = $modulesStmt->fetchAll();

$lecturesStmt = $db->prepare("SELECT * FROM course_lectures WHERE course_id = :cid ORDER BY order_index ASC, id ASC");
$lecturesStmt->execute([':cid' => $courseId]);
$allLectures = $lecturesStmt->fetchAll();

$enrollCountStmt = $db->prepare("SELECT COUNT(*) FROM course_enrollments WHERE course_id = :cid");
$enrollCountStmt->execute([':cid' => $courseId]);
$enrollCount = (int)$enrollCountStmt->fetchColumn();

$totalDurationMins = 0;
$totalFreePreviews = 0;
foreach ($allLectures as $lec) {
    $totalDurationMins += (int)$lec['duration_minutes'];
    if (!empty($lec['is_free_preview'])) {
        $totalFreePreviews++;
    }
}
$hours = floor($totalDurationMins / 60);
$remainingMins = $totalDurationMins % 60;
$formattedDuration = $hours > 0 ? "{$hours}h {$remainingMins}m" : "{$remainingMins}m";

$lecturesByModule = [];
foreach ($allLectures as $l) {
    $lecturesByModule[$l['module_id']][] = $l;
}
?>

<!-- 1. Sleek, Space-Efficient Course Header Strip -->
<div class="custom-card p-3 mb-3" style="height: auto;">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <?php 
                $thumbUrl = !empty($course['thumbnail']) && file_exists(APP_ROOT . '/assets/uploads/thumbnails/' . $course['thumbnail'])
                    ? ASSETS_URL . '/uploads/thumbnails/' . e($course['thumbnail'])
                    : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=200&auto=format&fit=crop&q=80';
            ?>
            <img src="<?= $thumbUrl ?>" alt="<?= e($course['title']) ?>" class="rounded-2 border shadow-sm d-none d-sm-block flex-shrink-0" style="width: 60px; height: 42px; object-fit: cover;">
            
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="fs-5 fw-bold text-dark mb-0"><?= e($course['title']) ?></h1>
                    <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;"><?= e($course['category_name'] ?? 'Course') ?></span>
                    <span class="badge bg-light text-dark border" style="font-size: 0.72rem;"><?= e($course['skill_level']) ?></span>
                    <span class="badge <?= $course['status'] === 'published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> border" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-circle me-1" style="font-size: 0.45rem;"></i> <?= ucfirst($course['status']) ?>
                    </span>
                </div>
                
                <!-- Compact Inline Metrics Strip (Replaces tall bulky boxes) -->
                <div class="d-flex align-items-center gap-3 flex-wrap mt-1 text-muted" style="font-size: 0.78rem;">
                    <span><i class="fa-solid fa-user-tie text-primary me-1"></i><?= e($course['instructor_name']) ?></span>
                    <span>&bull;</span>
                    <span><i class="fa-solid fa-folder-tree text-primary me-1"></i><strong><?= count($modules) ?></strong> Modules</span>
                    <span>&bull;</span>
                    <span><i class="fa-brands fa-youtube text-danger me-1"></i><strong><?= count($allLectures) ?></strong> Lectures</span>
                    <span>&bull;</span>
                    <span><i class="fa-regular fa-clock text-info me-1"></i><strong><?= $formattedDuration ?></strong> Total</span>
                    <span>&bull;</span>
                    <span><i class="fa-solid fa-unlock text-success me-1"></i><strong><?= $totalFreePreviews ?></strong> Free Preview</span>
                    <span>&bull;</span>
                    <span><i class="fa-solid fa-user-graduate text-warning me-1"></i><a href="<?= ADMIN_URL ?>/courses/enrollments.php?course_id=<?= $course['id'] ?>" class="text-decoration-none text-muted" title="View Enrolled Students"><strong><?= $enrollCount ?></strong> Students Enrolled</a></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <a href="<?= ADMIN_URL ?>/courses/enrollments.php?course_id=<?= $course['id'] ?>" class="btn btn-outline-warning btn-sm py-1 px-2" style="font-size: 0.78rem;" title="View Course Enrollments">
                <i class="fa-solid fa-user-graduate me-1"></i> Enrollments (<?= $enrollCount ?>)
            </a>
            <a href="<?= APP_URL ?>/course-detail.php?id=<?= $course['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm py-1 px-2" style="font-size: 0.78rem;" title="Public Course Page">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Public
            </a>
            <a href="<?= APP_URL ?>/watch.php?course_id=<?= $course['id'] ?>" target="_blank" class="btn btn-outline-success btn-sm py-1 px-2" style="font-size: 0.78rem;" title="Test Student Watch Player">
                <i class="fa-solid fa-play me-1"></i> Watch
            </a>
            <a href="<?= ADMIN_URL ?>/courses/edit.php?id=<?= $course['id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2" style="font-size: 0.78rem;" title="Edit Course Details">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Info
            </a>
            <a href="<?= ADMIN_URL ?>/courses/index.php" class="btn btn-outline-secondary btn-sm py-1 px-2" style="font-size: 0.78rem;" title="Back to All Courses">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3 p-2 small shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        <?= implode(' &bull; ', array_map('e', $errors)) ?>
        <button type="button" class="btn-close p-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- 2. Main Workspace Layout -->
<div class="row g-3">
    <!-- Left Column: Curriculum Accordion (Modules & Sequenced Video Lectures) -->
    <div class="col-xl-7 col-lg-7">
        <div class="custom-card p-3" style="height: auto;">
            <!-- Compact Toolbar -->
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="fs-6 fw-bold text-dark mb-0">
                        <i class="fa-solid fa-layer-group text-primary me-1"></i> Course Curriculum
                    </h2>
                    <span class="badge bg-light text-muted border small"><?= count($allLectures) ?> videos</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" id="btnExpandAll" title="Expand All Modules" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-up-right-and-down-left-from-center me-1"></i> Expand
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" id="btnCollapseAll" title="Collapse All Modules" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-down-left-and-up-right-to-center me-1"></i> Collapse
                    </button>
                    <button type="button" class="btn btn-primary-custom btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#addModuleModal" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-plus me-1"></i> New Module
                    </button>
                </div>
            </div>

            <!-- Instant Search Box -->
            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-end-0 py-1">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 0.75rem;"></i>
                    </span>
                    <input type="text" id="lectureSearchFilter" class="form-control border-start-0 py-1" style="font-size: 0.8rem;" placeholder="Filter lectures by title or YouTube ID...">
                    <button class="btn btn-outline-secondary py-1" type="button" id="clearLectureSearch" style="display: none;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <?php if (!empty($modules)): ?>
                <div class="accordion" id="adminCurriculumAccordion">
                    <?php foreach ($modules as $mIdx => $mod): 
                        $modLecs = $lecturesByModule[$mod['id']] ?? [];
                        $modMins = array_sum(array_column($modLecs, 'duration_minutes'));
                    ?>
                        <div class="accordion-item border mb-2 rounded-2 overflow-hidden shadow-sm module-block" data-module-id="<?= $mod['id'] ?>">
                            <!-- Module Header -->
                            <div class="accordion-header bg-light d-flex align-items-center justify-content-between px-3 py-2 border-bottom" id="adminHead<?= $mod['id'] ?>">
                                <button class="accordion-button <?= $mIdx === 0 ? '' : 'collapsed' ?> p-0 bg-transparent shadow-none flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#adminCollapse<?= $mod['id'] ?>" aria-expanded="<?= $mIdx === 0 ? 'true' : 'false' ?>">
                                    <div class="d-flex align-items-center flex-wrap gap-2 text-start">
                                        <span class="badge bg-dark text-white rounded-pill px-2 py-0" style="font-size: 0.7rem;">#<?= $mod['order_index'] ?></span>
                                        <span class="fw-bold text-dark small module-title-text"><?= e($mod['title']) ?></span>
                                        <span class="badge bg-white text-secondary border" style="font-size: 0.68rem;">
                                            <i class="fa-brands fa-youtube text-danger me-1"></i><?= count($modLecs) ?> videos &bull; <?= $modMins ?>m
                                        </span>
                                    </div>
                                </button>

                                <div class="d-flex align-items-center gap-1 ms-2 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-quick-add-to-module" 
                                            data-module-id="<?= $mod['id'] ?>" 
                                            data-module-title="<?= e($mod['title']) ?>"
                                            style="font-size: 0.72rem;" title="Add video lecture to this module">
                                        <i class="fa-solid fa-plus"></i> <span class="d-none d-sm-inline">Add</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-edit-module"
                                            data-module-id="<?= $mod['id'] ?>"
                                            data-module-title="<?= e($mod['title']) ?>"
                                            data-module-order="<?= $mod['order_index'] ?>"
                                            data-module-desc="<?= e($mod['description'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editModuleModal"
                                            style="font-size: 0.72rem;" title="Edit Module">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <a href="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>&action=delete_module&module_id=<?= $mod['id'] ?>&token=<?= csrf_token() ?>" 
                                       class="btn btn-sm btn-outline-danger py-0 px-2" 
                                       onclick="return confirm('Delete module \'<?= e(addslashes($mod['title'])) ?>\' and its <?= count($modLecs) ?> video lectures?');" 
                                       style="font-size: 0.72rem;" title="Delete Module">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Module Lectures List -->
                            <div id="adminCollapse<?= $mod['id'] ?>" class="accordion-collapse collapse <?= $mIdx === 0 ? 'show' : '' ?>" data-bs-parent="#adminCurriculumAccordion">
                                <div class="accordion-body p-0">
                                    <?php if (!empty($mod['description'])): ?>
                                        <div class="px-3 py-1 bg-white text-muted small border-bottom fst-italic" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-info-circle me-1 text-primary"></i> <?= e($mod['description']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($modLecs)): ?>
                                        <div class="list-group list-group-flush lecture-items-list">
                                            <?php foreach ($modLecs as $lecIdx => $lec): ?>
                                                <div class="list-group-item py-2 px-3 lecture-row" data-lecture-id="<?= $lec['id'] ?>" data-search-content="<?= e(strtolower($lec['title'] . ' ' . $lec['youtube_video_id'] . ' ' . ($lec['description'] ?? ''))) ?>">
                                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                                        
                                                        <!-- Compact Thumbnail + Title -->
                                                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 0;">
                                                            <!-- Compact Thumbnail with Watch overlay -->
                                                            <div class="position-relative flex-shrink-0 cursor-pointer btn-preview-video" 
                                                                 data-video-id="<?= e($lec['youtube_video_id']) ?>" 
                                                                 data-lecture-title="<?= e($lec['title']) ?>"
                                                                 data-lecture-desc="<?= e($lec['description'] ?? '') ?>"
                                                                 title="Click to preview video">
                                                                <img src="https://img.youtube.com/vi/<?= e($lec['youtube_video_id']) ?>/mqdefault.jpg" 
                                                                     alt="Thumbnail" 
                                                                     class="rounded border" 
                                                                     style="width: 68px; height: 40px; object-fit: cover;"
                                                                     onerror="this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=150&auto=format&fit=crop&q=80'">
                                                                <span class="position-absolute bottom-0 end-0 bg-dark text-white px-1 rounded-start" style="font-size: 0.6rem; opacity: 0.9;">
                                                                    <?= (int)$lec['duration_minutes'] ?>m
                                                                </span>
                                                                <div class="position-absolute top-50 start-50 translate-middle text-white bg-dark bg-opacity-75 rounded-circle d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                                    <i class="fa-solid fa-play" style="font-size: 0.5rem;"></i>
                                                                </div>
                                                            </div>

                                                            <!-- Title & Meta -->
                                                            <div class="flex-grow-1 text-truncate">
                                                                <div class="d-flex align-items-center gap-1 flex-wrap text-truncate">
                                                                    <span class="badge bg-secondary-subtle text-secondary py-0 px-1" style="font-size: 0.65rem;">#<?= (int)$lec['order_index'] ?></span>
                                                                    <span class="fw-semibold text-dark small text-truncate lecture-title-text" title="<?= e($lec['title']) ?>"><?= e($lec['title']) ?></span>
                                                                    <?php if (!empty($lec['is_free_preview'])): ?>
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 0.62rem;">Free Preview</span>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <div class="text-muted d-flex align-items-center gap-2 flex-wrap" style="font-size: 0.72rem;">
                                                                    <span class="cursor-pointer text-decoration-none copy-vid-pill" data-video-id="<?= e($lec['youtube_video_id']) ?>" title="Click to copy Video ID">
                                                                        <i class="fa-brands fa-youtube text-danger me-1"></i><code><?= e($lec['youtube_video_id']) ?></code>
                                                                        <i class="fa-regular fa-copy text-muted ms-1" style="font-size: 0.65rem;"></i>
                                                                    </span>
                                                                    <span>&bull;</span>
                                                                    <span><i class="fa-regular fa-clock text-primary me-1"></i><?= (int)$lec['duration_minutes'] ?>m</span>
                                                                    <?php if (!empty($lec['description'])): ?>
                                                                        <span>&bull;</span>
                                                                        <span class="text-truncate text-secondary" style="max-width: 150px;" title="<?= e($lec['description']) ?>">
                                                                            <?= e($lec['description']) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Action Buttons -->
                                                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                            <!-- Play Preview -->
                                                            <button type="button" class="btn btn-sm btn-outline-info py-0 px-2 btn-preview-video" 
                                                                    data-video-id="<?= e($lec['youtube_video_id']) ?>" 
                                                                    data-lecture-title="<?= e($lec['title']) ?>"
                                                                    data-lecture-desc="<?= e($lec['description'] ?? '') ?>"
                                                                    style="font-size: 0.72rem;" title="Preview Video">
                                                                <i class="fa-solid fa-play"></i>
                                                            </button>

                                                            <!-- Move Up -->
                                                            <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>" class="d-inline">
                                                                <?= csrf_field(); ?>
                                                                <input type="hidden" name="action" value="reorder_lecture">
                                                                <input type="hidden" name="lecture_id" value="<?= $lec['id'] ?>">
                                                                <input type="hidden" name="direction" value="up">
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" title="Move Up" <?= $lecIdx === 0 ? 'disabled' : '' ?>>
                                                                    <i class="fa-solid fa-chevron-up"></i>
                                                                </button>
                                                            </form>

                                                            <!-- Move Down -->
                                                            <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>" class="d-inline">
                                                                <?= csrf_field(); ?>
                                                                <input type="hidden" name="action" value="reorder_lecture">
                                                                <input type="hidden" name="lecture_id" value="<?= $lec['id'] ?>">
                                                                <input type="hidden" name="direction" value="down">
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" title="Move Down" <?= $lecIdx === (count($modLecs) - 1) ? 'disabled' : '' ?>>
                                                                    <i class="fa-solid fa-chevron-down"></i>
                                                                </button>
                                                            </form>

                                                            <!-- Edit Modal Trigger -->
                                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-edit-lecture"
                                                                    data-lecture-id="<?= $lec['id'] ?>"
                                                                    data-module-id="<?= $lec['module_id'] ?>"
                                                                    data-lecture-title="<?= e($lec['title']) ?>"
                                                                    data-video-id="<?= e($lec['youtube_video_id']) ?>"
                                                                    data-duration="<?= (int)$lec['duration_minutes'] ?>"
                                                                    data-order="<?= (int)$lec['order_index'] ?>"
                                                                    data-is-free="<?= (int)$lec['is_free_preview'] ?>"
                                                                    data-description="<?= e($lec['description'] ?? '') ?>"
                                                                    data-bs-toggle="modal" data-bs-target="#editLectureModal"
                                                                    style="font-size: 0.72rem;" title="Edit Lecture">
                                                                <i class="fa-solid fa-pen"></i>
                                                            </button>

                                                            <!-- YouTube link -->
                                                            <a href="https://www.youtube.com/watch?v=<?= e($lec['youtube_video_id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" title="Open in YouTube">
                                                                <i class="fa-brands fa-youtube text-danger"></i>
                                                            </a>

                                                            <!-- Delete -->
                                                            <a href="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>&action=delete_lecture&lecture_id=<?= $lec['id'] ?>&token=<?= csrf_token() ?>" 
                                                               class="btn btn-sm btn-outline-danger py-0 px-2" 
                                                               onclick="return confirm('Delete lecture \'<?= e(addslashes($lec['title'])) ?>\'?');" 
                                                               style="font-size: 0.72rem;" title="Delete">
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </a>
                                                        </div>

                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-3 text-center text-muted small">
                                            <i class="fa-brands fa-youtube text-secondary opacity-50 me-1"></i> No video lectures in this module yet.
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1 btn-quick-add-to-module" data-module-id="<?= $mod['id'] ?>" data-module-title="<?= e($mod['title']) ?>">
                                                + Add first lecture
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4 border rounded-2 bg-light">
                    <i class="fa-solid fa-folder-tree fs-2 text-primary opacity-50 mb-2"></i>
                    <h6 class="fw-bold text-dark mb-1">No Modules Created Yet</h6>
                    <p class="text-muted small mb-2">Start by creating your first course module.</p>
                    <button type="button" class="btn btn-primary-custom btn-sm py-1 px-3" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                        <i class="fa-solid fa-plus me-1"></i> Create First Module
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Single Unified Tabbed Card (Eliminates vertical gap & empty spaces) -->
    <div class="col-xl-5 col-lg-5">
        <div class="custom-card p-3" id="addLectureCard" style="height: auto;">
            <!-- Compact Navigation Tabs -->
            <ul class="nav nav-pills nav-fill mb-2 bg-light p-1 rounded-2 border" id="curriculumTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1 px-2 small fw-bold" id="tab-add-lecture-btn" data-bs-toggle="pill" data-bs-target="#tab-add-lecture" type="button" role="tab">
                        <i class="fa-brands fa-youtube text-danger me-1"></i> Add Video Lecture
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-2 small fw-bold" id="tab-add-module-btn" data-bs-toggle="pill" data-bs-target="#tab-add-module" type="button" role="tab">
                        <i class="fa-solid fa-folder-plus text-primary me-1"></i> Add Module
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="curriculumTabsContent">
                <!-- Tab 1: Add Video Lecture -->
                <div class="tab-pane fade show active" id="tab-add-lecture" role="tabpanel">
                    <?php if (empty($modules)): ?>
                        <div class="alert alert-warning small p-2 mb-0">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Please create a module first using the "Add Module" tab.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>" id="addLectureForm" novalidate>
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="add_lecture">

                            <div class="mb-2">
                                <label for="module_id" class="form-label small fw-semibold mb-1">
                                    Module <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" id="module_id" name="module_id" required>
                                    <?php foreach ($modules as $m): ?>
                                        <option value="<?= $m['id'] ?>">#<?= $m['order_index'] ?> - <?= e($m['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label for="lecture_title" class="form-label small fw-semibold mb-1">
                                    Lecture Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="lecture_title" name="lecture_title" placeholder="e.g. Master Class: PDO Transactions" required>
                            </div>

                            <!-- YouTube URL with automatic space stripping -->
                            <div class="mb-2">
                                <label for="youtube_video_id" class="form-label small fw-semibold mb-1">
                                    YouTube URL or Video ID <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-danger"><i class="fa-brands fa-youtube"></i></span>
                                    <input type="text" class="form-control" id="youtube_video_id" name="youtube_video_id" placeholder="youtube.com/watch?v=... or 11-char ID" required>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">Spaces are automatically removed. Accepts full link or ID.</div>
                                
                                <!-- Live Detection Box -->
                                <div id="addVideoPreviewBox" class="mt-1 p-2 border rounded bg-light d-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <img id="addVideoThumbImg" src="" alt="Thumbnail" class="rounded border" style="width: 60px; height: 36px; object-fit: cover;">
                                        <div class="small flex-grow-1 text-truncate">
                                            <div class="text-success fw-bold" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-circle-check me-1"></i> ID: <span id="addVideoDetectedId" class="badge bg-dark font-monospace"></span>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.68rem;">Live verified thumbnail.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label for="duration_minutes" class="form-label small fw-semibold mb-1">Duration (Mins) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fa-regular fa-clock"></i></span>
                                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" value="25" min="1" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label for="lec_order_index" class="form-label small fw-semibold mb-1">Sequence</label>
                                    <input type="number" class="form-control form-control-sm" id="lec_order_index" name="order_index" value="<?= count($allLectures) + 1 ?>" min="1">
                                </div>
                            </div>

                            <div class="mb-2 p-2 border rounded bg-light">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_free_preview" name="is_free_preview">
                                    <label class="form-check-label small fw-semibold text-dark" for="is_free_preview">
                                        <i class="fa-solid fa-unlock text-success me-1"></i> Allow Free Preview
                                    </label>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="lecture_description" class="form-label small fw-semibold mb-1">Notes / Summary (Optional)</label>
                                <textarea class="form-control form-control-sm" id="lecture_description" name="lecture_description" rows="2" placeholder="Summary, code links, or key takeaway notes..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-1 justify-content-center btn-sm shadow-sm">
                                <i class="fa-solid fa-plus me-1"></i> Add Video Lecture
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Tab 2: Add Module -->
                <div class="tab-pane fade" id="tab-add-module" role="tabpanel">
                    <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>" novalidate>
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="add_module">

                        <div class="mb-2">
                            <label for="module_title" class="form-label small fw-semibold mb-1">
                                Module Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="module_title" name="module_title" placeholder="e.g. Module <?= count($modules) + 1 ?>: Core Concepts" required>
                        </div>

                        <div class="mb-2">
                            <label for="mod_order_index" class="form-label small fw-semibold mb-1">Sequence Index</label>
                            <input type="number" class="form-control form-control-sm" id="mod_order_index" name="order_index" value="<?= count($modules) + 1 ?>" min="1">
                        </div>

                        <div class="mb-2">
                            <label for="module_description" class="form-label small fw-semibold mb-1">Description (Optional)</label>
                            <textarea class="form-control form-control-sm" id="module_description" name="module_description" rows="3" placeholder="Brief outline of module objectives..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-outline-primary w-100 py-1 justify-content-center btn-sm shadow-sm">
                            <i class="fa-solid fa-folder-plus me-1"></i> Create Module
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- COMPACT MODALS                                            -->
<!-- ========================================================= -->

<!-- 1. Edit Lecture Modal -->
<div class="modal fade" id="editLectureModal" tabindex="-1" aria-labelledby="editLectureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title fs-6 fw-bold mb-0" id="editLectureModalLabel">
                    <i class="fa-solid fa-pen-to-square text-primary me-1"></i> Edit Video Lecture
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>" id="editLectureForm">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit_lecture">
                <input type="hidden" name="lecture_id" id="edit_lecture_id" value="">

                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label for="edit_module_id" class="form-label small fw-semibold mb-1">Module <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="edit_module_id" name="module_id" required>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?= $m['id'] ?>">#<?= $m['order_index'] ?> - <?= e($m['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label for="edit_lecture_title" class="form-label small fw-semibold mb-1">Lecture Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="edit_lecture_title" name="lecture_title" required>
                    </div>

                    <div class="mb-2">
                        <label for="edit_youtube_video_id" class="form-label small fw-semibold mb-1">YouTube URL or Video ID <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-danger"><i class="fa-brands fa-youtube"></i></span>
                            <input type="text" class="form-control" id="edit_youtube_video_id" name="youtube_video_id" required>
                        </div>
                        
                        <div id="editVideoPreviewBox" class="mt-1 p-2 border rounded bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <img id="editVideoThumbImg" src="" alt="Thumbnail" class="rounded border" style="width: 60px; height: 36px; object-fit: cover;">
                                <div class="small flex-grow-1 text-truncate">
                                    <div class="text-success fw-bold" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-circle-check me-1"></i> ID: <span id="editVideoDetectedId" class="badge bg-dark font-monospace"></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.68rem;">Live YouTube preview.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label for="edit_duration_minutes" class="form-label small fw-semibold mb-1">Duration (Mins)</label>
                            <input type="number" class="form-control form-control-sm" id="edit_duration_minutes" name="duration_minutes" min="1" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_order_index" class="form-label small fw-semibold mb-1">Sequence</label>
                            <input type="number" class="form-control form-control-sm" id="edit_order_index" name="order_index" min="1" required>
                        </div>
                    </div>

                    <div class="mb-2 p-2 border rounded bg-light">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="edit_is_free_preview" name="is_free_preview">
                            <label class="form-check-label small fw-semibold text-dark" for="edit_is_free_preview">
                                <i class="fa-solid fa-unlock text-success me-1"></i> Allow Free Preview
                            </label>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="edit_lecture_description" class="form-label small fw-semibold mb-1">Notes / Summary</label>
                        <textarea class="form-control form-control-sm" id="edit_lecture_description" name="lecture_description" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom btn-sm py-1 px-3">
                        <i class="fa-solid fa-check me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Edit Module Modal -->
<div class="modal fade" id="editModuleModal" tabindex="-1" aria-labelledby="editModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title fs-6 fw-bold mb-0" id="editModuleModalLabel">
                    <i class="fa-solid fa-folder-pen text-primary me-1"></i> Edit Module
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit_module">
                <input type="hidden" name="module_id" id="edit_modal_module_id" value="">

                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label for="edit_module_title" class="form-label small fw-semibold mb-1">Module Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="edit_module_title" name="module_title" required>
                    </div>

                    <div class="mb-2">
                        <label for="edit_module_order" class="form-label small fw-semibold mb-1">Sequence Index</label>
                        <input type="number" class="form-control form-control-sm" id="edit_module_order" name="order_index" min="1" required>
                    </div>

                    <div class="mb-0">
                        <label for="edit_module_desc" class="form-label small fw-semibold mb-1">Description (Optional)</label>
                        <textarea class="form-control form-control-sm" id="edit_module_desc" name="module_description" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom btn-sm py-1 px-3">
                        <i class="fa-solid fa-check me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Add Module Modal (Triggered from Top Toolbar) -->
<div class="modal fade" id="addModuleModal" tabindex="-1" aria-labelledby="addModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title fs-6 fw-bold mb-0" id="addModuleModalLabel">
                    <i class="fa-solid fa-folder-plus text-primary me-1"></i> Add Module
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_module">

                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label for="modal_module_title" class="form-label small fw-semibold mb-1">Module Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="modal_module_title" name="module_title" placeholder="e.g. Module <?= count($modules) + 1 ?>: Core Setup" required>
                    </div>

                    <div class="mb-2">
                        <label for="modal_module_order" class="form-label small fw-semibold mb-1">Sequence Index</label>
                        <input type="number" class="form-control form-control-sm" id="modal_module_order" name="order_index" value="<?= count($modules) + 1 ?>" min="1" required>
                    </div>

                    <div class="mb-0">
                        <label for="modal_module_desc" class="form-label small fw-semibold mb-1">Description (Optional)</label>
                        <textarea class="form-control form-control-sm" id="modal_module_desc" name="module_description" rows="2" placeholder="Brief outline..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom btn-sm py-1 px-3">
                        <i class="fa-solid fa-plus me-1"></i> Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. Video Player Preview Modal -->
<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-labelledby="videoPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg overflow-hidden bg-dark text-white">
            <div class="modal-header border-secondary py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-brands fa-youtube text-danger"></i>
                    <h5 class="modal-title fs-6 fw-bold text-white mb-0" id="videoPreviewModalTitle">Preview Lecture</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9 bg-black">
                    <iframe id="videoPreviewIframe" src="" title="YouTube video player" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
                <div class="p-2 bg-dark">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted" id="videoPreviewMeta"></div>
                        <a id="videoPreviewDirectLink" href="#" target="_blank" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open on YouTube
                        </a>
                    </div>
                    <div id="videoPreviewDesc" class="mt-1 text-light small opacity-75 fst-italic" style="font-size: 0.75rem;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- JAVASCRIPT LOGIC                                         -->
<!-- ========================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Helper to extract 11-char YouTube ID while stripping all spaces
    function parseYouTubeVideoId(input) {
        if (!input) return '';
        // Strip any spaces inside or around the input
        input = input.replace(/\s+/g, '');
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = input.match(regExp);
        if (match && match[2].length === 11) {
            return match[2];
        }
        if (/^[a-zA-Z0-9_-]{11}$/.test(input)) {
            return input;
        }
        return '';
    }

    // 2. Add Lecture Input - Live Preview & Space Stripping
    const addInput = document.getElementById('youtube_video_id');
    const addBox = document.getElementById('addVideoPreviewBox');
    const addImg = document.getElementById('addVideoThumbImg');
    const addBadge = document.getElementById('addVideoDetectedId');

    if (addInput) {
        const updateAddPreview = function() {
            // Automatically clean spaces from value
            const cleanVal = addInput.value.replace(/\s+/g, '');
            if (addInput.value !== cleanVal) {
                addInput.value = cleanVal;
            }
            const vidId = parseYouTubeVideoId(cleanVal);
            if (vidId) {
                addImg.src = `https://img.youtube.com/vi/${vidId}/mqdefault.jpg`;
                addBadge.textContent = vidId;
                addBox.classList.remove('d-none');
            } else {
                addBox.classList.add('d-none');
            }
        };
        addInput.addEventListener('input', updateAddPreview);
        addInput.addEventListener('paste', () => setTimeout(updateAddPreview, 50));
        if (addInput.value) updateAddPreview();
    }

    // 3. Edit Lecture Input - Live Preview & Space Stripping
    const editInput = document.getElementById('edit_youtube_video_id');
    const editBox = document.getElementById('editVideoPreviewBox');
    const editImg = document.getElementById('editVideoThumbImg');
    const editBadge = document.getElementById('editVideoDetectedId');

    if (editInput) {
        const updateEditPreview = function() {
            const cleanVal = editInput.value.replace(/\s+/g, '');
            if (editInput.value !== cleanVal) {
                editInput.value = cleanVal;
            }
            const vidId = parseYouTubeVideoId(cleanVal);
            if (vidId) {
                editImg.src = `https://img.youtube.com/vi/${vidId}/mqdefault.jpg`;
                addBadge.textContent = vidId;
                editBox.classList.remove('d-none');
            } else {
                editBox.classList.add('d-none');
            }
        };
        editInput.addEventListener('input', updateEditPreview);
        editInput.addEventListener('paste', () => setTimeout(updateEditPreview, 50));
    }

    // 4. Quick Add to Module Trigger
    document.querySelectorAll('.btn-quick-add-to-module').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const modId = this.getAttribute('data-module-id');
            const modSelect = document.getElementById('module_id');
            const tabBtn = document.getElementById('tab-add-lecture-btn');
            
            // Switch to Add Lecture tab if needed
            if (tabBtn) {
                bootstrap.Tab.getOrCreateInstance(tabBtn).show();
            }

            if (modSelect && modId) {
                modSelect.value = modId;
                const targetCard = document.getElementById('addLectureCard');
                if (targetCard) {
                    targetCard.scrollIntoView({ behavior: 'smooth' });
                    targetCard.classList.add('border-primary');
                    setTimeout(() => targetCard.classList.remove('border-primary'), 1000);
                }
                const titleInput = document.getElementById('lecture_title');
                if (titleInput) titleInput.focus();
            }
        });
    });

    // 5. Populate Edit Lecture Modal
    document.querySelectorAll('.btn-edit-lecture').forEach(button => {
        button.addEventListener('click', function () {
            const lecId = this.getAttribute('data-lecture-id');
            const modId = this.getAttribute('data-module-id');
            const title = this.getAttribute('data-lecture-title');
            const videoId = this.getAttribute('data-video-id');
            const duration = this.getAttribute('data-duration');
            const order = this.getAttribute('data-order');
            const isFree = this.getAttribute('data-is-free') === '1';
            const desc = this.getAttribute('data-description');

            document.getElementById('edit_lecture_id').value = lecId || '';
            document.getElementById('edit_module_id').value = modId || '';
            document.getElementById('edit_lecture_title').value = title || '';
            document.getElementById('edit_youtube_video_id').value = (videoId || '').replace(/\s+/g, '');
            document.getElementById('edit_duration_minutes').value = duration || '10';
            document.getElementById('edit_order_index').value = order || '1';
            document.getElementById('edit_is_free_preview').checked = isFree;
            document.getElementById('edit_lecture_description').value = desc || '';

            if (editInput) {
                editInput.dispatchEvent(new Event('input'));
            }
        });
    });

    // 6. Populate Edit Module Modal
    document.querySelectorAll('.btn-edit-module').forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
            const modId = this.getAttribute('data-module-id');
            const title = this.getAttribute('data-module-title');
            const order = this.getAttribute('data-module-order');
            const desc = this.getAttribute('data-module-desc');

            document.getElementById('edit_modal_module_id').value = modId || '';
            document.getElementById('edit_module_title').value = title || '';
            document.getElementById('edit_module_order').value = order || '1';
            document.getElementById('edit_module_desc').value = desc || '';
        });
    });

    // 7. Video Player Preview Modal
    const videoPreviewModal = document.getElementById('videoPreviewModal');
    const previewIframe = document.getElementById('videoPreviewIframe');
    const previewTitle = document.getElementById('videoPreviewModalTitle');
    const previewMeta = document.getElementById('videoPreviewMeta');
    const previewDirectLink = document.getElementById('videoPreviewDirectLink');
    const previewDesc = document.getElementById('videoPreviewDesc');

    document.querySelectorAll('.btn-preview-video').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const videoId = (this.getAttribute('data-video-id') || '').replace(/\s+/g, '');
            const title = this.getAttribute('data-lecture-title') || 'Video Lecture Preview';
            const desc = this.getAttribute('data-lecture-desc') || '';

            if (videoId) {
                previewTitle.textContent = title;
                previewMeta.innerHTML = `<span class="badge bg-danger me-1">YouTube</span> ID: <code>${videoId}</code>`;
                previewDirectLink.href = `https://www.youtube.com/watch?v=${videoId}`;
                previewDesc.textContent = desc ? `Notes: ${desc}` : '';

                previewIframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`;

                const modal = new bootstrap.Modal(videoPreviewModal);
                modal.show();
            }
        });
    });

    if (videoPreviewModal) {
        videoPreviewModal.addEventListener('hidden.bs.modal', function () {
            previewIframe.src = '';
        });
    }

    // 8. Instant Live Search Filter
    const searchFilter = document.getElementById('lectureSearchFilter');
    const clearSearch = document.getElementById('clearLectureSearch');
    const lectureRows = document.querySelectorAll('.lecture-row');
    const moduleBlocks = document.querySelectorAll('.module-block');

    if (searchFilter) {
        searchFilter.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            if (query.length > 0) {
                clearSearch.style.display = 'block';

                moduleBlocks.forEach(mod => {
                    let hasMatchingLecture = false;
                    const rowsInMod = mod.querySelectorAll('.lecture-row');

                    rowsInMod.forEach(row => {
                        const content = row.getAttribute('data-search-content') || '';
                        if (content.includes(query)) {
                            row.style.display = 'block';
                            hasMatchingLecture = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    const collapseEl = mod.querySelector('.accordion-collapse');
                    if (hasMatchingLecture) {
                        mod.style.display = 'block';
                        if (collapseEl && !collapseEl.classList.contains('show')) {
                            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                            bsCollapse.show();
                        }
                    } else {
                        mod.style.display = 'none';
                    }
                });
            } else {
                clearSearch.style.display = 'none';
                lectureRows.forEach(row => row.style.display = 'block');
                moduleBlocks.forEach(mod => mod.style.display = 'block');
            }
        });

        clearSearch.addEventListener('click', function () {
            searchFilter.value = '';
            searchFilter.dispatchEvent(new Event('input'));
            searchFilter.focus();
        });
    }

    // 9. Expand All / Collapse All
    const btnExpand = document.getElementById('btnExpandAll');
    const btnCollapse = document.getElementById('btnCollapseAll');

    if (btnExpand) {
        btnExpand.addEventListener('click', function () {
            document.querySelectorAll('#adminCurriculumAccordion .accordion-collapse').forEach(el => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                bsCollapse.show();
            });
        });
    }

    if (btnCollapse) {
        btnCollapse.addEventListener('click', function () {
            document.querySelectorAll('#adminCurriculumAccordion .accordion-collapse').forEach(el => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                bsCollapse.hide();
            });
        });
    }

    // 10. Copy Video ID Pill
    document.querySelectorAll('.copy-vid-pill').forEach(pill => {
        pill.addEventListener('click', function(e) {
            e.stopPropagation();
            const vidId = this.getAttribute('data-video-id');
            if (vidId && navigator.clipboard) {
                navigator.clipboard.writeText(vidId).then(() => {
                    const originalHtml = this.innerHTML;
                    this.innerHTML = `<span class="text-success"><i class="fa-solid fa-check"></i> Copied!</span>`;
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                    }, 1200);
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
