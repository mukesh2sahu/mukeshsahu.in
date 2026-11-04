<?php
/**
 * Admin Blog Management - Index
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage Blogs';
$pageHeading = 'Technology Articles & Tutorials Management';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Delete Blog
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $blogId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $blogId > 0) {
        $del = $db->prepare("DELETE FROM blogs WHERE id = :id");
        $del->execute([':id' => $blogId]);
        log_activity('Delete Blog', 'blogs', 'Deleted blog article #' . $blogId);
        set_flash('success', 'Article removed successfully.');
    } else {
        set_flash('danger', 'Invalid security token.');
    }
    header('Location: ' . ADMIN_URL . '/blogs/index.php');
    exit;
}

// Fetch all blogs
$stmt = $db->query("
    SELECT b.*, cat.name AS category_name, u.name AS author_name 
    FROM blogs b
    JOIN blog_categories cat ON b.category_id = cat.id
    LEFT JOIN users u ON b.author_id = u.id
    ORDER BY b.id DESC
");
$blogs = $stmt->fetchAll();
?>

<div class="custom-card p-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-1">Technology Articles</h4>
            <p class="text-muted small mb-0">Total <?= count($blogs) ?> engineering articles published</p>
        </div>
        <a href="<?= ADMIN_URL ?>/blogs/add.php" class="btn btn-primary-custom btn-sm">
            <i class="fa-solid fa-pen-nib me-1"></i> Write New Article
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#ID</th>
                    <th>Article Title & Slug</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th>Published Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blogs as $b): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $b['id'] ?></td>
                        <td>
                            <div class="fw-bold text-dark">
                                <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($b['slug']) ?>" target="_blank" class="text-dark hover-primary">
                                    <?= e($b['title']) ?>
                                </a>
                            </div>
                            <span class="small text-muted font-monospace">/<?= e($b['slug']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-purple-subtle text-purple border small" style="background-color: var(--accent-purple-light); color: var(--accent-purple);">
                                <?= e($b['category_name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="small text-secondary"><?= e($b['author_name'] ?? 'Mukesh Sahu') ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><i class="fa-regular fa-eye me-1"></i> <?= (int)$b['views_count'] ?></span>
                        </td>
                        <td>
                            <?php if ($b['status'] === 'published'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                            <?php elseif ($b['status'] === 'draft'): ?>
                                <span class="badge bg-secondary-subtle text-secondary border">Draft</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border">Archived</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="small text-muted"><?= format_date($b['published_at']) ?></span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($b['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="View Live Article">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/blogs/edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Article">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/blogs/index.php?action=delete&id=<?= $b['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" title="Delete Article" onclick="return confirm('Delete this blog post permanently?');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
