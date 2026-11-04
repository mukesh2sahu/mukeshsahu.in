<?php
/**
 * Admin Course Management - Add Course
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Add New Course';
$pageHeading = 'Create New Course Masterclass';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$errors = [];

$categories = $db->query("SELECT * FROM course_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$title = '';
$slug = '';
$categoryId = $categories[0]['id'] ?? 1;
$skillLevel = 'Beginner';
$duration = '12 Hours';
$totalLectures = 0;
$language = 'English & Hindi';
$instructorName = 'Mukesh Sahu';
$description = '';
$playlistUrl = '';
$isFeatured = 0;
$status = 'published';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $skillLevel = $_POST['skill_level'] ?? 'Beginner';
    $duration = trim($_POST['duration'] ?? '');
    $totalLectures = (int)($_POST['total_lectures'] ?? 0);
    $language = trim($_POST['language'] ?? 'English & Hindi');
    $instructorName = trim($_POST['instructor_name'] ?? 'Mukesh Sahu');
    $description = trim($_POST['description'] ?? '');
    $playlistUrl = trim($_POST['playlist_url'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';

    if (empty($title)) $errors[] = 'Course title is required.';
    if (empty($slug)) $slug = slugify($title);
    if (empty($duration)) $errors[] = 'Course duration is required.';
    if (empty($description)) $errors[] = 'Course description is required.';

    // Check duplicate slug
    $check = $db->prepare("SELECT id FROM courses WHERE slug = :slug LIMIT 1");
    $check->execute([':slug' => $slug]);
    if ($check->fetch()) {
        $slug = $slug . '-' . time();
    }

    // Thumbnail upload
    $thumbnail = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_image_file($_FILES['thumbnail'], 'thumbnails');
        if ($upload['success']) {
            $thumbnail = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $createdBy = current_user_id();

        $stmt = $db->prepare("
            INSERT INTO courses (category_id, title, slug, thumbnail, description, instructor_name, skill_level, duration, total_lectures, language, playlist_url, is_featured, status, created_by, created_at)
            VALUES (:cat, :title, :slug, :thumb, :desc, :instructor, :level, :duration, :total_lecs, :lang, :playlist, :feat, :status, :created_by, NOW())
        ");
        $stmt->execute([
            ':cat'        => $categoryId,
            ':title'      => $title,
            ':slug'       => $slug,
            ':thumb'      => $thumbnail,
            ':desc'       => $description,
            ':instructor' => $instructorName,
            ':level'      => $skillLevel,
            ':duration'   => $duration,
            ':total_lecs' => $totalLectures,
            ':lang'       => $language,
            ':playlist'   => $playlistUrl,
            ':feat'       => $isFeatured,
            ':status'     => $status,
            ':created_by' => $createdBy
        ]);
        $newCourseId = (int)$db->lastInsertId();

        // Create initial default module
        $modStmt = $db->prepare("INSERT INTO course_modules (course_id, title, description, order_index, created_at) VALUES (:cid, 'Module 1: Introduction & Fundamentals', 'Course overview and environment setup', 1, NOW())");
        $modStmt->execute([':cid' => $newCourseId]);

        log_activity('Add Course', 'courses', "Created course '$title' (#$newCourseId)");
        set_flash('success', "Course '$title' created! Now add your first module and YouTube video lectures.");
        header('Location: ' . ADMIN_URL . '/courses/modules.php?course_id=' . $newCourseId);
        exit;
    }
}
?>

<div class="custom-card p-4 p-md-5">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Create New Course</h4>
            <span class="text-muted small">Set up course details before adding video modules</span>
        </div>
        <a href="<?= ADMIN_URL ?>/courses/index.php" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Courses
        </a>
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

    <form method="POST" action="<?= ADMIN_URL ?>/courses/add.php" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label for="title" class="form-label fw-semibold small">Course Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?= e($title) ?>" placeholder="e.g. Master Core PHP & MySQL: Zero to Production" required>
            </div>
            <div class="col-md-4">
                <label for="category_id" class="form-label fw-semibold small">Course Category <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="instructor_name" class="form-label fw-semibold small">Instructor Name</label>
                <input type="text" class="form-control" id="instructor_name" name="instructor_name" value="<?= e($instructorName) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="skill_level" class="form-label fw-semibold small">Skill Level</label>
                <select class="form-select" id="skill_level" name="skill_level">
                    <option value="Beginner" <?= $skillLevel === 'Beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="Intermediate" <?= $skillLevel === 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="Advanced" <?= $skillLevel === 'Advanced' ? 'selected' : '' ?>>Advanced</option>
                    <option value="All Levels" <?= $skillLevel === 'All Levels' ? 'selected' : '' ?>>All Levels</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="duration" class="form-label fw-semibold small">Duration</label>
                <input type="text" class="form-control" id="duration" name="duration" value="<?= e($duration) ?>" placeholder="e.g. 18 Hours" required>
            </div>

            <div class="col-md-4">
                <label for="language" class="form-label fw-semibold small">Language</label>
                <input type="text" class="form-control" id="language" name="language" value="<?= e($language) ?>" placeholder="English & Hindi">
            </div>
            <div class="col-md-4">
                <label for="playlist_url" class="form-label fw-semibold small">YouTube Playlist URL (optional)</label>
                <input type="url" class="form-control" id="playlist_url" name="playlist_url" value="<?= e($playlistUrl) ?>" placeholder="https://www.youtube.com/playlist?list=...">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold small">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>

            <div class="col-12">
                <label for="thumbnail" class="form-label fw-semibold small">Course Thumbnail Image</label>
                <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                <div class="form-text small">Accepted formats: JPG, PNG, WEBP (Ratio: 16:9 recommended).</div>
            </div>

            <div class="col-12">
                <label for="description" class="form-label fw-semibold small">Course Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Comprehensive course syllabus description, objectives, and what learners will build..." required><?= e($description) ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?= $isFeatured ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold small" for="is_featured">
                        <i class="fa-solid fa-star text-warning me-1"></i> Feature this course on Homepage
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="fa-solid fa-arrow-right me-2"></i> Save & Manage Video Lectures
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
