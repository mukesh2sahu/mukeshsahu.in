<?php
/**
 * Admin IT Job Management - Add Job
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Add IT Job Posting';
$pageHeading = 'Create New IT Job Opening';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$errors = [];

$categories = $db->query("SELECT * FROM job_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$title = '';
$companyName = '';
$categoryId = $categories[0]['id'] ?? 1;
$location = '';
$workMode = 'Remote';
$experienceLevel = '1 - 3 Years';
$salaryRange = '';
$employmentType = 'Full-time';
$requiredSkills = '';
$description = '';
$eligibilityCriteria = '';
$applicationDeadline = '';
$applicationLink = '';
$isFeatured = 0;
$status = 'published';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $location = trim($_POST['location'] ?? '');
    $workMode = $_POST['work_mode'] ?? 'Remote';
    $experienceLevel = trim($_POST['experience_level'] ?? '');
    $salaryRange = trim($_POST['salary_range'] ?? '');
    $employmentType = $_POST['employment_type'] ?? 'Full-time';
    $requiredSkills = trim($_POST['required_skills'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eligibilityCriteria = trim($_POST['eligibility_criteria'] ?? '');
    $applicationDeadline = trim($_POST['application_deadline'] ?? '');
    $applicationLink = trim($_POST['application_link'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';

    if (empty($title)) $errors[] = 'Job title is required.';
    if (empty($companyName)) $errors[] = 'Company name is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (empty($salaryRange)) $errors[] = 'Salary range is required.';
    if (empty($requiredSkills)) $errors[] = 'Required skills are required.';
    if (empty($description)) $errors[] = 'Job description is required.';
    if (empty($applicationLink) || !filter_var($applicationLink, FILTER_VALIDATE_URL)) {
        $errors[] = 'A valid application link URL is required.';
    }

    if (empty($errors)) {
        $deadlineVal = !empty($applicationDeadline) ? $applicationDeadline : null;
        $postedBy = current_user_id();

        $stmt = $db->prepare("
            INSERT INTO jobs (category_id, title, company_name, location, work_mode, experience_level, salary_range, employment_type, required_skills, description, eligibility_criteria, application_deadline, application_link, is_featured, status, posted_by, created_at)
            VALUES (:cat, :title, :company, :loc, :mode, :exp, :salary, :type, :skills, :desc, :elig, :deadline, :link, :feat, :status, :posted, NOW())
        ");
        $stmt->execute([
            ':cat'      => $categoryId,
            ':title'    => $title,
            ':company'  => $companyName,
            ':loc'      => $location,
            ':mode'     => $workMode,
            ':exp'      => $experienceLevel,
            ':salary'   => $salaryRange,
            ':type'     => $employmentType,
            ':skills'   => $requiredSkills,
            ':desc'     => $description,
            ':elig'     => $eligibilityCriteria,
            ':deadline' => $deadlineVal,
            ':link'     => $applicationLink,
            ':feat'     => $isFeatured,
            ':status'   => $status,
            ':posted'   => $postedBy
        ]);
        $newJobId = (int)$db->lastInsertId();

        log_activity('Add Job', 'jobs', "Added new job '$title' at '$companyName' (#$newJobId)");
        set_flash('success', "Job opening for '$title' created successfully.");
        header('Location: ' . ADMIN_URL . '/jobs/index.php');
        exit;
    }
}
?>

<div class="custom-card p-4 p-md-5">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Publish New IT Job</h4>
            <span class="text-muted small">Fill out all job information to publish for platform members</span>
        </div>
        <a href="<?= ADMIN_URL ?>/jobs/index.php" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Job List
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

    <form method="POST" action="<?= ADMIN_URL ?>/jobs/add.php" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label for="title" class="form-label fw-semibold small">Job Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?= e($title) ?>" placeholder="e.g. Senior PHP Developer / Full Stack Engineer" required>
            </div>
            <div class="col-md-4">
                <label for="category_id" class="form-label fw-semibold small">Job Category <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="company_name" class="form-label fw-semibold small">Company Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="company_name" name="company_name" value="<?= e($companyName) ?>" placeholder="e.g. Acme Tech Solutions" required>
            </div>
            <div class="col-md-6">
                <label for="location" class="form-label fw-semibold small">Job Location <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="location" name="location" value="<?= e($location) ?>" placeholder="e.g. Bengaluru / Remote, India" required>
            </div>

            <div class="col-md-4">
                <label for="work_mode" class="form-label fw-semibold small">Work Mode</label>
                <select class="form-select" id="work_mode" name="work_mode">
                    <option value="Remote" <?= $workMode === 'Remote' ? 'selected' : '' ?>>Remote</option>
                    <option value="Hybrid" <?= $workMode === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                    <option value="On-site" <?= $workMode === 'On-site' ? 'selected' : '' ?>>On-site</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="experience_level" class="form-label fw-semibold small">Experience Required</label>
                <input type="text" class="form-control" id="experience_level" name="experience_level" value="<?= e($experienceLevel) ?>" placeholder="e.g. 2 - 5 Years">
            </div>
            <div class="col-md-4">
                <label for="salary_range" class="form-label fw-semibold small">Salary Range <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="salary_range" name="salary_range" value="<?= e($salaryRange) ?>" placeholder="e.g. ₹10,00,000 - ₹16,00,000 P.A." required>
            </div>

            <div class="col-md-4">
                <label for="employment_type" class="form-label fw-semibold small">Employment Type</label>
                <select class="form-select" id="employment_type" name="employment_type">
                    <option value="Full-time" <?= $employmentType === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                    <option value="Part-time" <?= $employmentType === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                    <option value="Contract" <?= $employmentType === 'Contract' ? 'selected' : '' ?>>Contract</option>
                    <option value="Internship" <?= $employmentType === 'Internship' ? 'selected' : '' ?>>Internship</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="application_deadline" class="form-label fw-semibold small">Application Deadline</label>
                <input type="date" class="form-control" id="application_deadline" name="application_deadline" value="<?= e($applicationDeadline) ?>">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold small">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>

            <div class="col-12">
                <label for="application_link" class="form-label fw-semibold small">External Application Link URL <span class="text-danger">*</span></label>
                <input type="url" class="form-control" id="application_link" name="application_link" value="<?= e($applicationLink) ?>" placeholder="https://company.com/careers/apply/123" required>
            </div>

            <div class="col-12">
                <label for="required_skills" class="form-label fw-semibold small">Required Skills (comma-separated) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="required_skills" name="required_skills" value="<?= e($requiredSkills) ?>" placeholder="e.g. PHP 8, MySQL, Laravel, Docker, REST APIs" required>
            </div>

            <div class="col-12">
                <label for="description" class="form-label fw-semibold small">Job Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Detailed job overview, daily responsibilities, and team structure..." required><?= e($description) ?></textarea>
            </div>

            <div class="col-12">
                <label for="eligibility_criteria" class="form-label fw-semibold small">Eligibility Criteria / Qualifications</label>
                <textarea class="form-control" id="eligibility_criteria" name="eligibility_criteria" rows="3" placeholder="Degree, certifications, years of hands-on experience..."><?= e($eligibilityCriteria) ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?= $isFeatured ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold small" for="is_featured">
                        <i class="fa-solid fa-star text-warning me-1"></i> Mark as Featured Job (Pin to homepage & top of listings)
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom px-4 py-2">
            <i class="fa-solid fa-plus me-2"></i> Publish Job Listing
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
