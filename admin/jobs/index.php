<?php
/**
 * Admin IT Job Management - Index
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage IT Jobs';
$pageHeading = 'IT Job Openings Management';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $deleteId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $deleteId > 0) {
        $del = $db->prepare("DELETE FROM jobs WHERE id = :id");
        $del->execute([':id' => $deleteId]);
        log_activity('Delete Job', 'jobs', 'Deleted job posting ID: ' . $deleteId);
        set_flash('success', 'Job posting deleted successfully.');
    } else {
        set_flash('danger', 'Invalid security token.');
    }
    header('Location: ' . ADMIN_URL . '/jobs/index.php');
    exit;
}

// Handle Toggle Featured
if (isset($_GET['action']) && $_GET['action'] === 'toggle_featured') {
    $jobId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $jobId > 0) {
        $upd = $db->prepare("UPDATE jobs SET is_featured = NOT is_featured WHERE id = :id");
        $upd->execute([':id' => $jobId]);
        log_activity('Toggle Featured Job', 'jobs', 'Toggled featured state for job #' . $jobId);
        set_flash('success', 'Job featured state updated.');
    }
    header('Location: ' . ADMIN_URL . '/jobs/index.php');
    exit;
}

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $jobId = (int)($_GET['id'] ?? 0);
    $status = $_GET['status'] ?? 'published';
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && in_array($status, ['published', 'draft', 'expired'])) {
        $upd = $db->prepare("UPDATE jobs SET status = :status WHERE id = :id");
        $upd->execute([':status' => $status, ':id' => $jobId]);
        log_activity('Update Job Status', 'jobs', "Changed status of job #$jobId to $status");
        set_flash('success', "Job status updated to $status.");
    }
    header('Location: ' . ADMIN_URL . '/jobs/index.php');
    exit;
}

// Fetch all jobs
$stmt = $db->query("
    SELECT j.*, cat.name AS category_name, cat.icon AS category_icon,
           (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id) as saved_count
    FROM jobs j
    JOIN job_categories cat ON j.category_id = cat.id
    ORDER BY j.id DESC
");
$jobs = $stmt->fetchAll();
?>

<div class="custom-card p-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-1">Job Postings Directory</h4>
            <p class="text-muted small mb-0">Total <?= count($jobs) ?> IT positions published or drafted</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= ADMIN_URL ?>/jobs/categories.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-tags me-1"></i> Job Categories
            </a>
            <a href="<?= ADMIN_URL ?>/jobs/add.php" class="btn btn-primary-custom btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Add New Job
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#ID</th>
                    <th>Job Title & Company</th>
                    <th>Category</th>
                    <th>Mode & Location</th>
                    <th>Salary Range</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Saved</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $j['id'] ?></td>
                        <td>
                            <div class="fw-bold text-dark">
                                <a href="<?= APP_URL ?>/job-detail.php?id=<?= $j['id'] ?>" target="_blank" class="text-dark hover-primary">
                                    <?= e($j['title']) ?>
                                </a>
                            </div>
                            <div class="small text-muted"><?= e($j['company_name']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border small">
                                <i class="fa-solid <?= e($j['category_icon']) ?> me-1"></i> <?= e($j['category_name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-white text-dark border small"><?= e($j['work_mode']) ?></span>
                            <div class="small text-muted"><?= e(truncate_text($j['location'], 20)) ?></div>
                        </td>
                        <td>
                            <span class="small fw-semibold text-success"><?= e($j['salary_range']) ?></span>
                        </td>
                        <td>
                            <?php if ($j['status'] === 'published'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                            <?php elseif ($j['status'] === 'draft'): ?>
                                <span class="badge bg-secondary-subtle text-secondary border">Draft</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border">Expired</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= ADMIN_URL ?>/jobs/index.php?action=toggle_featured&id=<?= $j['id'] ?>&token=<?= csrf_token() ?>" class="text-decoration-none">
                                <?php if ($j['is_featured']): ?>
                                    <i class="fa-solid fa-star text-warning fs-5" title="Featured (Click to unfeature)"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star text-muted fs-5" title="Not Featured (Click to feature)"></i>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= (int)$j['saved_count'] ?></span>
                        </td>
                        <td>
                            <span class="small text-muted"><?= format_date($j['created_at']) ?></span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                    <li>
                                        <a class="dropdown-item" href="<?= ADMIN_URL ?>/jobs/edit.php?id=<?= $j['id'] ?>">
                                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Job
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= APP_URL ?>/job-detail.php?id=<?= $j['id'] ?>" target="_blank">
                                            <i class="fa-solid fa-eye me-2 text-info"></i> View Live
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= ADMIN_URL ?>/jobs/index.php?action=toggle_status&status=<?= $j['status'] === 'published' ? 'draft' : 'published' ?>&id=<?= $j['id'] ?>&token=<?= csrf_token() ?>">
                                            <i class="fa-solid fa-power-off me-2 text-secondary"></i> Set as <?= $j['status'] === 'published' ? 'Draft' : 'Published' ?>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?= ADMIN_URL ?>/jobs/index.php?action=delete&id=<?= $j['id'] ?>&token=<?= csrf_token() ?>" onclick="return confirm('Delete this job listing permanently?');">
                                            <i class="fa-solid fa-trash-can me-2"></i> Delete Job
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
