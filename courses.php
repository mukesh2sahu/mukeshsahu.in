<?php
/**
 * Courses Catalog (Login Required)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth-check.php';

// Member-only access rule
require_login();

// Route to detail if id is present
if (!empty($_GET['id'])) {
    header('Location: ' . APP_URL . '/course-detail.php?id=' . (int)$_GET['id']);
    exit;
}

$pageTitle = 'Online Technology Courses';
$metaDesc = 'Learn Core PHP, MySQL, Modern React, Python, and Autonomous AI Agents directly from Mukesh Sahu.';

$db = get_db();
$userId = current_user_id();

// 1. Fetch Categories for filter
$catStmt = $db->query("SELECT id, name, slug, icon FROM course_categories WHERE status = 'active' ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// 2. Query Parameters
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$skillLevel = trim($_GET['level'] ?? '');

$where = ["c.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = "(c.title LIKE :search OR c.description LIKE :search OR c.instructor_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($categorySlug !== '') {
    $where[] = "cat.slug = :catslug";
    $params[':catslug'] = $categorySlug;
}

if ($skillLevel !== '') {
    $where[] = "c.skill_level = :skill";
    $params[':skill'] = $skillLevel;
}

$whereClause = implode(' AND ', $where);

// Total Count
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM courses c 
    JOIN course_categories cat ON c.category_id = cat.id 
    WHERE $whereClause
");
$countStmt->execute($params);
$totalCourses = (int)$countStmt->fetchColumn();

// Fetch Courses with enrollment info
$coursesStmt = $db->prepare("
    SELECT c.*, cat.name AS category_name, cat.slug AS category_slug,
           e.progress_percentage, e.last_lecture_id,
           (SELECT cl.id FROM course_lectures cl WHERE cl.course_id = c.id ORDER BY cl.order_index ASC LIMIT 1) AS first_lecture_id
    FROM courses c
    JOIN course_categories cat ON c.category_id = cat.id
    LEFT JOIN course_enrollments e ON (c.id = e.course_id AND e.user_id = :uid)
    WHERE $whereClause
    ORDER BY c.is_featured DESC, c.created_at DESC
");
$fetchParams = array_merge([':uid' => $userId], $params);
$coursesStmt->execute($fetchParams);
$courses = $coursesStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <!-- Header Banner -->
    <div class="p-4 p-md-5 rounded-4 mb-5" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-primary px-3 py-1 rounded-pill mb-2"><i class="fa-solid fa-lock-open me-1"></i> Members Learning Portal</span>
                <h1 class="display-6 fw-bold text-white mb-2">Technology & Engineering Masterclasses</h1>
                <p class="text-light opacity-75 lead fs-6 mb-0">
                    Comprehensive, structured curricula embedded with high-definition YouTube video lessons, hands-on architectures, and real-time progress saving.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="<?= APP_URL ?>/user/my-courses.php" class="btn btn-outline-light">
                    <i class="fa-solid fa-graduation-cap me-1"></i> My Enrolled Courses
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="custom-card p-4 mb-4">
        <form method="GET" action="<?= APP_URL ?>/courses.php" class="row g-3 align-items-end">
            <div class="col-lg-6 col-md-6">
                <label for="search" class="form-label small fw-semibold text-secondary">Search Courses</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control" id="search" name="search" value="<?= e($search) ?>" placeholder="Course title, keywords, or technology...">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label for="category" class="form-label small fw-semibold text-secondary">Course Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <label for="level" class="form-label small fw-semibold text-secondary">Skill Level</label>
                <select class="form-select" id="level" name="level">
                    <option value="">All Levels</option>
                    <option value="Beginner" <?= $skillLevel === 'Beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="Intermediate" <?= $skillLevel === 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="Advanced" <?= $skillLevel === 'Advanced' ? 'selected' : '' ?>>Advanced</option>
                </select>
            </div>

            <div class="col-lg-1 col-md-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100 justify-content-center" title="Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <?php if ($search || $categorySlug || $skillLevel): ?>
                    <a href="<?= APP_URL ?>/courses.php" class="btn btn-outline-secondary" title="Reset">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom overflow-auto">
        <a href="<?= APP_URL ?>/courses.php" class="btn btn-sm <?= empty($categorySlug) ? 'btn-primary-custom' : 'btn-outline-secondary' ?> rounded-pill">
            All (<?= $totalCourses ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= APP_URL ?>/courses.php?category=<?= e($cat['slug']) ?>" class="btn btn-sm <?= $categorySlug === $cat['slug'] ? 'btn-primary-custom' : 'btn-outline-secondary' ?> rounded-pill">
                <i class="fa-solid <?= e($cat['icon']) ?> me-1"></i> <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Courses Grid -->
    <?php if (!empty($courses)): ?>
        <div class="row g-4">
            <?php foreach ($courses as $c): 
                $isEnrolled = $c['progress_percentage'] !== null;
                $perc = (float)($c['progress_percentage'] ?? 0);
                $targetLectureId = $c['last_lecture_id'] ?: $c['first_lecture_id'];
            ?>
                <div class="col-lg-4 col-md-6 col-xxl-3">
                    <div class="custom-card h-100">
                        <div class="course-thumb-wrapper">
                            <?php 
                                $thumbUrl = !empty($c['thumbnail']) && file_exists(APP_ROOT . '/assets/uploads/thumbnails/' . $c['thumbnail'])
                                    ? ASSETS_URL . '/uploads/thumbnails/' . e($c['thumbnail'])
                                    : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&auto=format&fit=crop&q=80';
                            ?>
                            <img src="<?= $thumbUrl ?>" alt="<?= e($c['title']) ?>" class="course-thumb-img">
                            <span class="course-duration-badge">
                                <i class="fa-regular fa-clock me-1"></i> <?= e($c['duration']) ?>
                            </span>
                        </div>

                        <div class="course-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="card-tag tag-blue"><?= e($c['category_name']) ?></span>
                                <span class="badge bg-light text-dark border"><?= e($c['skill_level']) ?></span>
                            </div>

                            <h4 class="fs-5 mb-2">
                                <a href="<?= APP_URL ?>/course-detail.php?id=<?= $c['id'] ?>" class="text-dark hover-primary">
                                    <?= e($c['title']) ?>
                                </a>
                            </h4>

                            <div class="small text-muted mb-2">
                                Instructor: <strong><?= e($c['instructor_name']) ?></strong> &bull; <?= (int)$c['total_lectures'] ?> Lectures
                            </div>

                            <p class="text-muted small flex-grow-1 mb-3">
                                <?= e(truncate_text($c['description'], 110)) ?>
                            </p>

                            <?php if ($isEnrolled): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center small mb-1">
                                        <span class="text-muted">Progress</span>
                                        <span class="fw-bold text-success"><?= round($perc) ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $perc ?>%;"></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                                <a href="<?= APP_URL ?>/course-detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    Syllabus
                                </a>
                                <?php if ($isEnrolled): ?>
                                    <a href="<?= APP_URL ?>/watch.php?course_id=<?= $c['id'] ?><?= $targetLectureId ? '&lecture_id=' . $targetLectureId : '' ?>" class="btn btn-sm btn-success">
                                        <?= $perc > 0 ? 'Resume' : 'Watch' ?> <i class="fa-solid fa-play ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= APP_URL ?>/modules/courses/enroll.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary-custom">
                                        Enroll Now <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="custom-card text-center py-5">
            <div class="stat-icon-wrapper bg-light text-muted mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="fa-solid fa-graduation-cap fs-2"></i>
            </div>
            <h4 class="fs-5 fw-bold text-dark mb-2">No Matching Courses Found</h4>
            <p class="text-muted small mb-4" style="max-width: 450px; margin: 0 auto;">
                Try searching for different keywords or clear selected category and level filters.
            </p>
            <a href="<?= APP_URL ?>/courses.php" class="btn btn-outline-custom">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
