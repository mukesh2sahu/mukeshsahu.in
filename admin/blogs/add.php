<?php
/**
 * Admin Blog Management - Add Blog
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Write New Blog';
$pageHeading = 'Compose Technology Article';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$errors = [];

$categories = $db->query("SELECT * FROM blog_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$title = '';
$slug = '';
$categoryId = $categories[0]['id'] ?? 1;
$excerpt = '';
$content = '';
$metaTitle = '';
$metaDesc = '';
$status = 'published';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDesc = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? 'published';

    if (empty($title)) $errors[] = 'Blog title is required.';
    if (empty($slug)) $slug = slugify($title);
    if (empty($content)) $errors[] = 'Blog article content is required.';

    // Check duplicate slug
    $check = $db->prepare("SELECT id FROM blogs WHERE slug = :slug LIMIT 1");
    $check->execute([':slug' => $slug]);
    if ($check->fetch()) {
        $slug = $slug . '-' . time();
    }

    // Featured image upload
    $featuredImg = null;
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = upload_image_file($_FILES['featured_image'], 'blogs');
        if ($upload['success']) {
            $featuredImg = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $authorId = current_user_id();
        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            INSERT INTO blogs (category_id, author_id, title, slug, featured_image, excerpt, content, meta_title, meta_description, status, published_at, created_at)
            VALUES (:cat, :author, :title, :slug, :img, :excerpt, :content, :mtitle, :mdesc, :status, :pub_at, NOW())
        ");
        $stmt->execute([
            ':cat'     => $categoryId,
            ':author'  => $authorId,
            ':title'   => $title,
            ':slug'    => $slug,
            ':img'     => $featuredImg,
            ':excerpt' => $excerpt,
            ':content' => $content,
            ':mtitle'  => $metaTitle,
            ':mdesc'   => $metaDesc,
            ':status'  => $status,
            ':pub_at'  => $publishedAt
        ]);
        $newId = (int)$db->lastInsertId();

        log_activity('Add Blog', 'blogs', "Published article '$title' (#$newId)");
        set_flash('success', "Article '$title' created successfully.");
        header('Location: ' . ADMIN_URL . '/blogs/index.php');
        exit;
    }
}
?>

<div class="custom-card p-4 p-md-5">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Compose New Article</h4>
            <span class="text-muted small">Share tutorials, technical deep dives, or career advice</span>
        </div>
        <a href="<?= ADMIN_URL ?>/blogs/index.php" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Blogs
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

    <form method="POST" action="<?= ADMIN_URL ?>/blogs/add.php" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label for="title" class="form-label fw-semibold small">Article Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?= e($title) ?>" placeholder="e.g. Building High-Performance PHP Applications with PDO and Modern Architecture" required>
            </div>
            <div class="col-md-4">
                <label for="category_id" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-8">
                <label for="featured_image" class="form-label fw-semibold small">Featured Article Header Image</label>
                <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold small">Publication Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>

            <div class="col-12">
                <label for="excerpt" class="form-label fw-semibold small">Brief Excerpt / Summary</label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="2" placeholder="Brief teaser for article card previews..."><?= e($excerpt) ?></textarea>
            </div>

            <div class="col-12">
                <label for="content" class="form-label fw-semibold small">Article Content (Supports HTML) <span class="text-danger">*</span></label>
                <textarea class="form-control font-monospace" id="content" name="content" rows="12" placeholder="Write your full article HTML content here (<h3>, <p>, <ul>, <pre><code>, etc.)..." required><?= e($content) ?></textarea>
            </div>

            <div class="col-md-6">
                <label for="meta_title" class="form-label fw-semibold small">SEO Meta Title (optional)</label>
                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?= e($metaTitle) ?>" placeholder="Title for Google search results">
            </div>
            <div class="col-md-6">
                <label for="meta_description" class="form-label fw-semibold small">SEO Meta Description (optional)</label>
                <input type="text" class="form-control" id="meta_description" name="meta_description" value="<?= e($metaDesc) ?>" placeholder="150-160 characters summary for search engines">
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="fa-solid fa-paper-plane me-2"></i> Save & Publish Article
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
