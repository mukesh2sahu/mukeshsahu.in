<?php
/**
 * Admin AI Tools Management - Add Tool
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Add AI Tool';
$pageHeading = 'Add New Artificial Intelligence Tool';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$errors = [];

$categories = $db->query("SELECT * FROM ai_tool_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$name = '';
$slug = '';
$categoryId = $categories[0]['id'] ?? 1;
$officialUrl = '';
$pricingType = 'Freemium';
$userRating = 4.8;
$featuresRaw = '';
$description = '';
$isFeatured = 0;
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $officialUrl = trim($_POST['official_url'] ?? '');
    $pricingType = $_POST['pricing_type'] ?? 'Freemium';
    $userRating = (float)($_POST['user_rating'] ?? 4.5);
    $featuresRaw = trim($_POST['features'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';

    if (empty($name)) $errors[] = 'Tool name is required.';
    if (empty($slug)) $slug = slugify($name);
    if (empty($officialUrl) || !filter_var($officialUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'A valid official website URL is required.';
    }
    if (empty($description)) $errors[] = 'Description is required.';

    // Check duplicate slug
    $check = $db->prepare("SELECT id FROM ai_tools WHERE slug = :slug LIMIT 1");
    $check->execute([':slug' => $slug]);
    if ($check->fetch()) {
        $slug = $slug . '-' . time();
    }

    // Convert features to JSON
    $featureList = array_values(array_filter(array_map('trim', explode("\n", $featuresRaw))));
    $featuresJson = json_encode($featureList);

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO ai_tools (category_id, name, slug, description, official_url, pricing_type, features_json, user_rating, is_featured, status, created_at)
            VALUES (:cat, :name, :slug, :desc, :url, :pricing, :feats, :rating, :feat, :status, NOW())
        ");
        $stmt->execute([
            ':cat'     => $categoryId,
            ':name'    => $name,
            ':slug'    => $slug,
            ':desc'    => $description,
            ':url'     => $officialUrl,
            ':pricing' => $pricingType,
            ':feats'   => $featuresJson,
            ':rating'  => $userRating,
            ':feat'    => $isFeatured,
            ':status'  => $status
        ]);
        $newId = (int)$db->lastInsertId();

        log_activity('Add AI Tool', 'ai_tools', "Added AI tool '$name' (#$newId)");
        set_flash('success', "AI tool '$name' added to directory.");
        header('Location: ' . ADMIN_URL . '/ai-tools/index.php');
        exit;
    }
}
?>

<div class="custom-card p-4 p-md-5" style="max-width: 800px;">
    <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-0">Add New AI Platform</h4>
            <span class="text-muted small">Curate a new artificial intelligence tool for members</span>
        </div>
        <a href="<?= ADMIN_URL ?>/ai-tools/index.php" class="btn btn-outline-secondary btn-sm">
            &larr; Back to AI Directory
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

    <form method="POST" action="<?= ADMIN_URL ?>/ai-tools/add.php" novalidate>
        <?= csrf_field(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <label for="name" class="form-label fw-semibold small">Tool Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" placeholder="e.g. Cursor AI Code Editor" required>
            </div>
            <div class="col-md-5">
                <label for="category_id" class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-8">
                <label for="official_url" class="form-label fw-semibold small">Official Website URL <span class="text-danger">*</span></label>
                <input type="url" class="form-control" id="official_url" name="official_url" value="<?= e($officialUrl) ?>" placeholder="https://example.com" required>
            </div>
            <div class="col-md-4">
                <label for="pricing_type" class="form-label fw-semibold small">Pricing Model</label>
                <select class="form-select" id="pricing_type" name="pricing_type">
                    <option value="Freemium" <?= $pricingType === 'Freemium' ? 'selected' : '' ?>>Freemium</option>
                    <option value="Free" <?= $pricingType === 'Free' ? 'selected' : '' ?>>Free</option>
                    <option value="Paid" <?= $pricingType === 'Paid' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="user_rating" class="form-label fw-semibold small">Initial Rating (1.0 to 5.0)</label>
                <input type="number" step="0.1" min="1.0" max="5.0" class="form-control" id="user_rating" name="user_rating" value="<?= e((string)$userRating) ?>">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-semibold small">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-center pt-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?= $isFeatured ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold small" for="is_featured">
                        <i class="fa-solid fa-star text-warning me-1"></i> Feature on Homepage
                    </label>
                </div>
            </div>

            <div class="col-12">
                <label for="features" class="form-label fw-semibold small">Key Features (One feature per line)</label>
                <textarea class="form-control" id="features" name="features" rows="3" placeholder="Full codebase indexing&#10;Inline terminal assistance&#10;Multi-file AI edits"><?= e($featuresRaw) ?></textarea>
            </div>

            <div class="col-12">
                <label for="description" class="form-label fw-semibold small">Description & Capabilities <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Comprehensive explanation of what this AI platform does and how it helps engineers..." required><?= e($description) ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-purple-custom px-4 py-2">
            <i class="fa-solid fa-plus me-2"></i> Save AI Tool
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
