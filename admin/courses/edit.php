<?php
/**
 * Admin Course Management - Edit Course
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Edit Course';
$pageHeading = 'Edit Course Masterclass';
require_once __DIR__ . '/../includes/header.php';

$courseId = (int)($_GET['id'] ?? 0);
$db = get_db();
$errors = [];

$stmt = $db->prepare("SELECT * FROM courses WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $courseId]);
$course = $stmt->fetch();

if (!$course) {
    set_flash('danger', 'Course not found.');
    header('Location: ' . ADMIN_URL . '/courses/index.php');
    exit;
}

$categories = $db->query("SELECT * FROM course_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? $course['category_id']);
    $skillLevel = $_POST['skill_level'] ?? 'Beginner';
    $duration = trim($_POST['duration'] ?? '');
    $language = trim($_POST['language'] ?? 'English & Hindi');
    $instructorName = trim($_POST['instructor_name'] ?? 'Mukesh Sahu');
    $description = trim($_POST['description'] ?? '');
    $playlistUrl = trim($_POST['playlist_url'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';

    if (empty($title)) $errors[] = 'Course title is required.';
    if (empty($duration)) $errors[] = 'Course duration is required.';
    if (empty($description)) $errors[] = 'Course description is required.';

    // Thumbnail upload if provided
    $thumbnail = $course['thumbnail'];
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_image_file($_FILES['thumbnail'], 'thumbnails');
        if ($upload['success']) {
            $thumbnail = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $upd = $db->prepare("
            UPDATE courses SET 
                category_id = :cat,
                title = :title,
                thumbnail = :thumb,
                description = :desc,
                instructor_name = :instructor,
                skill_level = :level,
                duration = :duration,
                language = :lang,
                playlist_url = :playlist,
                is_featured = :feat,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ");
        $upd->execute([
            ':cat'        => $categoryId,
            ':title'      => $title,
            ':thumb'      => $thumbnail,
            ':desc'       => $description,
            ':instructor' => $instructorName,
            ':level'      => $skillLevel,
            ':duration'   => $duration,
            ':lang'       => $language,
            ':playlist'   => $playlistUrl,
            ':feat'       => $isFeatured,
            ':status'     => $status,
            ':id'         => $courseId
        ]);

        log_activity('Edit Course', 'courses', "Updated course '$title' (#$courseId)");
        set_flash('success', "Course '$title' updated successfully.");
        header('Location: ' . ADMIN_URL . '/courses/index.php');
        exit;
    }
}
?>

<div class="custom-card p-4 p-md-5">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Edit Course: <?= e($course['title']) ?></h4>
            <span class="text-muted small">Update course overview, thumbnails, and status</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= ADMIN_URL ?>/courses/modules.php?course_id=<?= $course['id'] ?>" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-list-check me-1"></i> Manage Video Lectures
            </a>
            <a href="<?= ADMIN_URL ?>/courses/index.php" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Courses
            </a>
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

    <form method="POST" action="<?= ADMIN_URL ?>/courses/edit.php?id=<?= $course['id'] ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label for="title" class="form-label fw-semibold small">Course Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?= e($course['title']) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="category_id" class="form-label fw-semibold small">Course Category <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$course['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="instructor_name" class="form-label fw-semibold small">Instructor Name</label>
                <input type="text" class="form-control" id="instructor_name" name="instructor_name" value="<?= e($course['instructor_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="skill_level" class="form-label fw-semibold small">Skill Level</label>
                <select class="form-select" id="skill_level" name="skill_level">
                    <option value="Beginner" <?= $course['skill_level'] === 'Beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="Intermediate" <?= $course['skill_level'] === 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="Advanced" <?= $course['skill_level'] === 'Advanced' ? 'selected' : '' ?>>Advanced</option>
                    <option value="All Levels" <?= $course['skill_level'] === 'All Levels' ? 'selected' : '' ?>>All Levels</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="duration" class="form-label fw-semibold small">Duration</label>
                <input type="text" class="form-control" id="duration" name="duration" value="<?= e($course['duration']) ?>" required>
            </div>

            <div class="col-md-4">
                <label for="language" class="form-label fw-semibold small">Language</label>
                <input type="text" class="form-control" id="language" name="language" value="<?= e($course['language']) ?>">
            </div>
            <div class="col-md-4">
                <label for="playlist_url" class="form-label fw-semibold small">YouTube Playlist URL</label>
                <input type="url" class="form-control" id="playlist_url" name="playlist_url" value="<?= e($course['playlist_url'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold small">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $course['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>

            <div class="col-12">
                <label for="thumbnail" class="form-label fw-semibold small">Course Thumbnail Image (Leave empty to keep existing)</label>
                <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
            </div>

            <div class="col-12">
                <label for="description" class="form-label fw-semibold small">Course Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="5" required><?= e($course['description']) ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?= $course['is_featured'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold small" for="is_featured">
                        <i class="fa-solid fa-star text-warning me-1"></i> Feature this course on Homepage
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
