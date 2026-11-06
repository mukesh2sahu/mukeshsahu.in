<?php
/**
 * Blogs Catalog (Public Access)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Technology & Engineering Blogs';
$metaDesc = 'Read in-depth software engineering blogs, PHP tutorials, AI agent guides, and career roadmaps by Mukesh Sahu.';

$db = get_db();

// 1. Fetch Categories
$catStmt = $db->query("SELECT id, name, slug FROM blog_categories WHERE status = 'active' ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// 2. Fetch Popular Tags
$tagStmt = $db->query("SELECT id, name, slug FROM blog_tags ORDER BY name ASC LIMIT 10");
$tags = $tagStmt->fetchAll();

// 3. Query Parameters
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$tagSlug = trim($_GET['tag'] ?? '');

$where = ["b.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = "(b.title LIKE :search OR b.content LIKE :search OR b.excerpt LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($categorySlug !== '') {
    $where[] = "cat.slug = :catslug";
    $params[':catslug'] = $categorySlug;
}

if ($tagSlug !== '') {
    $where[] = "b.id IN (SELECT r.blog_id FROM blog_tag_relations r JOIN blog_tags t ON r.tag_id = t.id WHERE t.slug = :tagslug)";
    $params[':tagslug'] = $tagSlug;
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

// Total Count
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM blogs b 
    JOIN blog_categories cat ON b.category_id = cat.id 
    WHERE $whereClause
");
$countStmt->execute($params);
$totalBlogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalBlogs / $perPage);

// Fetch Blogs
$blogsStmt = $db->prepare("
    SELECT b.*, cat.name AS category_name, cat.slug AS category_slug, u.name AS author_name 
    FROM blogs b
    JOIN blog_categories cat ON b.category_id = cat.id
    LEFT JOIN users u ON b.author_id = u.id
    WHERE $whereClause
    ORDER BY b.published_at DESC
    LIMIT $perPage OFFSET $offset
");
$blogsStmt->execute($params);
$blogs = $blogsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <!-- Header Banner -->
    <div class="p-4 p-md-5 rounded-4 mb-5" style="background: linear-gradient(135deg, #0f172a, #1e1b4b); color: #fff;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-purple px-3 py-1 rounded-pill mb-2" style="background-color: var(--accent-purple);"><i class="fa-solid fa-newspaper me-1"></i> Tech Articles</span>
                <h1 class="display-6 fw-bold text-white mb-2">Technology & Engineering Insights</h1>
                <p class="text-light opacity-75 lead fs-6 mb-0">
                    Practical architecture tutorials, backend engineering, deep dives into AI agents, and proven career strategies.
                </p>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="custom-card p-4 mb-4">
        <form method="GET" action="<?= APP_URL ?>/blogs.php" class="row g-3 align-items-end">
            <div class="col-lg-7 col-md-6">
                <label for="search" class="form-label small fw-semibold text-secondary">Search Articles</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" id="search" name="search" value="<?= e($search) ?>" placeholder="Search by topic, keyword, or language...">
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <label for="category" class="form-label small fw-semibold text-secondary">Filter by Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-1 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100 justify-content-center" title="Search">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <?php if ($search || $categorySlug || $tagSlug): ?>
                    <a href="<?= APP_URL ?>/blogs.php" class="btn btn-outline-secondary" title="Reset">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom overflow-auto">
        <a href="<?= APP_URL ?>/blogs.php" class="btn btn-sm <?= (empty($categorySlug) && empty($tagSlug)) ? 'btn-primary-custom' : 'btn-outline-secondary' ?> rounded-pill">
            All Articles (<?= $totalBlogs ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= APP_URL ?>/blogs.php?category=<?= e($cat['slug']) ?>" class="btn btn-sm <?= $categorySlug === $cat['slug'] ? 'btn-primary-custom' : 'btn-outline-secondary' ?> rounded-pill">
                <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Blogs Grid -->
    <?php if (!empty($blogs)): ?>
        <div class="row g-3 g-xl-4">
            <?php foreach ($blogs as $blog): 
                $blogImg = 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=80';
                if ($blog['id'] == 1) $blogImg = 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80';
                if ($blog['id'] == 2) $blogImg = 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&auto=format&fit=crop&q=80';
                if ($blog['id'] == 3) $blogImg = 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&auto=format&fit=crop&q=80';
                if ($blog['id'] == 4) $blogImg = 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=600&auto=format&fit=crop&q=80';

                if (!empty($blog['featured_image']) && file_exists(APP_ROOT . '/assets/uploads/blogs/' . $blog['featured_image'])) {
                    $blogImg = ASSETS_URL . '/uploads/blogs/' . e($blog['featured_image']);
                }
            ?>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="custom-card h-100 d-flex flex-column">
                        <img src="<?= $blogImg ?>" alt="<?= e($blog['title']) ?>" class="blog-card-img" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=600&auto=format&fit=crop&q=80';">
                        <div class="p-3 p-xl-4 flex-grow-1 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="card-tag tag-purple"><?= e($blog['category_name']) ?></span>
                                <span class="blog-meta"><i class="fa-regular fa-calendar me-1"></i> <?= format_date($blog['published_at']) ?></span>
                            </div>

                            <h4 class="blog-title">
                                <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($blog['slug']) ?>" class="text-dark hover-primary">
                                    <?= e($blog['title']) ?>
                                </a>
                            </h4>

                            <p class="blog-excerpt flex-grow-1 mb-3">
                                <?= e(truncate_text($blog['excerpt'] ?? $blog['content'], 100)) ?>
                            </p>

                            <div class="pt-3 border-top mt-auto d-flex align-items-center justify-content-between">
                                <span class="small text-muted">
                                    <i class="fa-regular fa-eye me-1"></i> <?= (int)$blog['views_count'] ?> views
                                </span>
                                <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($blog['slug']) ?>" class="btn btn-sm btn-link text-primary fw-semibold p-0">
                                    Read Article <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center mt-5">
                <nav aria-label="Blog page navigation">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categorySlug) ?>&tag=<?= urlencode($tagSlug) ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categorySlug) ?>&tag=<?= urlencode($tagSlug) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categorySlug) ?>&tag=<?= urlencode($tagSlug) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="custom-card text-center py-5">
            <div class="stat-icon-wrapper bg-light text-muted mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="fa-solid fa-newspaper fs-2"></i>
            </div>
            <h4 class="fs-5 fw-bold text-dark mb-2">No Articles Found</h4>
            <p class="text-muted small mb-4" style="max-width: 450px; margin: 0 auto;">
                Try searching for different keywords or view all published articles.
            </p>
            <a href="<?= APP_URL ?>/blogs.php" class="btn btn-outline-custom">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
