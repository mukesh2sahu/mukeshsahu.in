<?php
/**
 * Admin IT Job Management - Categories
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage Job Categories';
$pageHeading = 'IT Job Categories';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$errors = [];

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $catId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $catId > 0) {
        // Check if jobs are attached
        $countStmt = $db->prepare("SELECT COUNT(*) FROM jobs WHERE category_id = :cid");
        $countStmt->execute([':cid' => $catId]);
        if ((int)$countStmt->fetchColumn() > 0) {
            set_flash('danger', 'Cannot delete this category because active job postings are currently assigned to it.');
        } else {
            $del = $db->prepare("DELETE FROM job_categories WHERE id = :id");
            $del->execute([':id' => $catId]);
            log_activity('Delete Job Category', 'jobs', 'Deleted category #' . $catId);
            set_flash('success', 'Job category removed successfully.');
        }
    }
    header('Location: ' . ADMIN_URL . '/jobs/categories.php');
    exit;
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-briefcase');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) $errors[] = 'Category name is required.';
    if (empty($slug)) $slug = slugify($name);

    if (empty($errors)) {
        // Check duplicate slug
        $check = $db->prepare("SELECT id FROM job_categories WHERE slug = :slug LIMIT 1");
        $check->execute([':slug' => $slug]);
        if ($check->fetch()) {
            $errors[] = 'A category with this slug already exists.';
        } else {
            $ins = $db->prepare("INSERT INTO job_categories (name, slug, icon, description, status, created_at) VALUES (:name, :slug, :icon, :desc, 'active', NOW())");
            $ins->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':icon' => $icon,
                ':desc' => $description
            ]);
            log_activity('Add Job Category', 'jobs', "Added category $name");
            set_flash('success', "Category '$name' added successfully.");
            header('Location: ' . ADMIN_URL . '/jobs/categories.php');
            exit;
        }
    }
}

// Fetch categories with job counts
$cats = $db->query("
    SELECT c.*, COUNT(j.id) as job_count
    FROM job_categories c
    LEFT JOIN jobs j ON c.id = j.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();
?>

<div class="row g-4">
    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="custom-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h4 class="fs-5 fw-bold text-dark mb-0">Job Categories</h4>
                    <span class="text-muted small">Organize tech roles and filters</span>
                </div>
                <a href="<?= ADMIN_URL ?>/jobs/index.php" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Jobs
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Icon</th>
                            <th>Category Name</th>
                            <th>Slug</th>
                            <th>Active Jobs</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cats as $c): ?>
                            <tr>
                                <td>
                                    <div class="job-company-logo" style="width: 36px; height: 36px; font-size: 1rem;">
                                        <i class="fa-solid <?= e($c['icon']) ?>"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($c['name']) ?></div>
                                    <div class="small text-muted"><?= e(truncate_text($c['description'] ?? '', 40)) ?></div>
                                </td>
                                <td><code><?= e($c['slug']) ?></code></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border"><?= (int)$c['job_count'] ?> jobs</span>
                                </td>
                                <td class="text-end">
                                    <?php if ((int)$c['job_count'] === 0): ?>
                                        <a href="<?= ADMIN_URL ?>/jobs/categories.php?action=delete&id=<?= $c['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this unused category?');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete category with jobs assigned">
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="custom-card p-4">
            <h5 class="fs-6 fw-bold text-dark mb-3">Add New Category</h5>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger small p-2 mb-3">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= ADMIN_URL ?>/jobs/categories.php" novalidate>
                <?= csrf_field(); ?>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Flutter Developer" required>
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label small fw-semibold">Slug (optional)</label>
                    <input type="text" class="form-control" id="slug" name="slug" placeholder="e.g. flutter-developer">
                </div>

                <div class="mb-3">
                    <label for="icon" class="form-label small fw-semibold">FontAwesome Icon Class</label>
                    <input type="text" class="form-control" id="icon" name="icon" value="fa-laptop-code" placeholder="e.g. fa-laptop-code">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of this domain..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2 justify-content-center">
                    <i class="fa-solid fa-plus me-1"></i> Add Category
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
