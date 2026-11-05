<?php
/**
 * User Profile Settings
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Profile Settings';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_login();

$userId = current_user_id();
$db = get_db();
$errors = [];

// Fetch current user and profile
$stmt = $db->prepare("
    SELECT u.name, u.email, u.mobile, p.* 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = :uid 
    LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token invalid or expired. Please refresh and try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $headline = trim($_POST['headline'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $githubUrl = trim($_POST['github_url'] ?? '');
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
    $portfolioUrl = trim($_POST['portfolio_url'] ?? '');

    if (empty($name) || mb_strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }

    // Avatar upload if provided
    $avatarFilename = $userData['avatar'] ?? null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadRes = upload_image_file($_FILES['avatar'], 'avatars');
        if ($uploadRes['success']) {
            $avatarFilename = $uploadRes['filename'];
            $_SESSION['user_avatar'] = $avatarFilename;
        } else {
            $errors[] = $uploadRes['error'];
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update users table
            $updUser = $db->prepare("UPDATE users SET name = :name, mobile = :mobile WHERE id = :uid");
            $updUser->execute([
                ':name'   => $name,
                ':mobile' => $mobile,
                ':uid'    => $userId
            ]);

            // Update or Insert into user_profiles
            $checkProfile = $db->prepare("SELECT id FROM user_profiles WHERE user_id = :uid LIMIT 1");
            $checkProfile->execute([':uid' => $userId]);
            $profileExists = (bool)$checkProfile->fetchColumn();

            if ($profileExists) {
                $updProfile = $db->prepare("
                    UPDATE user_profiles 
                    SET headline = :headline,
                        bio = :bio,
                        avatar = :avatar,
                        skills = :skills,
                        location = :location,
                        github_url = :github,
                        linkedin_url = :linkedin,
                        portfolio_url = :portfolio,
                        updated_at = NOW()
                    WHERE user_id = :uid
                ");
            } else {
                $updProfile = $db->prepare("
                    INSERT INTO user_profiles (user_id, headline, bio, avatar, skills, location, github_url, linkedin_url, portfolio_url, updated_at)
                    VALUES (:uid, :headline, :bio, :avatar, :skills, :location, :github, :linkedin, :portfolio, NOW())
                ");
            }

            $updProfile->execute([
                ':uid'       => $userId,
                ':headline'  => $headline,
                ':bio'       => $bio,
                ':avatar'    => $avatarFilename,
                ':skills'    => $skills,
                ':location'  => $location,
                ':github'    => $githubUrl,
                ':linkedin'  => $linkedinUrl,
                ':portfolio' => $portfolioUrl
            ]);

            $db->commit();

            $_SESSION['user_name'] = $name;
            set_flash('success', 'Your profile settings have been updated successfully.');
            header('Location: ' . APP_URL . '/user/profile.php');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log('Profile update error: ' . $e->getMessage());
            $errors[] = 'Failed to update profile due to an internal server error.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3 col-xl-3 col-xxl-2">
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Profile Form Content -->
        <div class="col-lg-9 col-xl-9 col-xxl-10">
            <div class="custom-card card-static p-4 p-md-5 w-100">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <div>
                        <h3 class="fs-4 fw-bold mb-1">Account & Profile Settings</h3>
                        <p class="text-muted small mb-0">Manage your personal branding, technical skills, and social links</p>
                    </div>
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

                <form method="POST" action="<?= APP_URL ?>/user/profile.php" enctype="multipart/form-data" novalidate>
                    <?= csrf_field(); ?>

                    <!-- Avatar Section -->
                    <div class="d-flex align-items-center gap-4 mb-4 p-3 bg-light rounded-3 border">
                        <?php 
                            $avatarSrc = !empty($userData['avatar']) && file_exists(APP_ROOT . '/assets/uploads/avatars/' . $userData['avatar'])
                                ? ASSETS_URL . '/uploads/avatars/' . e($userData['avatar'])
                                : ASSETS_URL . '/images/profile-mukesh.png';
                        ?>
                        <img src="<?= $avatarSrc ?>" alt="Profile Avatar" class="rounded-circle border border-2 border-primary shadow-sm" style="width: 72px; height: 72px; object-fit: cover;">
                        <div>
                            <label for="avatar" class="form-label fw-semibold small mb-1">Update Profile Photograph</label>
                            <input class="form-control form-control-sm" type="file" id="avatar" name="avatar" accept="image/*">
                            <div class="form-text small">Accepted formats: JPG, PNG, WEBP (Max: 5MB).</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-xl-3">
                            <label for="name" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($userData['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <input type="email" class="form-control bg-light" id="email" value="<?= e($userData['email'] ?? '') ?>" readonly>
                            <div class="form-text small">Cannot be changed directly.</div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <label for="mobile" class="form-label fw-semibold small">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" value="<?= e($userData['mobile'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <label for="location" class="form-label fw-semibold small">Current Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?= e($userData['location'] ?? '') ?>" placeholder="e.g. Bengaluru, India">
                        </div>
                        <div class="col-md-6">
                            <label for="headline" class="form-label fw-semibold small">Professional Headline</label>
                            <input type="text" class="form-control" id="headline" name="headline" value="<?= e($userData['headline'] ?? '') ?>" placeholder="e.g. Full Stack Developer | Aspiring AI Engineer">
                        </div>
                        <div class="col-md-6">
                            <label for="skills" class="form-label fw-semibold small">Technical Skills (comma-separated)</label>
                            <input type="text" class="form-control" id="skills" name="skills" value="<?= e($userData['skills'] ?? '') ?>" placeholder="e.g. PHP, MySQL, React, Python, Docker">
                        </div>
                        <div class="col-12">
                            <label for="bio" class="form-label fw-semibold small">Biography / Summary</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Briefly describe your background, career interests, and tech journey..."><?= e($userData['bio'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <h5 class="fs-6 fw-bold text-dark mt-4 mb-3 pt-3 border-top">Social & Portfolio Links</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="linkedin_url" class="form-label fw-semibold small">LinkedIn URL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-primary"><i class="fa-brands fa-linkedin"></i></span>
                                <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" value="<?= e($userData['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="github_url" class="form-label fw-semibold small">GitHub URL</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-dark"><i class="fa-brands fa-github"></i></span>
                                <input type="url" class="form-control" id="github_url" name="github_url" value="<?= e($userData['github_url'] ?? '') ?>" placeholder="https://github.com/username">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="portfolio_url" class="form-label fw-semibold small">Personal Website</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-success"><i class="fa-solid fa-globe"></i></span>
                                <input type="url" class="form-control" id="portfolio_url" name="portfolio_url" value="<?= e($userData['portfolio_url'] ?? '') ?>" placeholder="https://yourwebsite.com">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom px-4 py-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Profile Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
