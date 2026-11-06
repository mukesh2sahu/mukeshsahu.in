<?php
/**
 * IT Jobs Portal (Login Required)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth-check.php';

// Strict member-only access rule
require_login();

// Route to detail page if ID parameter is provided
if (!empty($_GET['id'])) {
    header('Location: ' . APP_URL . '/job-detail.php?id=' . (int)$_GET['id']);
    exit;
}

$pageTitle = 'IT Job Opportunities';
$metaDesc = 'Browse verified IT jobs, software engineer openings, remote developer positions, and internships.';

$db = get_db();
$userId = current_user_id();

// 1. Fetch Job Categories for filter
$catStmt = $db->query("SELECT id, name, slug, icon FROM job_categories WHERE status = 'active' ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// 2. Query Parameters
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$workMode = trim($_GET['work_mode'] ?? '');
$experience = trim($_GET['experience'] ?? '');

$where = ["j.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = "(j.title LIKE :search OR j.company_name LIKE :search OR j.required_skills LIKE :search OR j.location LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($categorySlug !== '') {
    $where[] = "cat.slug = :catslug";
    $params[':catslug'] = $categorySlug;
}

if ($workMode !== '') {
    $where[] = "j.work_mode = :workmode";
    $params[':workmode'] = $workMode;
}

if ($experience !== '') {
    $where[] = "j.experience_level LIKE :exp";
    $params[':exp'] = '%' . $experience . '%';
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Total count
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM jobs j 
    JOIN job_categories cat ON j.category_id = cat.id 
    WHERE $whereClause
");
$countStmt->execute($params);
$totalJobs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalJobs / $perPage);

// Fetch Jobs
$jobsStmt = $db->prepare("
    SELECT j.*, cat.name AS category_name, cat.icon AS category_icon, cat.slug AS category_slug,
           (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id AND sj.user_id = :uid) AS is_saved
    FROM jobs j
    JOIN job_categories cat ON j.category_id = cat.id
    WHERE $whereClause
    ORDER BY j.is_featured DESC, j.created_at DESC
    LIMIT $perPage OFFSET $offset
");

$fetchParams = array_merge([':uid' => $userId], $params);
$jobsStmt->execute($fetchParams);
$jobs = $jobsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <!-- Header Banner -->
    <div class="p-4 p-md-5 rounded-4 mb-5" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-primary px-3 py-1 rounded-pill mb-2"><i class="fa-solid fa-lock-open me-1"></i> Members Portal</span>
                <h1 class="display-6 fw-bold text-white mb-2">Explore IT Opportunities</h1>
                <p class="text-light opacity-75 lead fs-6 mb-0">
                    Handpicked software development, full-stack, AI engineering, and internship opportunities directly verified by Mukesh Sahu.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="<?= APP_URL ?>/user/saved-jobs.php" class="btn btn-outline-light">
                    <i class="fa-solid fa-bookmark me-1"></i> View Saved Jobs
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="custom-card p-4 mb-4">
        <form method="GET" action="<?= APP_URL ?>/jobs.php" class="row g-3 align-items-end">
            <!-- Keyword search -->
            <div class="col-lg-4 col-md-6">
                <label for="search" class="form-label small fw-semibold text-secondary">Search Keyword</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" id="search" name="search" value="<?= e($search) ?>" placeholder="Job title, skill, or company...">
                </div>
            </div>

            <!-- Category filter -->
            <div class="col-lg-3 col-md-6">
                <label for="category" class="form-label small fw-semibold text-secondary">Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Work Mode filter -->
            <div class="col-lg-2 col-md-4">
                <label for="work_mode" class="form-label small fw-semibold text-secondary">Work Mode</label>
                <select class="form-select" id="work_mode" name="work_mode">
                    <option value="">Any Mode</option>
                    <option value="Remote" <?= $workMode === 'Remote' ? 'selected' : '' ?>>Remote</option>
                    <option value="Hybrid" <?= $workMode === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                    <option value="On-site" <?= $workMode === 'On-site' ? 'selected' : '' ?>>On-site</option>
                </select>
            </div>

            <!-- Experience filter -->
            <div class="col-lg-2 col-md-4">
                <label for="experience" class="form-label small fw-semibold text-secondary">Experience</label>
                <select class="form-select" id="experience" name="experience">
                    <option value="">Any Experience</option>
                    <option value="0" <?= $experience === '0' ? 'selected' : '' ?>>Fresher / Intern</option>
                    <option value="1" <?= $experience === '1' ? 'selected' : '' ?>>1 - 3 Years</option>
                    <option value="3" <?= $experience === '3' ? 'selected' : '' ?>>3 - 5 Years</option>
                    <option value="5" <?= $experience === '5' ? 'selected' : '' ?>>5+ Years</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="col-lg-1 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100 justify-content-center" title="Apply Filters">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <?php if ($search || $categorySlug || $workMode || $experience): ?>
                    <a href="<?= APP_URL ?>/jobs.php" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category Pills Bar -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom overflow-auto">
        <a href="<?= APP_URL ?>/jobs.php" class="btn btn-sm <?= empty($categorySlug) ? 'btn-primary-custom' : 'btn-outline-secondary' ?> rounded-pill">
            All (<?= $totalJobs ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= APP_URL ?>/jobs.php?category=<?= e($cat['slug']) ?>" class="btn btn-sm <?= $categorySlug === $cat['slug'] ? 'btn-primary-custom' : 'btn-outline-secondary' ?> rounded-pill">
                <i class="fa-solid <?= e($cat['icon']) ?> me-1"></i> <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Job Cards Grid -->
    <?php if (!empty($jobs)): ?>
        <div class="row g-3 g-xl-4">
            <?php foreach ($jobs as $job): ?>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="custom-card job-card h-100">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="job-company-logo">
                                <i class="fa-solid <?= e($job['category_icon'] ?? 'fa-briefcase') ?>"></i>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="card-tag <?= $job['work_mode'] === 'Remote' ? 'tag-green' : 'tag-orange' ?>">
                                    <?= e($job['work_mode']) ?>
                                </span>
                                <button class="btn btn-sm btn-link text-muted btn-save-job p-0 <?= $job['is_saved'] ? 'text-primary' : '' ?>" data-job-id="<?= $job['id'] ?>" data-csrf="<?= csrf_token() ?>" title="Save Job">
                                    <i class="<?= $job['is_saved'] ? 'fa-solid' : 'fa-regular' ?> fa-bookmark fs-5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <span class="card-tag tag-blue small mb-1"><?= e($job['category_name']) ?></span>
                            <?php if ($job['is_featured']): ?>
                                <span class="badge bg-warning text-dark small"><i class="fa-solid fa-star me-1"></i> Featured</span>
                            <?php endif; ?>
                        </div>

                        <h4 class="job-title">
                            <a href="<?= APP_URL ?>/job-detail.php?id=<?= $job['id'] ?>" class="text-dark hover-primary">
                                <?= e($job['title']) ?>
                            </a>
                        </h4>

                        <div class="fw-semibold text-secondary small mb-3">
                            <?= e($job['company_name']) ?> &bull; <?= e($job['location']) ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="job-meta-pill bg-light border px-2 py-1 rounded">
                                <i class="fa-solid fa-indian-rupee-sign text-success"></i> <?= e(format_salary($job['salary_range'])) ?>
                            </span>
                            <span class="job-meta-pill bg-light border px-2 py-1 rounded">
                                <i class="fa-solid fa-briefcase text-primary"></i> <?= e($job['experience_level']) ?>
                            </span>
                        </div>

                        <p class="text-muted small mb-4 flex-grow-1">
                            <?= e(truncate_text($job['description'], 105)) ?>
                        </p>

                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <span class="small text-muted">
                                <i class="fa-regular fa-clock me-1"></i> <?= time_ago($job['created_at']) ?>
                            </span>
                            <div class="d-flex gap-2">
                                <a href="<?= APP_URL ?>/job-detail.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-custom">
                                    Details
                                </a>
                                <a href="<?= e($job['application_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary-custom">
                                    Apply <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
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
                <nav aria-label="Job page navigation">
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categorySlug) ?>&work_mode=<?= urlencode($workMode) ?>&experience=<?= urlencode($experience) ?>">
                                Previous
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categorySlug) ?>&work_mode=<?= urlencode($workMode) ?>&experience=<?= urlencode($experience) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categorySlug) ?>&work_mode=<?= urlencode($workMode) ?>&experience=<?= urlencode($experience) ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="custom-card text-center py-5">
            <div class="stat-icon-wrapper bg-light text-muted mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="fa-solid fa-briefcase fs-2"></i>
            </div>
            <h4 class="fs-5 fw-bold text-dark mb-2">No Matching Job Openings Found</h4>
            <p class="text-muted small mb-4" style="max-width: 450px; margin: 0 auto;">
                Try adjusting your search keywords, clearing selected category filters, or checking back soon.
            </p>
            <a href="<?= APP_URL ?>/jobs.php" class="btn btn-outline-custom">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset All Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
