<?php
/**
 * Contact Page - MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Contact Mukesh Sahu';
$metaDesc = 'Get in touch with Mukesh Sahu for consulting, course inquiries, job postings, or speaking engagements.';
require_once __DIR__ . '/config/app.php';

$errors = [];
$name = '';
$email = '';
$mobile = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Invalid or expired session security token. Please try submitting again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($mobile) || strlen($mobile) < 7) {
        $errors[] = 'A valid mobile number is required.';
    }
    if (empty($subject)) {
        $errors[] = 'Subject is required.';
    }
    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters long.';
    }

    if (empty($errors)) {
        try {
            $db = get_db();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $db->prepare("
                INSERT INTO contact_messages (name, email, mobile, subject, message, is_read, ip_address, created_at) 
                VALUES (:name, :email, :mobile, :subject, :message, 0, :ip, NOW())
            ");
            $stmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':mobile'  => $mobile,
                ':subject' => $subject,
                ':message' => $message,
                ':ip'      => $ip
            ]);

            set_flash('success', 'Thank you for reaching out! Your message has been sent successfully. Mukesh Sahu will respond shortly.');
            header('Location: ' . APP_URL . '/contact.php');
            exit;
        } catch (Exception $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $errors[] = 'Failed to submit your message due to a server error. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';

$siteEmail = get_setting('site_email', 'contact@mukeshsahu.in');
$sitePhone = get_setting('site_phone', '+91 98765 43210');
$siteAddress = get_setting('site_address', 'Bengaluru, Karnataka, India');
$linkedin = get_setting('social_linkedin', 'https://linkedin.com');
$github = get_setting('social_github', 'https://github.com');
$youtube = get_setting('social_youtube', 'https://youtube.com');
$twitter = get_setting('social_twitter', 'https://twitter.com');
?>

<div class="container section-padding">
    <div class="row g-5">
        <!-- Contact Information Column -->
        <div class="col-lg-5">
            <div class="pe-lg-3">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold mb-3">
                    <i class="fa-solid fa-headset me-1"></i> Get in Touch
                </span>
                <h1 class="display-6 fw-bold mb-3">Let's Discuss Opportunities & Tech</h1>
                <p class="text-secondary mb-4" style="line-height: 1.8;">
                    Have questions regarding any of our courses, looking to post job requirements, seeking AI technical advice, or want to invite Mukesh Sahu for a tech talk? Drop a message below.
                </p>

                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border shadow-sm">
                        <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-semibold">Email Address</div>
                            <a href="mailto:<?= e($siteEmail) ?>" class="fw-bold text-dark"><?= e($siteEmail) ?></a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border shadow-sm">
                        <div class="stat-icon-wrapper bg-success-subtle text-success">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-semibold">Phone Number</div>
                            <span class="fw-bold text-dark"><?= e($sitePhone) ?></span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border shadow-sm">
                        <div class="stat-icon-wrapper bg-info-subtle text-info">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <div class="small text-muted fw-semibold">Base Location</div>
                            <span class="fw-bold text-dark"><?= e($siteAddress) ?></span>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-3">Connect on Social Networks</h6>
                <div class="d-flex gap-2">
                    <a href="<?= e($linkedin) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" style="background: var(--electric-blue); color: #fff;">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" style="background: #24292e; color: #fff;">
                        <i class="fa-brands fa-github"></i>
                    </a>
                    <a href="<?= e($youtube) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" style="background: #ff0000; color: #fff;">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                    <a href="<?= e($twitter) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn" style="background: #0f172a; color: #fff;">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Form Column -->
        <div class="col-lg-7">
            <div class="custom-card p-4 p-md-5">
                <h3 class="fs-4 fw-bold mb-2">Send an Inquiry</h3>
                <p class="text-muted small mb-4">Fill out the form below. We usually reply within 24 business hours.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <strong>Please correct the following:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/contact.php" novalidate>
                    <?= csrf_field(); ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" placeholder="e.g. Rahul Sharma" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="e.g. rahul@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label for="mobile" class="form-label fw-semibold small">Mobile Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="mobile" name="mobile" value="<?= e($mobile) ?>" placeholder="+91 98765 43210" required>
                        </div>
                        <div class="col-md-6">
                            <label for="subject" class="form-label fw-semibold small">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject" value="<?= e($subject) ?>" placeholder="e.g. Course Query / Job Collaboration" required>
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label fw-semibold small">Message Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="5" placeholder="Write your detailed message or inquiry here..." required><?= e($message) ?></textarea>
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" class="btn btn-primary-custom w-100 py-2 justify-content-center">
                                <i class="fa-solid fa-paper-plane me-2"></i> Submit Inquiry
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
