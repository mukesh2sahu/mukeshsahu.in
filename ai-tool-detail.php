<?php
/**
 * AI Tool Detail Page (Login Required)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth-check.php';

require_login();

$toolId = (int)($_GET['id'] ?? 0);
$db = get_db();
$userId = current_user_id();

$stmt = $db->prepare("
    SELECT t.*, cat.name AS category_name, cat.slug AS category_slug, cat.icon AS category_icon
    FROM ai_tools t
    JOIN ai_tool_categories cat ON t.category_id = cat.id
    WHERE t.id = :id AND t.status = 'active'
    LIMIT 1
");
$stmt->execute([':id' => $toolId]);
$tool = $stmt->fetch();

if (!$tool) {
    set_flash('danger', 'AI Tool not found or inactive.');
    header('Location: ' . APP_URL . '/ai-tools.php');
    exit;
}

$pageTitle = $tool['name'] . ' – AI Tool Overview';
$metaDesc = truncate_text($tool['description'], 160);

$isSaved = is_tool_saved($userId, $toolId);
$features = json_decode($tool['features_json'] ?? '[]', true) ?: [];

// Fetch related tools
$relStmt = $db->prepare("
    SELECT id, name, category_id, pricing_type, user_rating, official_url
    FROM ai_tools 
    WHERE category_id = :cid AND id != :id AND status = 'active'
    LIMIT 4
");
$relStmt->execute([':cid' => $tool['category_id'], ':id' => $toolId]);
$relatedTools = $relStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/ai-tools.php">AI Tools</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/ai-tools.php?category=<?= e($tool['category_slug']) ?>"><?= e($tool['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($tool['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="custom-card p-4 p-md-5 mb-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3 mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ai-tool-icon" style="width: 64px; height: 64px; font-size: 1.8rem;">
                            <i class="fa-solid <?= e($tool['category_icon'] ?? 'fa-robot') ?>"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="card-tag tag-blue"><?= e($tool['category_name']) ?></span>
                                <span class="card-tag <?= $tool['pricing_type'] === 'Free' ? 'tag-green' : ($tool['pricing_type'] === 'Freemium' ? 'tag-blue' : 'tag-orange') ?>">
                                    <?= e($tool['pricing_type']) ?>
                                </span>
                                <?php if ($tool['is_featured']): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-star me-1"></i> Featured</span>
                                <?php endif; ?>
                            </div>
                            <h1 class="fs-2 fw-bold text-dark mb-0"><?= e($tool['name']) ?></h1>
                        </div>
                    </div>

                    <button class="btn btn-outline-secondary btn-save-tool rounded-circle p-2 flex-shrink-0 <?= $isSaved ? 'text-danger' : '' ?>" data-tool-id="<?= $tool['id'] ?>" data-csrf="<?= csrf_token() ?>" title="Save Favorite" data-bs-toggle="tooltip">
                        <i class="<?= $isSaved ? 'fa-solid' : 'fa-regular' ?> fa-heart fs-5"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center gap-4 mb-4 p-3 bg-light rounded-3 border">
                    <div>
                        <div class="small text-muted mb-1">Community Rating</div>
                        <div class="text-warning fs-5">
                            <i class="fa-solid fa-star"></i>
                            <strong class="text-dark ms-1"><?= number_format((float)$tool['user_rating'], 1) ?></strong>
                            <span class="text-muted small">/ 5.0</span>
                        </div>
                    </div>
                    <div class="border-start ps-4">
                        <div class="small text-muted mb-1">Pricing Model</div>
                        <div class="fw-bold text-dark fs-6"><?= e($tool['pricing_type']) ?> Plan Available</div>
                    </div>
                    <div class="border-start ps-4">
                        <div class="small text-muted mb-1">Status</div>
                        <span class="badge bg-success-subtle text-success">Verified Active</span>
                    </div>
                </div>

                <h5 class="fs-5 fw-bold text-dark mb-3">Overview & Capabilities</h5>
                <div class="text-secondary mb-4" style="line-height: 1.8;">
                    <?= nl2br(e($tool['description'])) ?>
                </div>

                <?php if (!empty($features)): ?>
                    <h5 class="fs-6 fw-bold text-dark mb-3">Key Features & Highlights</h5>
                    <div class="row g-2 mb-4">
                        <?php foreach ($features as $feat): ?>
                            <div class="col-sm-6">
                                <div class="p-2 px-3 bg-light rounded-2 border d-flex align-items-center gap-2 small">
                                    <i class="fa-solid fa-circle-check text-success flex-shrink-0"></i>
                                    <span class="text-dark fw-semibold"><?= e($feat) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="p-4 bg-purple-subtle rounded-3 border border-purple-subtle d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-4" style="background-color: var(--accent-purple-light);">
                    <div>
                        <div class="fw-bold text-dark fs-6">Ready to use <?= e($tool['name']) ?>?</div>
                        <div class="small text-muted">Visit the official platform to sign up or begin using the service.</div>
                    </div>
                    <a href="<?= e($tool['official_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-purple-custom btn-lg">
                        Visit Official Website <i class="fa-solid fa-arrow-up-right-from-square ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar / Related AI Tools -->
        <div class="col-lg-4">
            <div class="custom-card p-4">
                <h5 class="fs-6 fw-bold text-dark mb-3">Similar AI Tools</h5>
                <?php if (!empty($relatedTools)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($relatedTools as $rel): ?>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold mb-0 fs-6">
                                        <a href="<?= APP_URL ?>/ai-tool-detail.php?id=<?= $rel['id'] ?>" class="text-dark hover-primary">
                                            <?= e($rel['name']) ?>
                                        </a>
                                    </h6>
                                    <span class="badge bg-white text-dark border small"><?= e($rel['pricing_type']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small pt-2">
                                    <span class="text-warning">
                                        <i class="fa-solid fa-star"></i> <?= number_format((float)$rel['user_rating'], 1) ?>
                                    </span>
                                    <a href="<?= e($rel['official_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-primary fw-semibold">
                                        Visit <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">No other tools currently listed in this category.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
