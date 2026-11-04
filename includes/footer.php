<?php
/**
 * Global Footer Component
 * MukeshSahu Platform
 */

declare(strict_types=1);

$siteEmail = get_setting('site_email', 'contact@mukeshsahu.in');
$sitePhone = get_setting('site_phone', '+91 98765 43210');
$siteAddress = get_setting('site_address', 'Bengaluru, Karnataka, India');
$linkedin = get_setting('social_linkedin', 'https://linkedin.com');
$github = get_setting('social_github', 'https://github.com');
$youtube = get_setting('social_youtube', 'https://youtube.com');
$twitter = get_setting('social_twitter', 'https://twitter.com');
?>
</main>

<footer class="footer-custom">
    <div class="container">
        <div class="row g-4 mb-5">
            <!-- Col 1: Brand & Bio -->
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand navbar-brand-logo mb-3 d-inline-flex" href="<?= APP_URL ?>/index.php">
                    <span class="brand-badge">MS</span>
                    <span class="brand-name text-white"><?= e(get_setting('site_name', 'MukeshSahu')) ?></span>
                </a>
                <p class="text-secondary mb-4" style="line-height: 1.7;">
                    Empowering software engineers and learners with industry-aligned online courses, handpicked IT job opportunities, curated AI platforms, and technical blogs.
                </p>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= e($linkedin) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="LinkedIn" data-bs-toggle="tooltip">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="GitHub" data-bs-toggle="tooltip">
                        <i class="fa-brands fa-github"></i>
                    </a>
                    <a href="<?= e($youtube) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="YouTube" data-bs-toggle="tooltip">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                    <a href="<?= e($twitter) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="Twitter / X" data-bs-toggle="tooltip">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5>Platform</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= APP_URL ?>/index.php" class="footer-link">Home</a></li>
                    <li><a href="<?= APP_URL ?>/about.php" class="footer-link">About Me</a></li>
                    <li><a href="<?= APP_URL ?>/jobs.php" class="footer-link">IT Jobs</a></li>
                    <li><a href="<?= APP_URL ?>/courses.php" class="footer-link">Courses</a></li>
                    <li><a href="<?= APP_URL ?>/ai-tools.php" class="footer-link">AI Tools</a></li>
                    <li><a href="<?= APP_URL ?>/blogs.php" class="footer-link">Tech Blogs</a></li>
                    <li><a href="<?= APP_URL ?>/contact.php" class="footer-link">Contact</a></li>
                </ul>
            </div>

            <!-- Col 3: Tech Tracks -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5>Featured Tracks</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= APP_URL ?>/courses.php" class="footer-link">Core PHP & MySQL</a></li>
                    <li><a href="<?= APP_URL ?>/courses.php" class="footer-link">Python & AI Engineering</a></li>
                    <li><a href="<?= APP_URL ?>/courses.php" class="footer-link">Modern React.js & Redux</a></li>
                    <li><a href="<?= APP_URL ?>/courses.php" class="footer-link">LangChain & LangGraph</a></li>
                    <li><a href="<?= APP_URL ?>/ai-tools.php" class="footer-link">AI Coding Assistants</a></li>
                    <li><a href="<?= APP_URL ?>/jobs.php" class="footer-link">Remote Developer Jobs</a></li>
                </ul>
            </div>

            <!-- Col 4: Contact & Location -->
            <div class="col-lg-3 col-md-6">
                <h5>Contact Info</h5>
                <ul class="list-unstyled text-secondary">
                    <li class="mb-2 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-envelope mt-1 text-primary"></i>
                        <a href="mailto:<?= e($siteEmail) ?>" class="text-secondary text-break"><?= e($siteEmail) ?></a>
                    </li>
                    <li class="mb-2 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-phone text-primary"></i>
                        <span><?= e($sitePhone) ?></span>
                    </li>
                    <li class="mb-3 d-flex align-items-start gap-2">
                        <i class="fa-solid fa-location-dot mt-1 text-primary"></i>
                        <span><?= e($siteAddress) ?></span>
                    </li>
                </ul>
                <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                    <small class="text-light d-block fw-bold mb-1"><i class="fa-solid fa-shield-halved text-success me-1"></i> Verified & Secure</small>
                    <small class="text-secondary">All educational lectures, job postings, and AI tools are personally verified.</small>
                </div>
            </div>
        </div>

        <div class="pt-4 border-top border-secondary border-opacity-25 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="small text-secondary">
                &copy; <?= date('Y') ?> <strong><?= e(get_setting('site_name', 'MukeshSahu')) ?></strong>. All rights reserved. Designed for technology professionals.
            </div>
            <div class="d-flex align-items-center gap-3 small">
                <a href="<?= APP_URL ?>/about.php" class="text-secondary">About</a>
                <span>&bull;</span>
                <a href="<?= APP_URL ?>/contact.php" class="text-secondary">Support</a>
                <span>&bull;</span>
                <a href="<?= APP_URL ?>/admin/dashboard.php" class="text-secondary">Admin Portal</a>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Container for Notifications -->
<div id="global-toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;"></div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (Required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Chart.js (for analytics) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom App JS -->
<script src="<?= ASSETS_URL ?>/js/main.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
