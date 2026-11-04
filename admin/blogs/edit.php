<?php
/**
 * Admin Blog Management - Edit Blog
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Edit Blog Post';
$pageHeading = 'Edit Technology Article';
require_once __DIR__ . '/../includes/header.php';

$blogId = (int)($_GET['id'] ?? 0);
$db = get_db();
$errors = [];

$stmt = $db->prepare("SELECT * FROM blogs WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $blogId]);
$blog = $stmt->fetch();

if (!$blog) {
    set_flash('danger', 'Blog post not found.');
    header('Location: ' . ADMIN_URL . '/blogs/index.php');
    exit;
}

$categories = $db->query("SELECT * FROM blog_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? $blog['category_id']);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDesc = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? 'published';

    if (empty($title)) $errors[] = 'Blog title is required.';
    if (empty($content)) $errors[] = 'Article content is required.';

    // Image upload if provided
    $featuredImg = $blog['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_image_file($_FILES['featured_image'], 'blogs');
        if ($upload['success']) {
            $featuredImg = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $upd = $db->prepare("
            UPDATE blogs SET 
                category_id = :cat,
                title = :title,
                featured_image = :img,
                excerpt = :excerpt,
                content = :content,
                meta_title = :mtitle,
                meta_description = :mdesc,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ");
        $upd->execute([
            ':cat'     => $categoryId,
            ':title'   => $title,
            ':img'     => $featuredImg,
            ':excerpt' => $excerpt,
            ':content' => $content,
            ':mtitle'  => $metaTitle,
            ':mdesc'   => $metaDesc,
            ':status'  => $status,
            ':id'      => $blogId
        ]);

        log_activity('Edit Blog', 'blogs', "Updated article '$title' (#$blogId)");
        set_flash('success', "Article '$title' updated successfully.");
        header('Location: ' . ADMIN_URL . '/blogs/index.php');
        exit;
    }
}
?>

<div class="custom-card p-4 p-md-5">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Edit Article: <?= e($blog['title']) ?></h4>
            <span class="text-muted small">Views: <?= (int)$blog['views_count'] ?> &bull; Slug: <code><?= e($blog['slug']) ?></code></span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($blog['slug']) ?>" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-eye me-1"></i> View Live
            </a>
            <a href="<?= ADMIN_URL ?>/blogs/index.php" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Blogs
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

    <form method="POST" action="<?= ADMIN_URL ?>/blogs/edit.php?id=<?= $blog['id'] ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label for="title" class="form-label fw-semibold small">Article Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?= e($blog['title']) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="category_id" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$blog['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-8">
                <label for="featured_image" class="form-label fw-semibold small">Featured Article Header Image (Leave empty to keep existing)</label>
                <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold small">Publication Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="published" <?= $blog['status'] === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= $blog['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $blog['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>

            <div class="col-12">
                <label for="excerpt" class="form-label fw-semibold small">Brief Excerpt / Summary</label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="2"><?= e($blog['excerpt'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label for="content" class="form-label fw-semibold small">Article Content (Supports HTML) <span class="text-danger">*</span></label>
                <textarea class="form-control font-monospace" id="content" name="content" rows="12" required><?= e($blog['content']) ?></textarea>
            </div>

            <div class="col-md-6">
                <label for="meta_title" class="form-label fw-semibold small">SEO Meta Title</label>
                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?= e($blog['meta_title'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label for="meta_description" class="form-label fw-semibold small">SEO Meta Description</label>
                <input type="text" class="form-control" id="meta_description" name="meta_description" value="<?= e($blog['meta_description'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
