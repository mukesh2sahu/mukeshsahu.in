<?php
/**
 * About Me Page - MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'About Me';
$metaDesc = 'Learn about Mukesh Sahu, Full Stack Software Engineer, Instructor, and AI Enthusiast.';
require_once __DIR__ . '/includes/header.php';

$db = get_db();

// Fetch About info from website_settings or user_profiles
$name = get_setting('about_name', 'Mukesh Sahu');
$title = get_setting('about_title', 'Full Stack Software Engineer & Technology Educator');
$bio = get_setting('about_bio', 'Passionate software engineer, mentor, and content creator dedicated to empowering learners with modern programming languages, AI engineering, and career advancement.');
$educationJson = get_setting('about_education', '[]');
$experienceJson = get_setting('about_experience', '[]');
$skillsJson = get_setting('about_skills', '[]');

$educationList = json_decode($educationJson, true) ?: [];
$experienceList = json_decode($experienceJson, true) ?: [];
$skillsList = json_decode($skillsJson, true) ?: [];

$github = get_setting('social_github', 'https://github.com');
$linkedin = get_setting('social_linkedin', 'https://linkedin.com');
$youtube = get_setting('social_youtube', 'https://youtube.com');
$twitter = get_setting('social_twitter', 'https://twitter.com');
?>

<!-- About Hero / Header -->
<section class="section-padding bg-white border-bottom">
    <div class="container-fluid px-3 px-md-4 px-xl-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-4 text-center">
                <div class="hero-avatar-frame mx-auto shadow-lg" style="width: 290px; height: 290px;">
                    <img src="<?= ASSETS_URL ?>/images/profile-mukesh.svg?v=<?= APP_VERSION ?>" alt="<?= e($name) ?>" class="hero-avatar-img">
                </div>
                <div class="mt-4 d-flex justify-content-center gap-2">
                    <a href="<?= e($linkedin) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-custom btn-sm">
                        <i class="fa-brands fa-linkedin"></i> LinkedIn
                    </a>
                    <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-custom btn-sm">
                        <i class="fa-brands fa-github"></i> GitHub
                    </a>
                    <a href="<?= e($youtube) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-custom btn-sm">
                        <i class="fa-brands fa-youtube"></i> YouTube
                    </a>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="ps-lg-3">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold mb-3">
                        <i class="fa-solid fa-code me-1"></i> Professional Biography
                    </span>
                    <h1 class="display-5 fw-bold mb-2 text-dark"><?= e($name) ?></h1>
                    <h4 class="text-primary fw-semibold mb-4 fs-5"><?= e($title) ?></h4>
                    <div class="lead text-secondary mb-4" style="line-height: 1.8; font-size: 1.12rem;">
                        <?= nl2br(e($bio)) ?>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-4 pt-3 border-top">
                        <div>
                            <div class="small text-muted text-uppercase fw-semibold">Experience</div>
                            <div class="fw-bold fs-5 text-dark">7+ Years</div>
                        </div>
                        <div class="border-start ps-4">
                            <div class="small text-muted text-uppercase fw-semibold">Students Mentored</div>
                            <div class="fw-bold fs-5 text-dark">15,000+</div>
                        </div>
                        <div class="border-start ps-4">
                            <div class="small text-muted text-uppercase fw-semibold">Tech Focus</div>
                            <div class="fw-bold fs-5 text-dark">Full-Stack & Generative AI</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Experience & Education Dual Column -->
<section class="section-padding">
    <div class="container-fluid px-3 px-md-4 px-xl-5">
        <div class="row g-5">
            <!-- Work Experience Timeline -->
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div>
                        <h3 class="fs-4 mb-0">Professional Experience</h3>
                        <p class="small text-muted mb-0">Career milestones & corporate contributions</p>
                    </div>
                </div>

                <div class="timeline mt-4">
                    <?php if (!empty($experienceList)): ?>
                        <?php foreach ($experienceList as $exp): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="custom-card p-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                        <h5 class="fs-6 fw-bold mb-0 text-dark"><?= e($exp['role'] ?? '') ?></h5>
                                        <span class="badge bg-light text-primary border small"><?= e($exp['period'] ?? '') ?></span>
                                    </div>
                                    <div class="text-secondary small fw-semibold mb-2"><?= e($exp['company'] ?? '') ?></div>
                                    <p class="text-muted small mb-0"><?= e($exp['description'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No experience entries recorded.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Educational Background Timeline -->
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="stat-icon-wrapper bg-purple-subtle text-purple" style="background-color: var(--accent-purple-light); color: var(--accent-purple);">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 class="fs-4 mb-0">Educational Background</h3>
                        <p class="small text-muted mb-0">Academic qualifications & engineering training</p>
                    </div>
                </div>

                <div class="timeline mt-4">
                    <?php if (!empty($educationList)): ?>
                        <?php foreach ($educationList as $edu): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot" style="background: var(--accent-purple);"></div>
                                <div class="custom-card p-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                        <h5 class="fs-6 fw-bold mb-0 text-dark"><?= e($edu['degree'] ?? '') ?></h5>
                                        <span class="badge bg-light text-secondary border small"><?= e($edu['year'] ?? '') ?></span>
                                    </div>
                                    <div class="text-secondary small fw-semibold mb-2"><?= e($edu['institution'] ?? '') ?></div>
                                    <p class="text-muted small mb-0"><?= e($edu['details'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No educational records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technical Skills & Proficiency -->
<section class="section-padding bg-white border-top border-bottom">
    <div class="container-fluid px-3 px-md-4 px-xl-5">
        <div class="text-center max-w-700 mx-auto mb-5">
            <div class="section-subtitle">Skills Matrix</div>
            <h2 class="section-title">Technical Expertise & Core Competencies</h2>
            <p class="text-muted">Hands-on mastery acquired over years of production architecture and teaching.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($skillsList as $skill): ?>
                <div class="col-md-6 col-xxl-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark"><?= e($skill['name'] ?? '') ?></span>
                            <span class="badge bg-primary"><?= (int)($skill['level'] ?? 90) ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-gradient-primary" role="progressbar" style="width: <?= (int)($skill['level'] ?? 90) ?>%; background: linear-gradient(90deg, var(--electric-blue), var(--accent-purple));" aria-valuenow="<?= (int)($skill['level'] ?? 90) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Key Projects and Achievements -->
<section class="section-padding">
    <div class="container-fluid px-3 px-md-4 px-xl-5">
        <div class="section-header text-center">
            <div class="section-subtitle">Portfolio & Milestones</div>
            <h2 class="section-title">Key Projects & Achievements</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="custom-card p-4">
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary mb-3">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <h5 class="fs-5 mb-2">Enterprise SaaS Architecture</h5>
                    <p class="small text-muted mb-3">
                        Designed multi-tenant ERP and learning systems serving hundreds of thousands of daily requests with high availability.
                    </p>
                    <span class="badge bg-light text-dark border">PHP 8 &bull; MySQL &bull; Redis</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="custom-card p-4">
                    <div class="stat-icon-wrapper bg-info-subtle text-info mb-3">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <h5 class="fs-5 mb-2">Autonomous Multi-Agent Workflows</h5>
                    <p class="small text-muted mb-3">
                        Built stateful AI agents using LangGraph and vector search pipelines that automate customer onboarding and support ticket triaging.
                    </p>
                    <span class="badge bg-light text-dark border">Python &bull; LangChain &bull; FastAPI</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="custom-card p-4">
                    <div class="stat-icon-wrapper bg-success-subtle text-success mb-3">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <h5 class="fs-5 mb-2">Community & Tech Mentorship</h5>
                    <p class="small text-muted mb-3">
                        Authored over 50+ tech tutorials, video courses, and organized community bootcamps helping junior developers break into top IT firms.
                    </p>
                    <span class="badge bg-light text-dark border">Mentorship &bull; YouTube &bull; Open Source</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
