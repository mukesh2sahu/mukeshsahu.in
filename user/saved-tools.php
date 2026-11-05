<?php
/**
 * User Saved AI Tools
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Favorite AI Tools';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_login();

$userId = current_user_id();
$db = get_db();

// Handle direct removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $toolId = (int)($_POST['tool_id'] ?? 0);
        $del = $db->prepare("DELETE FROM saved_ai_tools WHERE user_id = :uid AND tool_id = :tid");
        $del->execute([':uid' => $userId, ':tid' => $toolId]);
        set_flash('info', 'AI Tool removed from your favorites.');
        header('Location: ' . APP_URL . '/user/saved-tools.php');
        exit;
    }
}

// Fetch saved tools
$stmt = $db->prepare("
    SELECT t.*, cat.name AS category_name, cat.icon AS category_icon, st.created_at AS saved_at
    FROM saved_ai_tools st
    JOIN ai_tools t ON st.tool_id = t.id
    JOIN ai_tool_categories cat ON t.category_id = cat.id
    WHERE st.user_id = :uid
    ORDER BY st.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$savedTools = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-3 col-xxl-2">
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Saved Tools Content -->
        <div class="col-lg-9 col-xl-9 col-xxl-10">
            <div class="custom-card card-static p-4 p-md-5 w-100">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-3 border-bottom gap-3">
                    <div>
                        <h3 class="fs-4 fw-bold mb-1">Favorite AI Tools</h3>
                        <p class="text-muted small mb-0">Your bookmarked artificial intelligence platforms and assistants</p>
                    </div>
                    <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-purple-custom btn-sm">
                        <i class="fa-solid fa-robot me-1"></i> Discover AI Tools
                    </a>
                </div>

                <?php if (!empty($savedTools)): ?>
                    <div class="row g-3">
                        <?php foreach ($savedTools as $tool): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="ai-tool-icon">
                                            <i class="fa-solid <?= e($tool['category_icon'] ?? 'fa-robot') ?>"></i>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-white text-dark border"><?= e($tool['pricing_type']) ?></span>
                                            <form method="POST" action="<?= APP_URL ?>/user/saved-tools.php" class="d-inline" onsubmit="return confirm('Remove this tool from favorites?');">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="tool_id" value="<?= $tool['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remove Favorite">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <h5 class="fs-6 fw-bold mb-1">
                                        <a href="<?= APP_URL ?>/ai-tools.php?id=<?= $tool['id'] ?>" class="text-dark hover-primary">
                                            <?= e($tool['name']) ?>
                                        </a>
                                    </h5>
                                    <div class="small text-muted mb-2"><?= e($tool['category_name']) ?></div>
                                    <p class="text-muted small mb-3 flex-grow-1">
                                        <?= e(truncate_text($tool['description'], 90)) ?>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="small text-warning">
                                            <i class="fa-solid fa-star"></i> <?= number_format((float)$tool['user_rating'], 1) ?>
                                        </span>
                                        <a href="<?= e($tool['official_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-purple-custom py-1 px-3">
                                            Launch Tool <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="stat-icon-wrapper bg-light text-muted mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="fa-solid fa-heart fs-2"></i>
                        </div>
                        <h4 class="fs-5 fw-bold text-dark mb-2">No Favorite Tools Saved</h4>
                        <p class="text-muted small mb-4" style="max-width: 450px; margin: 0 auto;">
                            Browse the AI directory and bookmark ChatGPT, Claude, GitHub Copilot, Cursor, and other tools.
                        </p>
                        <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-purple-custom">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Discover AI Tools
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
