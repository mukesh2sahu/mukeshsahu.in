<?php
/**
 * AI Tools Directory (Login Required)
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth-check.php';

// Member-only access check
require_login();

// Route to detail if id is present
if (!empty($_GET['id'])) {
    header('Location: ' . APP_URL . '/ai-tool-detail.php?id=' . (int)$_GET['id']);
    exit;
}

$pageTitle = 'Curated AI Tools Directory';
$metaDesc = 'Discover top artificial intelligence platforms, coding assistants, LLM tools, and writing utilities.';

$db = get_db();
$userId = current_user_id();

// 1. Fetch Categories for filter
$catStmt = $db->query("SELECT id, name, slug, icon FROM ai_tool_categories WHERE status = 'active' ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// 2. Query Parameters
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$pricing = trim($_GET['pricing'] ?? '');

$where = ["t.status = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(t.name LIKE :search OR t.description LIKE :search OR t.features_json LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($categorySlug !== '') {
    $where[] = "cat.slug = :catslug";
    $params[':catslug'] = $categorySlug;
}

if ($pricing !== '') {
    $where[] = "t.pricing_type = :pricing";
    $params[':pricing'] = $pricing;
}

$whereClause = implode(' AND ', $where);

// Total Count
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM ai_tools t 
    JOIN ai_tool_categories cat ON t.category_id = cat.id 
    WHERE $whereClause
");
$countStmt->execute($params);
$totalTools = (int)$countStmt->fetchColumn();

// Fetch AI Tools
$toolsStmt = $db->prepare("
    SELECT t.*, cat.name AS category_name, cat.icon AS category_icon, cat.slug AS category_slug,
           (SELECT COUNT(*) FROM saved_ai_tools st WHERE st.tool_id = t.id AND st.user_id = :uid) AS is_saved
    FROM ai_tools t
    JOIN ai_tool_categories cat ON t.category_id = cat.id
    WHERE $whereClause
    ORDER BY t.is_featured DESC, t.user_rating DESC
");
$fetchParams = array_merge([':uid' => $userId], $params);
$toolsStmt->execute($fetchParams);
$tools = $toolsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-3">
    <!-- Compact Header Banner -->
    <div class="p-3 p-md-4 rounded-3 mb-3" style="background: linear-gradient(135deg, #0f172a, #2e1065); color: #fff;">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-purple px-2 py-1 rounded-pill mb-1" style="background-color: var(--accent-purple); font-size: 0.72rem;">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> AI Curations
                </span>
                <h1 class="fs-4 fw-bold text-white mb-1">Curated AI Tools & Coding Assistants</h1>
                <p class="text-light opacity-75 small mb-0">
                    Discover, bookmark, and integrate handpicked AI platforms designed to boost developer productivity, content creation, and workflow automation.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-2 mt-lg-0">
                <a href="<?= APP_URL ?>/user/saved-tools.php" class="btn btn-outline-light btn-sm py-1 px-3">
                    <i class="fa-solid fa-heart me-1"></i> My Saved AI Tools
                </a>
            </div>
        </div>
    </div>

    <!-- Compact Filter & Search Bar -->
    <div class="custom-card p-3 mb-3" style="height: auto;">
        <form method="GET" action="<?= APP_URL ?>/ai-tools.php" class="row g-2 align-items-end">
            <div class="col-lg-6 col-md-6">
                <label for="search" class="form-label small fw-semibold text-secondary mb-1">Search Tools</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?= e($search) ?>" placeholder="Tool name, feature, or keyword...">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label for="category" class="form-label small fw-semibold text-secondary mb-1">Category</label>
                <select class="form-select form-select-sm" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <label for="pricing" class="form-label small fw-semibold text-secondary mb-1">Pricing Model</label>
                <select class="form-select form-select-sm" id="pricing" name="pricing">
                    <option value="">All Pricing</option>
                    <option value="Free" <?= $pricing === 'Free' ? 'selected' : '' ?>>Free</option>
                    <option value="Freemium" <?= $pricing === 'Freemium' ? 'selected' : '' ?>>Freemium</option>
                    <option value="Paid" <?= $pricing === 'Paid' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>

            <div class="col-lg-1 col-md-6 d-flex gap-1">
                <button type="submit" class="btn btn-purple-custom btn-sm w-100 justify-content-center py-1" title="Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <?php if ($search || $categorySlug || $pricing): ?>
                    <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-outline-secondary btn-sm py-1" title="Reset">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category Pills -->
    <div class="d-flex flex-wrap gap-1 mb-3 pb-2 border-bottom overflow-auto">
        <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-sm py-1 px-3 <?= empty($categorySlug) ? 'btn-purple-custom' : 'btn-outline-secondary' ?> rounded-pill" style="font-size: 0.78rem;">
            All (<?= $totalTools ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= APP_URL ?>/ai-tools.php?category=<?= e($cat['slug']) ?>" class="btn btn-sm py-1 px-3 <?= $categorySlug === $cat['slug'] ? 'btn-purple-custom' : 'btn-outline-secondary' ?> rounded-pill" style="font-size: 0.78rem;">
                <i class="fa-solid <?= e($cat['icon']) ?> me-1"></i> <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- AI Tools Grid -->
    <?php if (!empty($tools)): ?>
        <div class="row g-3">
            <?php foreach ($tools as $tool): 
                $feats = json_decode($tool['features_json'] ?? '[]', true) ?: [];
            ?>
                <div class="col-lg-3 col-md-6">
                    <div class="custom-card ai-tool-card h-100">
                        <div class="ai-tool-header mb-2">
                            <div class="ai-tool-icon">
                                <i class="fa-solid <?= e($tool['category_icon'] ?? 'fa-robot') ?>"></i>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="card-tag py-0 px-2 <?= $tool['pricing_type'] === 'Free' ? 'tag-green' : ($tool['pricing_type'] === 'Freemium' ? 'tag-blue' : 'tag-orange') ?>" style="font-size: 0.68rem;">
                                    <?= e($tool['pricing_type']) ?>
                                </span>
                                <button class="btn btn-sm btn-link text-muted btn-save-tool p-0 <?= $tool['is_saved'] ? 'text-danger' : '' ?>" data-tool-id="<?= $tool['id'] ?>" data-csrf="<?= csrf_token() ?>" title="Save Favorite">
                                    <i class="<?= $tool['is_saved'] ? 'fa-solid' : 'fa-regular' ?> fa-heart fs-6"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-muted" style="font-size: 0.72rem;"><?= e($tool['category_name']) ?></span>
                            <?php if ($tool['is_featured']): ?>
                                <span class="badge bg-warning text-dark py-0 px-1" style="font-size: 0.65rem;"><i class="fa-solid fa-star me-1"></i> Featured</span>
                            <?php endif; ?>
                        </div>

                        <h4 class="fs-6 fw-bold mb-1">
                            <a href="<?= APP_URL ?>/ai-tool-detail.php?id=<?= $tool['id'] ?>" class="text-dark hover-primary">
                                <?= e($tool['name']) ?>
                            </a>
                        </h4>

                        <div class="mb-2">
                            <span class="text-warning small" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-star"></i>
                                <strong class="text-dark ms-1"><?= number_format((float)$tool['user_rating'], 1) ?></strong>
                                <span class="text-muted">/ 5.0</span>
                            </span>
                        </div>

                        <p class="text-muted small mb-2 flex-grow-1" style="font-size: 0.78rem; line-height: 1.5;">
                            <?= e(truncate_text($tool['description'], 85)) ?>
                        </p>

                        <!-- Features chips preview -->
                        <?php if (!empty($feats)): ?>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <?php foreach (array_slice($feats, 0, 2) as $f): ?>
                                    <span class="badge bg-light text-secondary border small text-truncate py-0 px-1" style="max-width: 130px; font-size: 0.68rem;">
                                        &bull; <?= e($f) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="pt-2 border-top d-flex gap-2">
                            <a href="<?= APP_URL ?>/ai-tool-detail.php?id=<?= $tool['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 flex-grow-1" style="font-size: 0.75rem;">
                                Details
                            </a>
                            <a href="<?= e($tool['official_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-purple-custom py-1 flex-grow-1 text-center justify-content-center" style="font-size: 0.75rem;">
                                Launch <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.65rem;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="custom-card text-center py-4">
            <div class="stat-icon-wrapper bg-light text-muted mx-auto mb-2" style="width: 52px; height: 52px;">
                <i class="fa-solid fa-robot fs-3"></i>
            </div>
            <h4 class="fs-6 fw-bold text-dark mb-1">No AI Tools Match Your Filter</h4>
            <p class="text-muted small mb-3" style="max-width: 450px; margin: 0 auto;">
                Try choosing a different pricing model or resetting your search keywords.
            </p>
            <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-sm btn-outline-custom">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
