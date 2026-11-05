<?php
/**
 * User Saved Jobs
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Saved IT Jobs';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_login();

$userId = current_user_id();
$db = get_db();

// Handle direct removal if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $jobId = (int)($_POST['job_id'] ?? 0);
        $del = $db->prepare("DELETE FROM saved_jobs WHERE user_id = :uid AND job_id = :jid");
        $del->execute([':uid' => $userId, ':jid' => $jobId]);
        set_flash('info', 'Job removed from your saved list.');
        header('Location: ' . APP_URL . '/user/saved-jobs.php');
        exit;
    }
}

// Fetch all saved jobs
$stmt = $db->prepare("
    SELECT j.*, cat.name AS category_name, cat.icon AS category_icon, sj.created_at AS saved_at
    FROM saved_jobs sj
    JOIN jobs j ON sj.job_id = j.id
    JOIN job_categories cat ON j.category_id = cat.id
    WHERE sj.user_id = :uid
    ORDER BY sj.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$savedJobs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-3 col-xxl-2">
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Saved Jobs Content -->
        <div class="col-lg-9 col-xl-9 col-xxl-10">
            <div class="custom-card card-static p-4 p-md-5 w-100">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-3 border-bottom gap-3">
                    <div>
                        <h3 class="fs-4 fw-bold mb-1">Saved IT Jobs</h3>
                        <p class="text-muted small mb-0">Review bookmarked job opportunities and apply directly</p>
                    </div>
                    <a href="<?= APP_URL ?>/jobs.php" class="btn btn-outline-custom btn-sm">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Search More Jobs
                    </a>
                </div>

                <?php if (!empty($savedJobs)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($savedJobs as $job): ?>
                            <div class="p-4 bg-light rounded-3 border">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="job-company-logo flex-shrink-0">
                                            <i class="fa-solid <?= e($job['category_icon'] ?? 'fa-briefcase') ?>"></i>
                                        </div>
                                        <div>
                                            <h5 class="fs-6 fw-bold mb-1">
                                                <a href="<?= APP_URL ?>/jobs.php?id=<?= $job['id'] ?>" class="text-dark hover-primary">
                                                    <?= e($job['title']) ?>
                                                </a>
                                            </h5>
                                            <div class="small text-muted mb-2">
                                                <strong><?= e($job['company_name']) ?></strong> &bull; <?= e($job['location']) ?> &bull; <span class="badge bg-white text-dark border"><?= e($job['work_mode']) ?></span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="badge bg-white text-success border">
                                                    <i class="fa-solid fa-indian-rupee-sign"></i> <?= e($job['salary_range']) ?>
                                                </span>
                                                <span class="badge bg-white text-primary border">
                                                    <i class="fa-solid fa-briefcase"></i> <?= e($job['experience_level']) ?>
                                                </span>
                                                <span class="badge bg-white text-secondary border">
                                                    <?= e($job['employment_type']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <a href="<?= APP_URL ?>/jobs.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-custom">
                                            Details
                                        </a>
                                        <a href="<?= e($job['application_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary-custom">
                                            Apply <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                        </a>
                                        <form method="POST" action="<?= APP_URL ?>/user/saved-jobs.php" class="d-inline" onsubmit="return confirm('Remove this job from your bookmarks?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Bookmark">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="small text-muted pt-2 border-top d-flex justify-content-between align-items-center">
                                    <span>Deadline: <strong><?= !empty($job['application_deadline']) ? format_date($job['application_deadline']) : 'Open' ?></strong></span>
                                    <span>Saved on: <?= format_date($job['saved_at']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="stat-icon-wrapper bg-light text-muted mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="fa-solid fa-bookmark fs-2"></i>
                        </div>
                        <h4 class="fs-5 fw-bold text-dark mb-2">No Saved Jobs</h4>
                        <p class="text-muted small mb-4" style="max-width: 450px; margin: 0 auto;">
                            Save openings that interest you to apply later and track their deadlines.
                        </p>
                        <a href="<?= APP_URL ?>/jobs.php" class="btn btn-primary-custom">
                            <i class="fa-solid fa-briefcase me-2"></i> Browse Active IT Jobs
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
