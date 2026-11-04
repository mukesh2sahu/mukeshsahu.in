<?php
/**
 * Logout
 * MukeshSahu Platform
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (is_logged_in()) {
    $userId = current_user_id();
    $db = get_db();
    
    // Clear remember token in db
    if ($userId) {
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
        $stmt->execute([':id' => $userId]);
    }

    // Unset remember cookie
    if (isset($_COOKIE['ms_remember'])) {
        setcookie('ms_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

// Clear all session data
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start fresh session for flash message
session_start();
set_flash('info', 'You have been successfully signed out.');

header('Location: ' . APP_URL . '/auth/login.php');
exit;
