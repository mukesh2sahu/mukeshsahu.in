<?php
/**
 * Homepage - MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Home | Personal Learning & Technology Platform';
require_once __DIR__ . '/includes/header.php';

$db = get_db();

// 1. Fetch Latest & Featured IT Jobs (Limit 3)
$jobsStmt = $db->query("
    SELECT j.*, c.name AS category_name, c.icon AS category_icon 
    FROM jobs j 
    JOIN job_categories c ON j.category_id = c.id 
    WHERE j.status = 'published' 
    ORDER BY j.is_featured DESC, j.created_at DESC 
    LIMIT 3
");
$latestJobs = $jobsStmt->fetchAll();

// 2. Fetch Featured Courses (Limit 3)
$coursesStmt = $db->query("
    SELECT c.*, cat.name AS category_name 
    FROM courses c 
    JOIN course_categories cat ON c.category_id = cat.id 
    WHERE c.status = 'published' 
    ORDER BY c.is_featured DESC, c.created_at DESC 
    LIMIT 3
");
$featuredCourses = $coursesStmt->fetchAll();

// 3. Fetch Featured AI Tools (Limit 4)
$aiToolsStmt = $db->query("
    SELECT t.*, cat.name AS category_name, cat.icon AS category_icon 
    FROM ai_tools t 
    JOIN ai_tool_categories cat ON t.category_id = cat.id 
    WHERE t.status = 'active' 
    ORDER BY t.is_featured DESC, t.user_rating DESC 
    LIMIT 4
");
$featuredAiTools = $aiToolsStmt->fetchAll();

// 4. Fetch Latest Blogs (Limit 3)
$blogsStmt = $db->query("
    SELECT b.*, cat.name AS category_name, u.name AS author_name 
    FROM blogs b 
    JOIN blog_categories cat ON b.category_id = cat.id 
    LEFT JOIN users u ON b.author_id = u.id 
    WHERE b.status = 'published' 
    ORDER BY b.published_at DESC 
    LIMIT 3
");
$latestBlogs = $blogsStmt->fetchAll();

// Stats counter
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalJobs = $db->query("SELECT COUNT(*) FROM jobs WHERE status = 'published'")->fetchColumn();
$totalCourses = $db->query("SELECT COUNT(*) FROM courses WHERE status = 'published'")->fetchColumn();
$totalAiTools = $db->query("SELECT COUNT(*) FROM ai_tools WHERE status = 'active'")->fetchColumn();
?>

<!-- 1. Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="hero-pill">
                    <i class="fa-solid fa-sparkles"></i> Welcome to the Official Platform
                </div>
                <h1 class="hero-title">
                    Welcome to <span class="gradient-text">MukeshSahu</span>
                </h1>
                <p class="hero-subheading">
                    Explore IT Opportunities, Learn New Technologies, Discover AI Tools, and Stay Updated. A unified personal platform bridging real-world engineering with industry careers.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="<?= APP_URL ?>/courses.php" class="btn btn-primary-custom btn-lg">
                        <i class="fa-solid fa-graduation-cap"></i> Explore Courses
                    </a>
                    <a href="<?= APP_URL ?>/jobs.php" class="btn btn-outline-custom btn-lg">
                        <i class="fa-solid fa-briefcase"></i> Find IT Jobs
                    </a>
                    <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-purple-custom btn-lg">
                        <i class="fa-solid fa-robot"></i> Discover AI Tools
                    </a>
                </div>
                <div class="d-flex align-items-center gap-4 text-muted pt-3 border-top">
                    <div>
                        <strong class="text-dark fs-5"><?= (int)$totalJobs ?>+</strong>
                        <div class="small">Active Tech Jobs</div>
                    </div>
                    <div class="border-start ps-4">
                        <strong class="text-dark fs-5"><?= (int)$totalCourses ?>+</strong>
                        <div class="small">Curated Courses</div>
                    </div>
                    <div class="border-start ps-4">
                        <strong class="text-dark fs-5"><?= (int)$totalAiTools ?>+</strong>
                        <div class="small">Vetted AI Tools</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-5 text-center">
                <div class="hero-avatar-wrapper">
                    <div class="hero-avatar-frame mx-auto">
                        <img src="<?= ASSETS_URL ?>/images/profile-mukesh.png" alt="Mukesh Sahu" class="hero-avatar-img">
                    </div>
                    <div class="hero-badge-floating badge-top-right">
                        <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-bold fs-6 text-dark">Tech Educator</div>
                            <div class="small text-muted">7+ Years Exp</div>
                        </div>
                    </div>
                    <div class="hero-badge-floating badge-bottom-left">
                        <div class="stat-icon-wrapper bg-success-subtle text-success">
                            <i class="fa-solid fa-shield-check"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-bold fs-6 text-dark">Verified Portal</div>
                            <div class="small text-muted">100% Curated</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. About Me Preview Section -->
<section class="section-padding bg-white border-bottom">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="p-4 p-md-5 rounded-4" style="background: linear-gradient(145deg, #0f172a, #1e293b); color: #fff;">
                    <span class="badge bg-primary px-3 py-2 mb-3 rounded-pill text-uppercase" style="letter-spacing: 1px;">Instructor & Architect</span>
                    <h3 class="text-white mb-3">Hi, I'm Mukesh Sahu</h3>
                    <p class="text-light opacity-75 mb-4" style="line-height: 1.8;">
                        <?= e(truncate_text(get_setting('about_bio', 'Software engineer and mentor with industry expertise in full-stack architecture, AI engineering, and modern web application development.'), 260)) ?>
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= APP_URL ?>/about.php" class="btn btn-outline-light px-4 py-2">
                            Read Full Biography <i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="ps-lg-4">
                    <div class="section-subtitle">Why This Platform?</div>
                    <h2 class="section-title">Designed for Modern Developers & Tech Aspirants</h2>
                    <p class="text-muted mb-4">
                        Unlike fragmented platforms, MukeshSahu combines verified IT job opportunities with structured video learning and cutting-edge AI tools to accelerate your engineering career.
                    </p>
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="stat-icon-wrapper bg-primary-subtle text-primary flex-shrink-0">
                                    <i class="fa-solid fa-laptop-code"></i>
                                </div>
                                <div>
                                    <h5 class="fs-6 mb-1">Handpicked IT Jobs</h5>
                                    <p class="small text-muted mb-0">Verified remote and on-site openings across PHP, React, Python, and AI.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="stat-icon-wrapper bg-purple-subtle text-purple flex-shrink-0" style="background-color: var(--accent-purple-light); color: var(--accent-purple);">
                                    <i class="fa-solid fa-play"></i>
                                </div>
                                <div>
                                    <h5 class="fs-6 mb-1">YouTube Integrated Courses</h5>
                                    <p class="small text-muted mb-0">Zero fluff, hands-on video modules with progress tracking and syllabus.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="stat-icon-wrapper bg-info-subtle text-info flex-shrink-0">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                </div>
                                <div>
                                    <h5 class="fs-6 mb-1">Curated AI Tools Directory</h5>
                                    <p class="small text-muted mb-0">Discover and bookmark high-utility AI platforms for coding, writing, and design.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="stat-icon-wrapper bg-success-subtle text-success flex-shrink-0">
                                    <i class="fa-solid fa-newspaper"></i>
                                </div>
                                <div>
                                    <h5 class="fs-6 mb-1">In-Depth Tech Articles</h5>
                                    <p class="small text-muted mb-0">Deep dives into architecture, database optimization, and interview guides.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Featured Courses Section -->
<section class="section-padding" id="featured-courses">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="section-subtitle">Accelerate Your Skills</div>
                <h2 class="section-title">Featured Online Courses</h2>
                <p class="text-muted mb-0">High-impact engineering courses with structured modules and YouTube player integration.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= APP_URL ?>/courses.php" class="btn btn-outline-custom">
                    View All Courses <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredCourses as $course): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="custom-card">
                        <div class="course-thumb-wrapper">
                            <?php 
                                $thumbUrl = !empty($course['thumbnail']) && file_exists(APP_ROOT . '/assets/uploads/thumbnails/' . $course['thumbnail'])
                                    ? ASSETS_URL . '/uploads/thumbnails/' . e($course['thumbnail'])
                                    : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&auto=format&fit=crop&q=80';
                            ?>
                            <img src="<?= $thumbUrl ?>" alt="<?= e($course['title']) ?>" class="course-thumb-img">
                            <span class="course-duration-badge">
                                <i class="fa-regular fa-clock me-1"></i> <?= e($course['duration']) ?>
                            </span>
                        </div>
                        <div class="course-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="card-tag tag-blue"><?= e($course['category_name']) ?></span>
                                <span class="badge bg-light text-dark border"><?= e($course['skill_level']) ?></span>
                            </div>
                            <h4 class="fs-5 mb-2">
                                <a href="<?= APP_URL ?>/courses.php?id=<?= $course['id'] ?>" class="text-dark hover-primary">
                                    <?= e($course['title']) ?>
                                </a>
                            </h4>
                            <p class="text-muted small flex-grow-1 mb-3">
                                <?= e(truncate_text($course['description'], 110)) ?>
                            </p>
                            <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                                <div class="small text-muted">
                                    <i class="fa-solid fa-layer-group me-1 text-primary"></i> <?= (int)$course['total_lectures'] ?> Lectures
                                </div>
                                <a href="<?= APP_URL ?>/courses.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary-custom">
                                    <?php if (is_logged_in()): ?>
                                        Access Course <i class="fa-solid fa-play ms-1"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-lock me-1"></i> Login to View
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4. Latest IT Jobs Section -->
<section class="section-padding bg-white border-top border-bottom" id="latest-jobs">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="section-subtitle">Career Opportunities</div>
                <h2 class="section-title">Latest IT Job Openings</h2>
                <p class="text-muted mb-0">Verified tech positions from top startups, product agencies, and enterprises.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= APP_URL ?>/jobs.php" class="btn btn-outline-custom">
                    Explore All Jobs <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($latestJobs as $job): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="custom-card job-card">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="job-company-logo">
                                <i class="fa-solid <?= e($job['category_icon'] ?? 'fa-briefcase') ?>"></i>
                            </div>
                            <span class="card-tag <?= $job['work_mode'] === 'Remote' ? 'tag-green' : 'tag-orange' ?>">
                                <?= e($job['work_mode']) ?>
                            </span>
                        </div>
                        <h4 class="job-title">
                            <a href="<?= APP_URL ?>/jobs.php?id=<?= $job['id'] ?>" class="text-dark hover-primary">
                                <?= e($job['title']) ?>
                            </a>
                        </h4>
                        <div class="fw-semibold text-secondary small mb-3">
                            <?= e($job['company_name']) ?> &bull; <?= e($job['location']) ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="job-meta-pill bg-light border px-2 py-1 rounded">
                                <i class="fa-solid fa-indian-rupee-sign text-success"></i> <?= e($job['salary_range']) ?>
                            </span>
                            <span class="job-meta-pill bg-light border px-2 py-1 rounded">
                                <i class="fa-solid fa-briefcase text-primary"></i> <?= e($job['experience_level']) ?>
                            </span>
                        </div>
                        <p class="text-muted small mb-4 flex-grow-1">
                            <?= e(truncate_text($job['description'], 95)) ?>
                        </p>
                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <span class="small text-muted">
                                <i class="fa-regular fa-clock me-1"></i> <?= time_ago($job['created_at']) ?>
                            </span>
                            <a href="<?= APP_URL ?>/jobs.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-custom">
                                <?php if (is_logged_in()): ?>
                                    View Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-lock me-1"></i> Sign In to View
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 5. Popular AI Tools Section -->
<section class="section-padding" id="ai-tools">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="section-subtitle">Boost Productivity</div>
                <h2 class="section-title">Popular AI Tools Directory</h2>
                <p class="text-muted mb-0">Curated artificial intelligence tools and coding assistants to supercharge your workflow.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= APP_URL ?>/ai-tools.php" class="btn btn-purple-custom">
                    Explore All AI Tools <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredAiTools as $tool): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="custom-card ai-tool-card">
                        <div class="ai-tool-header">
                            <div class="ai-tool-icon">
                                <i class="fa-solid <?= e($tool['category_icon'] ?? 'fa-robot') ?>"></i>
                            </div>
                            <span class="card-tag <?= $tool['pricing_type'] === 'Free' ? 'tag-green' : ($tool['pricing_type'] === 'Freemium' ? 'tag-blue' : 'tag-orange') ?>">
                                <?= e($tool['pricing_type']) ?>
                            </span>
                        </div>
                        <h4 class="fs-5 mb-1"><?= e($tool['name']) ?></h4>
                        <div class="small text-muted mb-2"><?= e($tool['category_name']) ?></div>
                        <div class="mb-3">
                            <span class="text-warning small">
                                <i class="fa-solid fa-star"></i>
                                <strong class="text-dark ms-1"><?= number_format((float)$tool['user_rating'], 1) ?></strong>
                            </span>
                        </div>
                        <p class="text-muted small mb-4 flex-grow-1">
                            <?= e(truncate_text($tool['description'], 85)) ?>
                        </p>
                        <div class="border-top pt-3">
                            <a href="<?= APP_URL ?>/ai-tools.php?id=<?= $tool['id'] ?>" class="btn btn-sm btn-outline-custom w-100">
                                <?php if (is_logged_in()): ?>
                                    Explore Tool <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-lock me-1"></i> Members Only
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 6. Latest Technology Blogs Section -->
<section class="section-padding bg-white border-top border-bottom">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="section-subtitle">Technical Insights</div>
                <h2 class="section-title">Latest Technology Blogs</h2>
                <p class="text-muted mb-0">Articles, engineering tutorials, architecture reviews, and career guidance.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= APP_URL ?>/blogs.php" class="btn btn-outline-custom">
                    Read All Articles <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($latestBlogs as $blog): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="custom-card">
                        <?php 
                            $blogImg = 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80';
                            if ($blog['id'] == 1) $blogImg = 'https://images.unsplash.com/photo-1599507593499-a3f7f7d97f66?w=600&auto=format&fit=crop&q=80';
                            if ($blog['id'] == 2) $blogImg = 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&auto=format&fit=crop&q=80';
                            if ($blog['id'] == 3) $blogImg = 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&auto=format&fit=crop&q=80';
                        ?>
                        <img src="<?= $blogImg ?>" alt="<?= e($blog['title']) ?>" class="blog-card-img">
                        <div class="p-4 flex-grow-1 d-flex flex-direction-column flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="card-tag tag-purple"><?= e($blog['category_name']) ?></span>
                                <span class="blog-meta"><i class="fa-regular fa-calendar me-1"></i> <?= format_date($blog['published_at']) ?></span>
                            </div>
                            <h4 class="fs-5 mb-2">
                                <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($blog['slug']) ?>" class="text-dark hover-primary">
                                    <?= e($blog['title']) ?>
                                </a>
                            </h4>
                            <p class="text-muted small flex-grow-1 mb-3">
                                <?= e(truncate_text($blog['excerpt'] ?? $blog['content'], 110)) ?>
                            </p>
                            <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                                <span class="small text-muted">
                                    <i class="fa-regular fa-eye me-1"></i> <?= (int)$blog['views_count'] ?> views
                                </span>
                                <a href="<?= APP_URL ?>/blog-detail.php?slug=<?= e($blog['slug']) ?>" class="btn btn-sm btn-link text-primary fw-semibold p-0">
                                    Read Article <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 7. Skills and Technologies Showcase -->
<section class="section-padding">
    <div class="container text-center">
        <div class="section-subtitle">Technical Proficiency</div>
        <h2 class="section-title mb-4">Technologies & Stacks Taught</h2>
        <p class="text-muted mx-auto mb-5" style="max-width: 650px;">
            The platform focuses on high-demand modern technology stacks used by world-class software organizations and fast-moving tech startups.
        </p>

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
            <?php 
                $techs = [
                    ['name' => 'PHP 8.2+', 'icon' => 'fa-brands fa-php', 'color' => '#8993be'],
                    ['name' => 'MySQL & PDO', 'icon' => 'fa-solid fa-database', 'color' => '#00758f'],
                    ['name' => 'Python 3', 'icon' => 'fa-brands fa-python', 'color' => '#3776ab'],
                    ['name' => 'React.js', 'icon' => 'fa-brands fa-react', 'color' => '#61dafb'],
                    ['name' => 'Generative AI', 'icon' => 'fa-solid fa-brain', 'color' => '#8b5cf6'],
                    ['name' => 'LangChain', 'icon' => 'fa-solid fa-link', 'color' => '#06b6d4'],
                    ['name' => 'JavaScript ES6+', 'icon' => 'fa-brands fa-js', 'color' => '#f7df1e'],
                    ['name' => 'Bootstrap 5', 'icon' => 'fa-brands fa-bootstrap', 'color' => '#7952b3'],
                    ['name' => 'Docker & Cloud', 'icon' => 'fa-brands fa-docker', 'color' => '#2496ed'],
                    ['name' => 'Power BI', 'icon' => 'fa-solid fa-chart-pie', 'color' => '#f2c811'],
                    ['name' => 'REST APIs', 'icon' => 'fa-solid fa-network-wired', 'color' => '#10b981'],
                    ['name' => 'Git & GitHub', 'icon' => 'fa-brands fa-git-alt', 'color' => '#f05032']
                ];
                foreach ($techs as $tech):
            ?>
                <div class="col">
                    <div class="p-3 bg-white rounded-3 border shadow-sm h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="<?= $tech['icon'] ?> fs-2 mb-2" style="color: <?= $tech['color'] ?>;"></i>
                        <span class="fw-semibold text-dark small"><?= $tech['name'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 8. Contact CTA Section -->
<section class="section-padding bg-white border-top">
    <div class="container">
        <div class="p-5 rounded-4 text-center text-md-start" style="background: linear-gradient(135deg, rgba(37,99,235,0.06), rgba(139,92,246,0.08)); border: 1px solid rgba(37,99,235,0.15);">
            <div class="row align-items-center g-4">
                <div class="col-md-8">
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-2">Have a Question or Project?</span>
                    <h3 class="mb-2">Let's Connect & Collaborate</h3>
                    <p class="text-muted mb-0">
                        Whether you are an aspiring engineer seeking mentorship, a company with job openings, or interested in AI consultations, I'm here to help.
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?= APP_URL ?>/contact.php" class="btn btn-primary-custom btn-lg">
                        <i class="fa-solid fa-paper-plane"></i> Contact Mukesh Sahu
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
