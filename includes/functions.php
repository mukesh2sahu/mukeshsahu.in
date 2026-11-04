<?php
/**
 * Global Helper Functions
 * MukeshSahu Platform
 */

declare(strict_types=1);

/**
 * Escape HTML output for XSS prevention
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF form input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 */
function get_flash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render Flash Messages as Bootstrap Alerts
 */
function display_flash(): string {
    $messages = get_flash();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="flash-messages-container my-3">';
    foreach ($messages as $msg) {
        $alertType = match ($msg['type']) {
            'error'   => 'danger',
            'warning' => 'warning',
            'info'    => 'info',
            default   => 'success',
        };
        $icon = match ($alertType) {
            'danger'  => 'fa-circle-exclamation',
            'warning' => 'fa-triangle-exclamation',
            'info'    => 'fa-circle-info',
            default   => 'fa-circle-check',
        };

        $html .= sprintf(
            '<div class="alert alert-%s alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="fa-solid %s me-2 fs-5"></i>
                <div class="flex-grow-1">%s</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>',
            e($alertType),
            $icon,
            e($msg['message'])
        );
    }
    $html .= '</div>';

    return $html;
}

/**
 * Authentication Helpers
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => (int)$_SESSION['user_id'],
        'name'      => $_SESSION['user_name'] ?? '',
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? 'user',
        'role_id'   => $_SESSION['user_role_id'] ?? 3,
        'role_name' => $_SESSION['user_role_display'] ?? 'Registered User',
        'avatar'    => $_SESSION['user_avatar'] ?? null
    ];
}

function is_admin(): bool {
    if (!is_logged_in()) {
        return false;
    }
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['admin', 'super_admin'], true);
}

function is_super_admin(): bool {
    if (!is_logged_in()) {
        return false;
    }
    return ($_SESSION['user_role'] ?? '') === 'super_admin';
}

/**
 * Brute-force Login Protection
 */
function check_login_attempts(string $email): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT failed_login_attempts, locked_until FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && $user['locked_until']) {
        $lockedUntil = strtotime($user['locked_until']);
        if ($lockedUntil > time()) {
            return false; // Still locked
        } else {
            // Unlock after timeout
            $unlock = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE email = :email");
            $unlock->execute([':email' => $email]);
        }
    }
    return true; // Not locked
}

function record_failed_login(string $email): void {
    $db = get_db();
    $stmt = $db->prepare("SELECT id, failed_login_attempts FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $attempts = $user['failed_login_attempts'] + 1;
        $lockedUntil = null;
        if ($attempts >= 5) {
            // Lock for 15 minutes
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }
        $update = $db->prepare("UPDATE users SET failed_login_attempts = :attempts, locked_until = :locked WHERE id = :id");
        $update->execute([
            ':attempts' => $attempts,
            ':locked'   => $lockedUntil,
            ':id'       => $user['id']
        ]);
    }
}

function reset_login_attempts(string $email): void {
    $db = get_db();
    $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE email = :email");
    $stmt->execute([':email' => $email]);
}

/**
 * Log Admin Activity
 */
function log_activity(string $action, string $module, string $details = ''): void {
    try {
        $db = get_db();
        $adminId = current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $db->prepare("INSERT INTO admin_activity_logs (admin_id, action, module, details, ip_address) VALUES (:admin_id, :action, :module, :details, :ip)");
        $stmt->execute([
            ':admin_id' => $adminId,
            ':action'   => $action,
            ':module'   => $module,
            ':details'  => $details,
            ':ip'       => $ip
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Settings Helper
 */
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    $db = get_db();

    if ($settingsCache === null) {
        $stmt = $db->query("SELECT setting_key, setting_value FROM website_settings");
        $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Update Setting Helper
 */
function update_setting(string $key, string $value, string $group = 'general'): bool {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO website_settings (setting_key, setting_value, setting_group) 
                          VALUES (:k, :v, :g) 
                          ON DUPLICATE KEY UPDATE setting_value = :v2, setting_group = :g2, updated_at = NOW()");
    return $stmt->execute([
        ':k'  => $key,
        ':v'  => $value,
        ':g'  => $group,
        ':v2' => $value,
        ':g2' => $group
    ]);
}

/**
 * Slug generator
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a-' . time() : $text;
}

/**
 * Human-friendly date formatting
 */
function format_date(?string $datetime, string $format = 'M d, Y'): string {
    if (empty($datetime)) {
        return 'N/A';
    }
    return date($format, strtotime($datetime));
}

/**
 * Time ago formatter
 */
function time_ago(?string $datetime): string {
    if (empty($datetime)) return 'N/A';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}

/**
 * Truncate text
 */
function truncate_text(string $text, int $limit = 120, string $end = '...'): string {
    $clean = strip_tags($text);
    if (mb_strlen($clean) <= $limit) {
        return $clean;
    }
    return mb_substr($clean, 0, $limit) . $end;
}

/**
 * Extract YouTube Video ID from any standard YouTube URL or raw ID
 */
function extract_youtube_id(string $urlOrId): string {
    $urlOrId = trim($urlOrId);
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $urlOrId, $match)) {
        return $match[1];
    }
    // Check if it's already an 11-char ID
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $urlOrId)) {
        return $urlOrId;
    }
    return $urlOrId;
}

/**
 * File upload helper with MIME verification
 */
function upload_image_file(array $file, string $subfolder = 'uploads'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
    }

    $maxBytes = 5 * 1024 * 1024; // 5MB limit
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed limit of 5MB.'];
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid file format. Allowed: JPG, PNG, WEBP, GIF.'];
    }

    $ext = $allowedMimes[$mime];
    $filename = uniqid('file_', true) . '.' . $ext;

    $targetDir = APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subfolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file to destination directory.'];
    }

    return [
        'success'  => true,
        'filename' => $filename,
        'path'     => 'uploads/' . $subfolder . '/' . $filename
    ];
}

/**
 * Bookmarks & Enrollments status helpers
 */
function is_job_saved(int $userId, int $jobId): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT id FROM saved_jobs WHERE user_id = :uid AND job_id = :jid LIMIT 1");
    $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
    return (bool)$stmt->fetch();
}

function is_tool_saved(int $userId, int $toolId): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT id FROM saved_ai_tools WHERE user_id = :uid AND tool_id = :tid LIMIT 1");
    $stmt->execute([':uid' => $userId, ':tid' => $toolId]);
    return (bool)$stmt->fetch();
}

function is_enrolled(int $userId, int $courseId): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT id FROM course_enrollments WHERE user_id = :uid AND course_id = :cid LIMIT 1");
    $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
    return (bool)$stmt->fetch();
}

function is_lecture_completed(int $userId, int $courseId, int $lectureId): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT id FROM lecture_progress WHERE user_id = :uid AND course_id = :cid AND lecture_id = :lid AND is_completed = 1 LIMIT 1");
    $stmt->execute([':uid' => $userId, ':cid' => $courseId, ':lid' => $lectureId]);
    return (bool)$stmt->fetch();
}

/**
 * Recalculate Course Progress Percentage
 */
function recalculate_course_progress(int $userId, int $courseId): float {
    $db = get_db();
    
    // Count total lectures in this course
    $stmt = $db->prepare("SELECT COUNT(*) FROM course_lectures WHERE course_id = :cid");
    $stmt->execute([':cid' => $courseId]);
    $totalLectures = (int)$stmt->fetchColumn();

    if ($totalLectures === 0) {
        return 0.0;
    }

    // Count completed lectures for user
    $stmt = $db->prepare("SELECT COUNT(*) FROM lecture_progress WHERE user_id = :uid AND course_id = :cid AND is_completed = 1");
    $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
    $completedLectures = (int)$stmt->fetchColumn();

    $percentage = round(($completedLectures / $totalLectures) * 100, 2);
    if ($percentage > 100.0) $percentage = 100.0;

    $completedAt = ($percentage >= 100.0) ? date('Y-m-d H:i:s') : null;

    $update = $db->prepare("UPDATE course_enrollments SET progress_percentage = :perc, completed_at = :completed_at, last_accessed_at = NOW() WHERE user_id = :uid AND course_id = :cid");
    $update->execute([
        ':perc'         => $percentage,
        ':completed_at' => $completedAt,
        ':uid'          => $userId,
        ':cid'          => $courseId
    ]);

    return $percentage;
}
