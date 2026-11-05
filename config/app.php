<?php
/**
 * Application Core Configuration
 * MukeshSahu Platform
 */

declare(strict_types=1);

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

// Global Output Buffering (ensures header() redirects never fail due to premature output)
if (!ob_get_level()) {
    ob_start();
}

// Determine root directory and application URL
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');

// Calculate base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Detect if running inside a subfolder like /mukeshsahu.in
// Find root path relative to the document root
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$appRootNorm = str_replace('\\', '/', APP_ROOT);

if ($docRoot && strpos($appRootNorm, $docRoot) === 0) {
    $subDir = substr($appRootNorm, strlen($docRoot));
    $baseUrl = rtrim($protocol . $host . $subDir, '/');
} else {
    // Fallback for built-in server or alias
    $baseUrl = rtrim($protocol . $host, '/');
}

define('APP_URL', $baseUrl);
define('ADMIN_URL', APP_URL . '/admin');
define('ASSETS_URL', APP_URL . '/assets');

// Brand Constants
define('APP_NAME', 'MukeshSahu');
define('APP_TAGLINE', 'Personal Learning & Technology Platform');
define('APP_VERSION', '1.0.0');

// Include Database
require_once APP_ROOT . '/config/database.php';

// Include Global Helper Functions
require_once APP_ROOT . '/includes/functions.php';
