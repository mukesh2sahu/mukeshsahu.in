<?php
/**
 * Admin Website Settings & Configuration
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Website Settings';
$pageHeading = 'General & Personal Brand Configurations';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $settings = [
            'site_name'        => trim($_POST['site_name'] ?? 'MukeshSahu'),
            'site_tagline'     => trim($_POST['site_tagline'] ?? ''),
            'site_email'       => trim($_POST['site_email'] ?? ''),
            'site_phone'       => trim($_POST['site_phone'] ?? ''),
            'site_address'     => trim($_POST['site_address'] ?? ''),
            'social_linkedin'  => trim($_POST['social_linkedin'] ?? ''),
            'social_github'    => trim($_POST['social_github'] ?? ''),
            'social_youtube'   => trim($_POST['social_youtube'] ?? ''),
            'social_twitter'   => trim($_POST['social_twitter'] ?? ''),
            'seo_meta_title'   => trim($_POST['seo_meta_title'] ?? ''),
            'seo_meta_description' => trim($_POST['seo_meta_description'] ?? ''),
            'seo_meta_keywords'=> trim($_POST['seo_meta_keywords'] ?? ''),
            'about_name'       => trim($_POST['about_name'] ?? ''),
            'about_title'      => trim($_POST['about_title'] ?? ''),
            'about_bio'        => trim($_POST['about_bio'] ?? ''),
            'about_education'  => trim($_POST['about_education'] ?? '[]'),
            'about_experience' => trim($_POST['about_experience'] ?? '[]'),
            'about_skills'     => trim($_POST['about_skills'] ?? '[]')
        ];

        foreach ($settings as $key => $val) {
            update_setting($key, $val);
        }

        log_activity('Update Website Settings', 'settings', 'Updated platform configuration and personal branding information');
        set_flash('success', 'Website settings have been saved successfully.');
        header('Location: ' . ADMIN_URL . '/settings/index.php');
        exit;
    }
}

// Read current settings
$siteName = get_setting('site_name', 'MukeshSahu');
$siteTagline = get_setting('site_tagline', 'Personal Learning & Technology Platform');
$siteEmail = get_setting('site_email', 'contact@mukeshsahu.in');
$sitePhone = get_setting('site_phone', '+91 98765 43210');
$siteAddress = get_setting('site_address', 'Bengaluru, Karnataka, India');
$linkedin = get_setting('social_linkedin', 'https://linkedin.com');
$github = get_setting('social_github', 'https://github.com');
$youtube = get_setting('social_youtube', 'https://youtube.com');
$twitter = get_setting('social_twitter', 'https://twitter.com');
$metaTitle = get_setting('seo_meta_title', 'MukeshSahu – Personal Learning & Technology Platform');
$metaDesc = get_setting('seo_meta_description', 'Explore verified IT jobs, professional courses with video integration, top AI tools directory, and technical blogs.');
$metaKeywords = get_setting('seo_meta_keywords', 'Mukesh Sahu, IT Jobs, Web Development, PHP Courses, AI Tools');
$aboutName = get_setting('about_name', 'Mukesh Sahu');
$aboutTitle = get_setting('about_title', 'Full Stack Software Engineer & Technology Educator');
$aboutBio = get_setting('about_bio', '');
$aboutEducation = get_setting('about_education', '[]');
$aboutExperience = get_setting('about_experience', '[]');
$aboutSkills = get_setting('about_skills', '[]');
?>

<div class="custom-card p-4 p-md-5">
    <div class="mb-4 pb-2 border-bottom">
        <h4 class="fs-5 fw-bold text-dark mb-1">Platform Configuration & Branding</h4>
        <p class="text-muted small mb-0">Update website identities, contact channels, About Me content, and SEO meta tags</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= ADMIN_URL ?>/settings/index.php" novalidate>
        <?= csrf_field(); ?>

        <!-- Tabbed Sections -->
        <ul class="nav nav-pills mb-4" id="settingsTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general-pane" type="button">
                    <i class="fa-solid fa-sliders me-1"></i> General & Contact
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="about-tab" data-bs-toggle="pill" data-bs-target="#about-pane" type="button">
                    <i class="fa-solid fa-user me-1"></i> About Me Profile
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="social-tab" data-bs-toggle="pill" data-bs-target="#social-pane" type="button">
                    <i class="fa-solid fa-share-nodes me-1"></i> Social Media
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="seo-tab" data-bs-toggle="pill" data-bs-target="#seo-pane" type="button">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> SEO & Metadata
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabContent">
            <!-- 1. General & Contact Tab -->
            <div class="tab-pane fade show active" id="general-pane">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="site_name" class="form-label small fw-semibold">Website Brand Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?= e($siteName) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="site_tagline" class="form-label small fw-semibold">Website Tagline</label>
                        <input type="text" class="form-control" id="site_tagline" name="site_tagline" value="<?= e($siteTagline) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="site_email" class="form-label small fw-semibold">Official Contact Email</label>
                        <input type="email" class="form-control" id="site_email" name="site_email" value="<?= e($siteEmail) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="site_phone" class="form-label small fw-semibold">Official Phone Number</label>
                        <input type="text" class="form-control" id="site_phone" name="site_phone" value="<?= e($sitePhone) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="site_address" class="form-label small fw-semibold">Location / Headquarters</label>
                        <input type="text" class="form-control" id="site_address" name="site_address" value="<?= e($siteAddress) ?>">
                    </div>
                </div>
            </div>

            <!-- 2. About Me Profile Tab -->
            <div class="tab-pane fade" id="about-pane">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="about_name" class="form-label small fw-semibold">Full Name</label>
                        <input type="text" class="form-control" id="about_name" name="about_name" value="<?= e($aboutName) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="about_title" class="form-label small fw-semibold">Professional Title / Headline</label>
                        <input type="text" class="form-control" id="about_title" name="about_title" value="<?= e($aboutTitle) ?>">
                    </div>
                    <div class="col-12">
                        <label for="about_bio" class="form-label small fw-semibold">Biography / Summary</label>
                        <textarea class="form-control" id="about_bio" name="about_bio" rows="4"><?= e($aboutBio) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label for="about_education" class="form-label small fw-semibold">Education JSON</label>
                        <textarea class="form-control font-monospace small" id="about_education" name="about_education" rows="4"><?= e($aboutEducation) ?></textarea>
                        <div class="form-text small">JSON Array format: [{"degree": "...", "institution": "...", "year": "...", "details": "..."}]</div>
                    </div>
                    <div class="col-12">
                        <label for="about_experience" class="form-label small fw-semibold">Work Experience JSON</label>
                        <textarea class="form-control font-monospace small" id="about_experience" name="about_experience" rows="4"><?= e($aboutExperience) ?></textarea>
                        <div class="form-text small">JSON Array format: [{"role": "...", "company": "...", "period": "...", "description": "..."}]</div>
                    </div>
                    <div class="col-12">
                        <label for="about_skills" class="form-label small fw-semibold">Skills Matrix JSON</label>
                        <textarea class="form-control font-monospace small" id="about_skills" name="about_skills" rows="4"><?= e($aboutSkills) ?></textarea>
                        <div class="form-text small">JSON Array format: [{"name": "PHP & MySQL", "level": 95}, ...]</div>
                    </div>
                </div>
            </div>

            <!-- 3. Social Media Tab -->
            <div class="tab-pane fade" id="social-pane">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="social_linkedin" class="form-label small fw-semibold">LinkedIn Profile URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary"><i class="fa-brands fa-linkedin"></i></span>
                            <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" value="<?= e($linkedin) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="social_github" class="form-label small fw-semibold">GitHub Profile URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-dark"><i class="fa-brands fa-github"></i></span>
                            <input type="url" class="form-control" id="social_github" name="social_github" value="<?= e($github) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="social_youtube" class="form-label small fw-semibold">YouTube Channel URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-danger"><i class="fa-brands fa-youtube"></i></span>
                            <input type="url" class="form-control" id="social_youtube" name="social_youtube" value="<?= e($youtube) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="social_twitter" class="form-label small fw-semibold">Twitter / X URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-dark"><i class="fa-brands fa-x-twitter"></i></span>
                            <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="<?= e($twitter) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. SEO Tab -->
            <div class="tab-pane fade" id="seo-pane">
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="seo_meta_title" class="form-label small fw-semibold">Default Meta Title</label>
                        <input type="text" class="form-control" id="seo_meta_title" name="seo_meta_title" value="<?= e($metaTitle) ?>">
                    </div>
                    <div class="col-12">
                        <label for="seo_meta_description" class="form-label small fw-semibold">Default Meta Description</label>
                        <textarea class="form-control" id="seo_meta_description" name="seo_meta_description" rows="3"><?= e($metaDesc) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label for="seo_meta_keywords" class="form-label small fw-semibold">Default Keywords (comma-separated)</label>
                        <input type="text" class="form-control" id="seo_meta_keywords" name="seo_meta_keywords" value="<?= e($metaKeywords) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-3 border-top">
            <button type="submit" class="btn btn-primary-custom px-4 py-2">
                <i class="fa-solid fa-floppy-disk me-2"></i> Save Website Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
