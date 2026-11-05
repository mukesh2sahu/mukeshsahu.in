<?php
/**
 * Admin AI Tools Management - Index
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Manage AI Tools';
$pageHeading = 'AI Platforms & Tools Directory Management';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Delete Tool
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $toolId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $toolId > 0) {
        $del = $db->prepare("DELETE FROM ai_tools WHERE id = :id");
        $del->execute([':id' => $toolId]);
        log_activity('Delete AI Tool', 'ai_tools', 'Deleted AI tool #' . $toolId);
        set_flash('success', 'AI tool removed successfully.');
    } else {
        set_flash('danger', 'Invalid security token.');
    }
    header('Location: ' . ADMIN_URL . '/ai-tools/index.php');
    exit;
}

// Handle Toggle Featured
if (isset($_GET['action']) && $_GET['action'] === 'toggle_featured') {
    $toolId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $toolId > 0) {
        $upd = $db->prepare("UPDATE ai_tools SET is_featured = NOT is_featured WHERE id = :id");
        $upd->execute([':id' => $toolId]);
        log_activity('Toggle Featured AI Tool', 'ai_tools', 'Toggled featured state for tool #' . $toolId);
        set_flash('success', 'AI tool featured status updated.');
    }
    header('Location: ' . ADMIN_URL . '/ai-tools/index.php');
    exit;
}

// Fetch all AI tools
$stmt = $db->query("
    SELECT t.*, cat.name as category_name, cat.icon as category_icon,
           (SELECT COUNT(*) FROM saved_ai_tools st WHERE st.tool_id = t.id) as saved_count
    FROM ai_tools t
    JOIN ai_tool_categories cat ON t.category_id = cat.id
    ORDER BY t.id DESC
");
$tools = $stmt->fetchAll();
?>

<div class="custom-card p-3" style="height: auto;">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 pb-2 border-bottom gap-2">
        <div>
            <h4 class="fs-6 fw-bold text-dark mb-0">AI Tools Directory</h4>
            <span class="text-muted small" style="font-size: 0.75rem;">Total <?= count($tools) ?> artificial intelligence platforms curated</span>
        </div>
        <a href="<?= ADMIN_URL ?>/ai-tools/add.php" class="btn btn-purple-custom btn-sm py-1 px-3">
            <i class="fa-solid fa-plus me-1"></i> Add New AI Tool
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#ID</th>
                    <th>Tool Name & Category</th>
                    <th>Pricing</th>
                    <th>Rating</th>
                    <th>Official URL</th>
                    <th>Featured</th>
                    <th>Saved</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tools as $t): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $t['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="ai-tool-icon" style="width: 38px; height: 38px; font-size: 1.1rem;">
                                    <i class="fa-solid <?= e($t['category_icon'] ?? 'fa-robot') ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">
                                        <a href="<?= APP_URL ?>/ai-tool-detail.php?id=<?= $t['id'] ?>" target="_blank" class="text-dark hover-primary">
                                            <?= e($t['name']) ?>
                                        </a>
                                    </div>
                                    <span class="small text-muted"><?= e($t['category_name']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-white text-dark border small"><?= e($t['pricing_type']) ?></span>
                        </td>
                        <td>
                            <span class="small text-warning">
                                <i class="fa-solid fa-star"></i> <?= number_format((float)$t['user_rating'], 1) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= e($t['official_url']) ?>" target="_blank" class="small text-secondary text-truncate d-inline-block" style="max-width: 140px;">
                                <?= e($t['official_url']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= ADMIN_URL ?>/ai-tools/index.php?action=toggle_featured&id=<?= $t['id'] ?>&token=<?= csrf_token() ?>" class="text-decoration-none">
                                <?php if ($t['is_featured']): ?>
                                    <i class="fa-solid fa-star text-warning fs-5"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star text-muted fs-5"></i>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= (int)$t['saved_count'] ?></span>
                        </td>
                        <td>
                            <?php if ($t['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= ADMIN_URL ?>/ai-tools/edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Tool">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/ai-tools/index.php?action=delete&id=<?= $t['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" title="Delete Tool" onclick="return confirm('Delete this AI tool?');">
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
