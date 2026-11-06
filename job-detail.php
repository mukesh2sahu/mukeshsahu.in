<?php
/**
 * IT Job Details Page (Login Required)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth-check.php';

// Strict member-only access check
require_login();

$jobId = (int)($_GET['id'] ?? 0);
$db = get_db();

$stmt = $db->prepare("
    SELECT j.*, cat.name AS category_name, cat.icon AS category_icon, cat.slug AS category_slug
    FROM jobs j
    JOIN job_categories cat ON j.category_id = cat.id
    WHERE j.id = :id AND j.status = 'published'
    LIMIT 1
");
$stmt->execute([':id' => $jobId]);
$job = $stmt->fetch();

if (!$job) {
    set_flash('danger', 'The requested job posting could not be found or has expired.');
    header('Location: ' . APP_URL . '/jobs.php');
    exit;
}

$pageTitle = $job['title'] . ' at ' . $job['company_name'];
$metaDesc = truncate_text($job['description'], 160);

$userId = current_user_id();
$isSaved = is_job_saved($userId, $jobId);

// Fetch related jobs in the same category
$relStmt = $db->prepare("
    SELECT id, title, company_name, location, work_mode, salary_range, created_at 
    FROM jobs 
    WHERE category_id = :cat_id AND id != :id AND status = 'published'
    ORDER BY created_at DESC 
    LIMIT 3
");
$relStmt->execute([':cat_id' => $job['category_id'], ':id' => $jobId]);
$relatedJobs = $relStmt->fetchAll();

// Skills array
$skillsArray = array_map('trim', explode(',', $job['required_skills']));

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/jobs.php">IT Jobs</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/jobs.php?category=<?= e($job['category_slug']) ?>"><?= e($job['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($job['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Job Description Column -->
        <div class="col-lg-8">
            <div class="custom-card p-4 p-md-5 mb-4">
                <!-- Header block -->
                <div class="d-flex flex-column flex-sm-row align-items-start justify-content-between gap-3 mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-start gap-3">
                        <div class="job-company-logo" style="width: 64px; height: 64px; font-size: 1.6rem;">
                            <i class="fa-solid <?= e($job['category_icon'] ?? 'fa-briefcase') ?>"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="card-tag tag-blue"><?= e($job['category_name']) ?></span>
                                <span class="card-tag <?= $job['work_mode'] === 'Remote' ? 'tag-green' : 'tag-orange' ?>">
                                    <?= e($job['work_mode']) ?>
                                </span>
                                <?php if ($job['is_featured']): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-star me-1"></i> Featured</span>
                                <?php endif; ?>
                            </div>
                            <h1 class="fs-3 fw-bold text-dark mb-1"><?= e($job['title']) ?></h1>
                            <div class="fw-semibold text-secondary">
                                <?= e($job['company_name']) ?> &bull; <?= e($job['location']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Bookmark Button -->
                    <button class="btn btn-outline-secondary btn-save-job rounded-circle p-2 flex-shrink-0 <?= $isSaved ? 'text-primary' : '' ?>" data-job-id="<?= $job['id'] ?>" data-csrf="<?= csrf_token() ?>" title="Save Job" data-bs-toggle="tooltip">
                        <i class="<?= $isSaved ? 'fa-solid' : 'fa-regular' ?> fa-bookmark fs-5"></i>
                    </button>
                </div>

                <!-- Key Specs Grid -->
                <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                    <div class="col-6 col-sm-3">
                        <div class="small text-muted mb-1"><i class="fa-solid fa-indian-rupee-sign text-success me-1"></i> Salary Range</div>
                        <div class="fw-bold text-dark small"><i class="fa-solid fa-indian-rupee-sign text-success me-1"></i><?= e(format_salary($job['salary_range'])) ?></div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="small text-muted mb-1"><i class="fa-solid fa-briefcase text-primary me-1"></i> Experience</div>
                        <div class="fw-bold text-dark small"><?= e($job['experience_level']) ?></div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="small text-muted mb-1"><i class="fa-solid fa-clock text-info me-1"></i> Job Type</div>
                        <div class="fw-bold text-dark small"><?= e($job['employment_type']) ?></div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="small text-muted mb-1"><i class="fa-regular fa-calendar-xmark text-danger me-1"></i> Deadline</div>
                        <div class="fw-bold text-dark small"><?= !empty($job['application_deadline']) ? format_date($job['application_deadline']) : 'Open Until Filled' ?></div>
                    </div>
                </div>

                <!-- Skills Section -->
                <div class="mb-4">
                    <h5 class="fs-6 fw-bold text-dark mb-2">Required Skills & Technologies</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($skillsArray as $skill): if (!empty($skill)): ?>
                            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-semibold">
                                <?= e($skill) ?>
                            </span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

                <!-- Full Job Description -->
                <div class="mb-4">
                    <h5 class="fs-5 fw-bold text-dark mb-3">Job Description</h5>
                    <div class="text-secondary" style="line-height: 1.8;">
                        <?= nl2br(e($job['description'])) ?>
                    </div>
                </div>

                <!-- Eligibility Criteria -->
                <?php if (!empty($job['eligibility_criteria'])): ?>
                    <div class="mb-4 pt-3 border-top">
                        <h5 class="fs-5 fw-bold text-dark mb-3">Eligibility Criteria & Qualifications</h5>
                        <div class="text-secondary" style="line-height: 1.8;">
                            <?= nl2br(e($job['eligibility_criteria'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Apply Actions -->
                <div class="p-4 bg-primary-subtle rounded-3 border border-primary-subtle d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-4">
                    <div>
                        <div class="fw-bold text-dark fs-6">Interested in this position?</div>
                        <div class="small text-muted">Apply directly on the employer's official portal.</div>
                    </div>
                    <a href="<?= e($job['application_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary-custom btn-lg">
                        Apply Now on Company Portal <i class="fa-solid fa-arrow-up-right-from-square ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar / Company Info & Related Jobs -->
        <div class="col-lg-4">
            <!-- Company Card -->
            <div class="custom-card p-4 mb-4">
                <h5 class="fs-6 fw-bold text-dark mb-3">Company Overview</h5>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="job-company-logo">
                        <i class="fa-solid <?= e($job['category_icon'] ?? 'fa-building') ?>"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><?= e($job['company_name']) ?></h6>
                        <span class="small text-muted"><?= e($job['location']) ?></span>
                    </div>
                </div>
                <div class="small text-secondary mb-3">
                    Verified job post curated on the MukeshSahu platform.
                </div>
                <div class="small text-muted border-top pt-2">
                    <i class="fa-regular fa-clock me-1"></i> Posted <?= time_ago($job['created_at']) ?>
                </div>
            </div>

            <!-- Related Jobs -->
            <?php if (!empty($relatedJobs)): ?>
                <div class="custom-card p-4">
                    <h5 class="fs-6 fw-bold text-dark mb-3">Related Opportunities</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($relatedJobs as $rel): ?>
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold mb-1 fs-6">
                                    <a href="<?= APP_URL ?>/job-detail.php?id=<?= $rel['id'] ?>" class="text-dark hover-primary">
                                        <?= e($rel['title']) ?>
                                    </a>
                                </h6>
                                <div class="small text-muted mb-2"><?= e($rel['company_name']) ?> &bull; <?= e($rel['location']) ?></div>
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span class="fw-semibold text-success"><i class="fa-solid fa-indian-rupee-sign me-1"></i><?= e(format_salary($rel['salary_range'])) ?></span>
                                    <span class="badge bg-white text-dark border"><?= e($rel['work_mode']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
