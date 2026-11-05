<?php
/**
 * Blog Article Detail Page (Public)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);
$db = get_db();

if (!empty($slug)) {
    $stmt = $db->prepare("
        SELECT b.*, cat.name AS category_name, cat.slug AS category_slug, u.name AS author_name, up.headline AS author_headline, up.avatar AS author_avatar
        FROM blogs b
        JOIN blog_categories cat ON b.category_id = cat.id
        LEFT JOIN users u ON b.author_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE b.slug = :slug AND b.status = 'published'
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
} else {
    $stmt = $db->prepare("
        SELECT b.*, cat.name AS category_name, cat.slug AS category_slug, u.name AS author_name, up.headline AS author_headline, up.avatar AS author_avatar
        FROM blogs b
        JOIN blog_categories cat ON b.category_id = cat.id
        LEFT JOIN users u ON b.author_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE b.id = :id AND b.status = 'published'
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
}

$blog = $stmt->fetch();

if (!$blog) {
    set_flash('danger', 'The requested article could not be found.');
    header('Location: ' . APP_URL . '/blogs.php');
    exit;
}

// Increment views count
$updViews = $db->prepare("UPDATE blogs SET views_count = views_count + 1 WHERE id = :id");
$updViews->execute([':id' => $blog['id']]);

// Fetch tags for this blog
$tagsStmt = $db->prepare("
    SELECT t.* 
    FROM blog_tags t
    JOIN blog_tag_relations r ON t.id = r.tag_id
    WHERE r.blog_id = :bid
");
$tagsStmt->execute([':bid' => $blog['id']]);
$tags = $tagsStmt->fetchAll();

// Fetch related articles
$relStmt = $db->prepare("
    SELECT id, title, slug, featured_image, published_at 
    FROM blogs 
    WHERE category_id = :cid AND id != :id AND status = 'published'
    ORDER BY published_at DESC 
    LIMIT 3
");
$relStmt->execute([':cid' => $blog['category_id'], ':id' => $blog['id']]);
$relatedBlogs = $relStmt->fetchAll();

$pageTitle = !empty($blog['meta_title']) ? $blog['meta_title'] : $blog['title'];
$metaDesc = !empty($blog['meta_description']) ? $blog['meta_description'] : truncate_text($blog['excerpt'] ?? $blog['content'], 160);

// Default image fallback
$blogImg = 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1000&auto=format&fit=crop&q=80';
if ($blog['id'] == 1) $blogImg = 'https://images.unsplash.com/photo-1599507593499-a3f7f7d97f66?w=1000&auto=format&fit=crop&q=80';
if ($blog['id'] == 2) $blogImg = 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1000&auto=format&fit=crop&q=80';
if ($blog['id'] == 3) $blogImg = 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1000&auto=format&fit=crop&q=80';

if (!empty($blog['featured_image']) && file_exists(APP_ROOT . '/assets/uploads/blogs/' . $blog['featured_image'])) {
    $blogImg = ASSETS_URL . '/uploads/blogs/' . e($blog['featured_image']);
}

$currentUrl = urlencode(APP_URL . '/blog-detail.php?slug=' . $blog['slug']);
$shareTitle = urlencode($blog['title']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/blogs.php">Tech Blogs</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/blogs.php?category=<?= e($blog['category_slug']) ?>"><?= e($blog['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($blog['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Main Article Column -->
        <div class="col-lg-8">
            <article class="custom-card p-4 p-md-5 mb-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="card-tag tag-purple"><?= e($blog['category_name']) ?></span>
                    <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i> <?= format_date($blog['published_at']) ?></span>
                    <span class="text-muted small ms-2"><i class="fa-regular fa-eye me-1"></i> <?= (int)$blog['views_count'] + 1 ?> views</span>
                </div>

                <h1 class="display-6 fw-bold text-dark mb-4"><?= e($blog['title']) ?></h1>

                <!-- Author info bar -->
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <img src="<?= ASSETS_URL ?>/images/profile-mukesh.png" alt="<?= e($blog['author_name'] ?? 'Mukesh Sahu') ?>" class="rounded-circle border" style="width: 46px; height: 46px; object-fit: cover;">
                    <div>
                        <div class="fw-bold text-dark small mb-0"><?= e($blog['author_name'] ?? 'Mukesh Sahu') ?></div>
                        <div class="text-muted small"><?= e($blog['author_headline'] ?? 'Full Stack Software Engineer') ?></div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="rounded-4 overflow-hidden mb-4 shadow-sm" style="max-height: 420px;">
                    <img src="<?= $blogImg ?>" alt="<?= e($blog['title']) ?>" class="w-100 h-100" style="object-fit: cover;">
                </div>

                <!-- Article Content -->
                <div class="article-body text-secondary mb-4" style="line-height: 1.9; font-size: 1.08rem;">
                    <?= $blog['content'] ?>
                </div>

                <!-- Tags list -->
                <?php if (!empty($tags)): ?>
                    <div class="pt-4 border-top mb-4">
                        <h6 class="fw-bold text-dark small mb-2"><i class="fa-solid fa-tags me-1 text-primary"></i> Article Topics:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($tags as $t): ?>
                                <a href="<?= APP_URL ?>/blogs.php?tag=<?= e($t['slug']) ?>" class="badge bg-light text-dark border px-3 py-2 rounded-pill hover-primary">
                                    #<?= e($t['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Social Share Bar -->
                <div class="p-3 bg-light rounded-3 border d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                    <span class="fw-bold text-dark small"><i class="fa-solid fa-share-nodes me-1 text-primary"></i> Share this article:</span>
                    <div class="d-flex gap-2">
                        <a href="https://twitter.com/intent/tweet?url=<?= $currentUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-dark" title="Share on Twitter">
                            <i class="fa-brands fa-x-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $currentUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary" title="Share on LinkedIn">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send?text=<?= $shareTitle ?>%20<?= $currentUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success" title="Share on WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar / Author & Related Articles -->
        <div class="col-lg-4">
            <!-- Author Card -->
            <div class="custom-card p-4 mb-4 text-center">
                <img src="<?= ASSETS_URL ?>/images/profile-mukesh.png" alt="Mukesh Sahu" class="rounded-circle border border-2 border-primary mb-3 mx-auto" style="width: 80px; height: 80px; object-fit: cover;">
                <h5 class="fs-5 fw-bold text-dark mb-1">Mukesh Sahu</h5>
                <p class="text-primary small fw-semibold mb-2">Author & Technology Educator</p>
                <p class="text-muted small mb-3">
                    Writing about full-stack engineering, database scalability with PDO, and building autonomous agentic systems.
                </p>
                <a href="<?= APP_URL ?>/about.php" class="btn btn-sm btn-outline-custom">
                    About Mukesh <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Related Articles -->
            <?php if (!empty($relatedBlogs)): ?>
                <div class="custom-card p-4">
                    <h5 class="fs-6 fw-bold text-dark mb-3">Related Technology Articles</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($relatedBlogs as $rel): ?>
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold mb-1 fs-6">
                                    <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($rel['slug']) ?>" class="text-dark hover-primary">
                                        <?= e($rel['title']) ?>
                                    </a>
                                </h6>
                                <span class="small text-muted"><i class="fa-regular fa-calendar me-1"></i> <?= format_date($rel['published_at']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
