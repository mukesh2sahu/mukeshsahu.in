<?php
/**
 * Authentication Middleware & Access Control
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/**
 * Enforce user authentication
 * Redirects guests to login page with return URL preservation
 */
function require_login(?string $customRedirect = null): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $customRedirect ?? ($_SERVER['REQUEST_URI'] ?? APP_URL . '/index.php');
        set_flash('warning', 'Please sign in or register to access this section.');
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }

    // Verify account status (active/banned)
    $db = get_db();
    $stmt = $db->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => current_user_id()]);
    $status = $stmt->fetchColumn();

    if ($status === 'banned' || $status === 'inactive') {
        session_destroy();
        session_start();
        set_flash('danger', 'Your account has been deactivated or suspended. Please contact support.');
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Enforce Admin Authentication
 */
function require_admin(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? ADMIN_URL . '/dashboard.php';
        set_flash('warning', 'Please log in with administrator credentials.');
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }

    if (!is_admin()) {
        set_flash('danger', 'Access denied. You do not have administrator permissions.');
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

/**
 * Enforce Super Admin Authentication
 */
function require_super_admin(): void {
    require_admin();

    if (!is_super_admin()) {
        set_flash('danger', 'Access denied. Super Administrator role required for this action.');
        header('Location: ' . ADMIN_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Guest-only pages (e.g. login, register, forgot-password)
 */
function guest_only(): void {
    if (is_logged_in()) {
        if (is_admin()) {
            header('Location: ' . ADMIN_URL . '/dashboard.php');
        } else {
            header('Location: ' . APP_URL . '/user/dashboard.php');
        }
        exit;
    }
}
