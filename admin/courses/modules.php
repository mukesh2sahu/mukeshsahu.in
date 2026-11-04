<?php
/**
 * Admin Course Modules & YouTube Lectures Management
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage Curriculum & YouTube Lectures';
$pageHeading = 'Curriculum & Video Integration';
require_once __DIR__ . '/../includes/header.php';

$courseId = (int)($_GET['course_id'] ?? 0);
$db = get_db();
$errors = [];

// Fetch course
$courseStmt = $db->prepare("SELECT * FROM courses WHERE id = :id LIMIT 1");
$courseStmt->execute([':id' => $courseId]);
$course = $courseStmt->fetch();

if (!$course) {
    set_flash('danger', 'Course not found.');
    header('Location: ' . ADMIN_URL . '/courses/index.php');
    exit;
}

// 1. Handle Add Module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_module') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token invalid.';
    } else {
        $modTitle = trim($_POST['module_title'] ?? '');
        $modDesc = trim($_POST['module_description'] ?? '');
        $orderIdx = (int)($_POST['order_index'] ?? 1);

        if (empty($modTitle)) {
            $errors[] = 'Module title is required.';
        } else {
            $ins = $db->prepare("INSERT INTO course_modules (course_id, title, description, order_index, created_at) VALUES (:cid, :title, :desc, :idx, NOW())");
            $ins->execute([
                ':cid'   => $courseId,
                ':title' => $modTitle,
                ':desc'  => $modDesc,
                ':idx'   => $orderIdx
            ]);
            log_activity('Add Course Module', 'courses', "Added module '$modTitle' to course #$courseId");
            set_flash('success', "Module '$modTitle' created.");
            header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
            exit;
        }
    }
}

// 2. Handle Add Lecture (with YouTube Video ID/URL extraction)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_lecture') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token invalid.';
    } else {
        $moduleId = (int)($_POST['module_id'] ?? 0);
        $lecTitle = trim($_POST['lecture_title'] ?? '');
        $videoInput = trim($_POST['youtube_video_id'] ?? '');
        $duration = (int)($_POST['duration_minutes'] ?? 10);
        $orderIdx = (int)($_POST['order_index'] ?? 1);
        $isFree = isset($_POST['is_free_preview']) ? 1 : 0;
        $lecDesc = trim($_POST['lecture_description'] ?? '');

        $cleanVideoId = extract_youtube_id($videoInput);

        if (empty($lecTitle)) $errors[] = 'Lecture title is required.';
        if (empty($cleanVideoId)) $errors[] = 'Valid YouTube Video ID or URL is required.';
        if ($moduleId <= 0) $errors[] = 'Please select a module.';

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

            // Update total lectures count in course
            $db->query("UPDATE courses SET total_lectures = (SELECT COUNT(*) FROM course_lectures WHERE course_id = $courseId) WHERE id = $courseId");

            log_activity('Add Lecture', 'courses', "Added lecture '$lecTitle' (Video: $cleanVideoId) to course #$courseId");
            set_flash('success', "Lecture '$lecTitle' added successfully.");
            header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
            exit;
        }
    }
}

// 3. Handle Delete Lecture
if (isset($_GET['action']) && $_GET['action'] === 'delete_lecture') {
    $lecId = (int)($_GET['lecture_id'] ?? 0);
    $token = $_GET['token'] ?? '';
    if (verify_csrf_token($token) && $lecId > 0) {
        $del = $db->prepare("DELETE FROM course_lectures WHERE id = :id AND course_id = :cid");
        $del->execute([':id' => $lecId, ':cid' => $courseId]);

        // Update total lectures count in course
        $db->query("UPDATE courses SET total_lectures = (SELECT COUNT(*) FROM course_lectures WHERE course_id = $courseId) WHERE id = $courseId");

        log_activity('Delete Lecture', 'courses', "Deleted lecture #$lecId from course #$courseId");
        set_flash('success', 'Lecture deleted.');
    }
    header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
    exit;
}

// 4. Handle Delete Module
if (isset($_GET['action']) && $_GET['action'] === 'delete_module') {
    $modId = (int)($_GET['module_id'] ?? 0);
    $token = $_GET['token'] ?? '';
    if (verify_csrf_token($token) && $modId > 0) {
        $del = $db->prepare("DELETE FROM course_modules WHERE id = :id AND course_id = :cid");
        $del->execute([':id' => $modId, ':cid' => $courseId]);

        $db->query("UPDATE courses SET total_lectures = (SELECT COUNT(*) FROM course_lectures WHERE course_id = $courseId) WHERE id = $courseId");

        log_activity('Delete Module', 'courses', "Deleted module #$modId from course #$courseId");
        set_flash('success', 'Module and its lectures deleted.');
    }
    header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $courseId);
    exit;
}

// Fetch Modules
$modulesStmt = $db->prepare("SELECT * FROM course_modules WHERE course_id = :cid ORDER BY order_index ASC");
$modulesStmt->execute([':cid' => $courseId]);
$modules = $modulesStmt->fetchAll();

// Fetch Lectures
$lecturesStmt = $db->prepare("SELECT * FROM course_lectures WHERE course_id = :cid ORDER BY order_index ASC");
$lecturesStmt->execute([':cid' => $courseId]);
$allLectures = $lecturesStmt->fetchAll();
?>

<div class="custom-card p-4 mb-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pb-3 border-bottom">
        <div>
            <span class="badge bg-primary-subtle text-primary border small mb-1">Course Curriculum</span>
            <h3 class="fs-4 fw-bold text-dark mb-0"><?= e($course['title']) ?></h3>
            <span class="small text-muted">Instructor: <?= e($course['instructor_name']) ?> &bull; Total: <?= count($allLectures) ?> Video Lectures</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= APP_URL ?>/course-detail.php?id=<?= $course['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-eye me-1"></i> Public Page
            </a>
            <a href="<?= APP_URL ?>/watch.php?course_id=<?= $course['id'] ?>" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-play me-1"></i> Test Watch Player
            </a>
            <a href="<?= ADMIN_URL ?>/courses/index.php" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Courses
            </a>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Modules & Sequenced Lectures Display -->
    <div class="col-lg-7">
        <div class="custom-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fs-6 fw-bold text-dark mb-0">Modules & Sequenced Lectures</h5>
                <span class="badge bg-light text-secondary border"><?= count($modules) ?> Modules</span>
            </div>

            <?php if (!empty($modules)): ?>
                <div class="accordion" id="adminCurriculumAccordion">
                    <?php foreach ($modules as $mIdx => $mod): 
                        $modLecs = array_filter($allLectures, function ($l) use ($mod) {
                            return (int)$l['module_id'] === (int)$mod['id'];
                        });
                    ?>
                        <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                            <h2 class="accordion-header d-flex justify-content-between align-items-center bg-light" id="adminHead<?= $mod['id'] ?>">
                                <button class="accordion-button <?= $mIdx === 0 ? '' : 'collapsed' ?> py-2 px-3 fw-bold bg-light shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminCollapse<?= $mod['id'] ?>">
                                    <span class="text-dark me-2">#<?= $mod['order_index'] ?>.</span>
                                    <span><?= e($mod['title']) ?></span>
                                    <span class="badge bg-white text-secondary border ms-2 small"><?= count($modLecs) ?> Videos</span>
                                </button>
                                <a href="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>&action=delete_module&module_id=<?= $mod['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-link text-danger p-2 me-2" onclick="return confirm('Delete this entire module and all its video lectures?');" title="Delete Module">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </h2>

                            <div id="adminCollapse<?= $mod['id'] ?>" class="accordion-collapse collapse <?= $mIdx === 0 ? 'show' : '' ?>" data-bs-parent="#adminCurriculumAccordion">
                                <div class="accordion-body p-0">
                                    <?php if (!empty($modLecs)): ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($modLecs as $lec): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                                    <div>
                                                        <div class="fw-semibold text-dark">
                                                            <i class="fa-brands fa-youtube text-danger me-1"></i>
                                                            <?= e($lec['title']) ?>
                                                            <?php if ($lec['is_free_preview']): ?>
                                                                <span class="badge bg-success-subtle text-success small ms-1">Free Preview</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            YouTube ID: <code><?= e($lec['youtube_video_id']) ?></code> &bull; <?= (int)$lec['duration_minutes'] ?> mins &bull; Seq: #<?= $lec['order_index'] ?>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <a href="https://www.youtube.com/watch?v=<?= e($lec['youtube_video_id']) ?>" target="_blank" class="btn btn-sm btn-link text-secondary p-0" title="Watch on YouTube">
                                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                        </a>
                                                        <a href="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>&action=delete_lecture&lecture_id=<?= $lec['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="return confirm('Delete this lecture?');" title="Delete Lecture">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </a>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="p-3 text-muted small text-center">No video lectures added to this module yet.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted small">No modules created yet. Add a module using the form on the right.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Add Module & Add Lecture Forms -->
    <div class="col-lg-5">
        <!-- Add Video Lecture Form -->
        <div class="custom-card p-4 mb-4">
            <h5 class="fs-6 fw-bold text-dark mb-3">
                <i class="fa-brands fa-youtube text-danger me-2"></i> Add YouTube Video Lecture
            </h5>

            <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>" novalidate>
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_lecture">

                <div class="mb-3">
                    <label for="module_id" class="form-label small fw-semibold">Assign to Module <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="module_id" name="module_id" required>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= e($m['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="lecture_title" class="form-label small fw-semibold">Lecture Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="lecture_title" name="lecture_title" placeholder="e.g. Setting Up PDO Connections & Error Handling" required>
                </div>

                <div class="mb-3">
                    <label for="youtube_video_id" class="form-label small fw-semibold">YouTube URL or Video ID <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-danger"><i class="fa-brands fa-youtube"></i></span>
                        <input type="text" class="form-control" id="youtube_video_id" name="youtube_video_id" placeholder="https://www.youtube.com/watch?v=... or 11-char ID" required>
                    </div>
                    <div class="form-text small" style="font-size: 0.72rem;">Accepts any full YouTube link, youtu.be shortlink, or raw video ID.</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="duration_minutes" class="form-label small fw-semibold">Duration (Minutes)</label>
                        <input type="number" class="form-control form-control-sm" id="duration_minutes" name="duration_minutes" value="25" min="1">
                    </div>
                    <div class="col-6">
                        <label for="lec_order_index" class="form-label small fw-semibold">Sequence / Order</label>
                        <input type="number" class="form-control form-control-sm" id="lec_order_index" name="order_index" value="<?= count($allLectures) + 1 ?>" min="1">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="lecture_description" class="form-label small fw-semibold">Lecture Description / Notes</label>
                    <textarea class="form-control form-control-sm" id="lecture_description" name="lecture_description" rows="2" placeholder="Summary of topics covered, code links, or commands demonstrated..."></textarea>
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" type="checkbox" id="is_free_preview" name="is_free_preview">
                    <label class="form-check-label small text-secondary" for="is_free_preview">
                        Allow Free Preview (Accessible without full enrollment)
                    </label>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2 justify-content-center btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Add Video Lecture
                </button>
            </form>
        </div>

        <!-- Add Module Form -->
        <div class="custom-card p-4">
            <h5 class="fs-6 fw-bold text-dark mb-3">
                <i class="fa-solid fa-folder-plus text-primary me-2"></i> Add New Module
            </h5>

            <form method="POST" action="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $courseId ?>" novalidate>
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_module">

                <div class="mb-3">
                    <label for="module_title" class="form-label small fw-semibold">Module Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="module_title" name="module_title" placeholder="e.g. Module 2: Database Design & Querying" required>
                </div>

                <div class="mb-3">
                    <label for="mod_order_index" class="form-label small fw-semibold">Order Index</label>
                    <input type="number" class="form-control form-control-sm" id="mod_order_index" name="order_index" value="<?= count($modules) + 1 ?>" min="1">
                </div>

                <div class="mb-3">
                    <label for="module_description" class="form-label small fw-semibold">Description (optional)</label>
                    <textarea class="form-control form-control-sm" id="module_description" name="module_description" rows="2" placeholder="Brief outline of this module..."></textarea>
                </div>

                <button type="submit" class="btn btn-outline-primary w-100 py-2 justify-content-center btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Create Module
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
