<?php
/**
 * Admin Index Redirect
 */
require_once __DIR__ . '/../config/app.php';
header('Location: ' . ADMIN_URL . '/dashboard.php');
exit;
