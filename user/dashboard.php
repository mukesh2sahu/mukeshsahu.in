<?php
/**
 * User Dashboard
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'User Dashboard';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_login();

$userId = current_user_id();
$db = get_db();

// 1. Fetch Stats
$enrolledCount = (int)$db->query("SELECT COUNT(*) FROM course_enrollments WHERE user_id = $userId")->fetchColumn();
$completedCount = (int)$db->query("SELECT COUNT(*) FROM course_enrollments WHERE user_id = $userId AND progress_percentage >= 100")->fetchColumn();
$savedJobsCount = (int)$db->query("SELECT COUNT(*) FROM saved_jobs WHERE user_id = $userId")->fetchColumn();
$savedToolsCount = (int)$db->query("SELECT COUNT(*) FROM saved_ai_tools WHERE user_id = $userId")->fetchColumn();

// 2. Fetch User's Enrolled Courses
$enrolledStmt = $db->prepare("
    SELECT e.*, c.title, c.slug, c.thumbnail, c.duration, c.total_lectures, cat.name AS category_name,
           (SELECT cl.id FROM course_lectures cl WHERE cl.course_id = c.id ORDER BY cl.order_index ASC LIMIT 1) AS first_lecture_id
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN course_categories cat ON c.category_id = cat.id
    WHERE e.user_id = :uid
    ORDER BY e.last_accessed_at DESC
    LIMIT 4
");
$enrolledStmt->execute([':uid' => $userId]);
$enrolledCourses = $enrolledStmt->fetchAll();

// 3. Fetch Saved Jobs
$jobsStmt = $db->prepare("
    SELECT j.*, cat.name AS category_name, cat.icon AS category_icon, sj.created_at AS saved_at
    FROM saved_jobs sj
    JOIN jobs j ON sj.job_id = j.id
    JOIN job_categories cat ON j.category_id = cat.id
    WHERE sj.user_id = :uid
    ORDER BY sj.created_at DESC
    LIMIT 3
");
$jobsStmt->execute([':uid' => $userId]);
$savedJobs = $jobsStmt->fetchAll();

// 4. Fetch Saved AI Tools
$toolsStmt = $db->prepare("
    SELECT t.*, cat.name AS category_name, cat.icon AS category_icon, st.created_at AS saved_at
    FROM saved_ai_tools st
    JOIN ai_tools t ON st.tool_id = t.id
    JOIN ai_tool_categories cat ON t.category_id = cat.id
    WHERE st.user_id = :uid
    ORDER BY st.created_at DESC
    LIMIT 3
");
$toolsStmt->execute([':uid' => $userId]);
$savedTools = $toolsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container section-padding">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Main Dashboard Content -->
        <div class="col-lg-9">
            <!-- Welcome Banner -->
            <div class="p-4 rounded-4 mb-4" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge bg-primary px-3 py-1 rounded-pill mb-2">Member Portal</span>
                        <h2 class="fs-3 fw-bold text-white mb-1">Welcome back, <?= e(current_user()['name']) ?>!</h2>
                        <p class="text-light opacity-75 small mb-0">
                            Continue your learning journey, apply to active IT positions, and utilize vetted AI tools.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="<?= APP_URL ?>/courses.php" class="btn btn-outline-light btn-sm me-2">
                            <i class="fa-solid fa-graduation-cap"></i> Browse Courses
                        </a>
                    </div>
                </div>
            </div>

            <!-- Analytical Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>
                            <span class="text-muted small fw-semibold">Active</span>
                        </div>
                        <h3 class="fs-3 fw-bold mb-0 text-dark"><?= $enrolledCount ?></h3>
                        <div class="small text-muted">Enrolled Courses</div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="stat-icon-wrapper bg-success-subtle text-success">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <span class="text-muted small fw-semibold">Done</span>
                        </div>
                        <h3 class="fs-3 fw-bold mb-0 text-dark"><?= $completedCount ?></h3>
                        <div class="small text-muted">Completed Courses</div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="stat-icon-wrapper bg-info-subtle text-info">
                                <i class="fa-solid fa-bookmark"></i>
                            </div>
                            <span class="text-muted small fw-semibold">Saved</span>
                        </div>
                        <h3 class="fs-3 fw-bold mb-0 text-dark"><?= $savedJobsCount ?></h3>
                        <div class="small text-muted">Bookmarked Jobs</div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="stat-icon-wrapper bg-purple-subtle text-purple" style="background-color: var(--accent-purple-light); color: var(--accent-purple);">
                                <i class="fa-solid fa-heart"></i>
                            </div>
                            <span class="text-muted small fw-semibold">Saved</span>
                        </div>
                        <h3 class="fs-3 fw-bold mb-0 text-dark"><?= $savedToolsCount ?></h3>
                        <div class="small text-muted">Favorite AI Tools</div>
                    </div>
                </div>
            </div>

            <!-- In-Progress Courses -->
            <div class="custom-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fs-5 fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-play text-primary me-2"></i> Continue Learning
                    </h4>
                    <a href="<?= APP_URL ?>/user/my-courses.php" class="small fw-semibold text-primary">View All Courses</a>
                </div>

                <?php if (!empty($enrolledCourses)): ?>
                    <div class="row g-3">
                        <?php foreach ($enrolledCourses as $course): 
                            $targetLectureId = $course['last_lecture_id'] ?: $course['first_lecture_id'];
                            $perc = (float)$course['progress_percentage'];
                        ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary-subtle text-primary"><?= e($course['category_name']) ?></span>
                                        <span class="small fw-bold text-dark"><?= round($perc) ?>%</span>
                                    </div>
                                    <h6 class="fw-bold mb-2 text-truncate" title="<?= e($course['title']) ?>">
                                        <?= e($course['title']) ?>
                                    </h6>
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $perc ?>%;" aria-valuenow="<?= $perc ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted"><i class="fa-regular fa-clock me-1"></i> <?= e($course['duration']) ?></span>
                                        <a href="<?= APP_URL ?>/watch.php?course_id=<?= $course['course_id'] ?><?= $targetLectureId ? '&lecture_id=' . $targetLectureId : '' ?>" class="btn btn-sm btn-primary-custom py-1 px-3">
                                            <?= $perc > 0 ? 'Resume' : 'Start' ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-graduation-cap fs-1 text-muted opacity-50 mb-2"></i>
                        <p class="text-muted small mb-3">You haven't enrolled in any courses yet.</p>
                        <a href="<?= APP_URL ?>/courses.php" class="btn btn-sm btn-primary-custom">
                            <i class="fa-solid fa-compass me-1"></i> Explore Free Courses
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Saved Jobs & Tools Row -->
            <div class="row g-4">
                <!-- Saved Jobs Preview -->
                <div class="col-md-6">
                    <div class="custom-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fs-6 fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-bookmark text-primary me-2"></i> Saved IT Jobs
                            </h5>
                            <a href="<?= APP_URL ?>/user/saved-jobs.php" class="small fw-semibold text-primary">All (<?= $savedJobsCount ?>)</a>
                        </div>

                        <?php if (!empty($savedJobs)): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($savedJobs as $job): ?>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="fw-bold mb-0 fs-6">
                                                <a href="<?= APP_URL ?>/jobs.php?id=<?= $job['id'] ?>" class="text-dark hover-primary">
                                                    <?= e($job['title']) ?>
                                                </a>
                                            </h6>
                                            <span class="badge bg-white text-dark border small"><?= e($job['work_mode']) ?></span>
                                        </div>
                                        <div class="small text-muted mb-2"><?= e($job['company_name']) ?> &bull; <?= e($job['location']) ?></div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small fw-semibold text-success"><?= e($job['salary_range']) ?></span>
                                            <a href="<?= APP_URL ?>/jobs.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-custom py-0 px-2 small">
                                                Details
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small my-3">No saved jobs. Bookmark jobs from the IT Jobs portal for easy access.</p>
                            <a href="<?= APP_URL ?>/jobs.php" class="btn btn-sm btn-outline-custom">Browse IT Jobs</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Saved AI Tools Preview -->
                <div class="col-md-6">
                    <div class="custom-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fs-6 fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-heart text-danger me-2"></i> Favorite AI Tools
                            </h5>
                            <a href="<?= APP_URL ?>/user/saved-tools.php" class="small fw-semibold text-primary">All (<?= $savedToolsCount ?>)</a>
                        </div>

                        <?php if (!empty($savedTools)): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($savedTools as $tool): ?>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="fw-bold mb-0 fs-6">
                                                <a href="<?= APP_URL ?>/ai-tools.php?id=<?= $tool['id'] ?>" class="text-dark hover-primary">
                                                    <?= e($tool['name']) ?>
                                                </a>
                                            </h6>
                                            <span class="badge bg-white text-dark border small"><?= e($tool['pricing_type']) ?></span>
                                        </div>
                                        <div class="small text-muted mb-2"><?= e($tool['category_name']) ?></div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-warning">
                                                <i class="fa-solid fa-star"></i> <?= number_format((float)$tool['user_rating'], 1) ?>
                                            </span>
                                            <a href="<?= e($tool['official_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-purple-custom py-0 px-2 small">
                                                Launch <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small my-3">No favorite AI tools yet. Bookmark tools you use regularly.</p>
                            <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-sm btn-outline-custom">Discover AI Tools</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
