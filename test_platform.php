<?php
/**
 * Automated End-to-End Verification Test Script
 * MukeshSahu Platform
 */

$baseUrl = 'http://127.0.0.1:8085';
$cookieFileUser = sys_get_temp_dir() . '/cookie_user.txt';
$cookieFileAdmin = sys_get_temp_dir() . '/cookie_admin.txt';

if (file_exists($cookieFileUser)) unlink($cookieFileUser);
if (file_exists($cookieFileAdmin)) unlink($cookieFileAdmin);

function http_request($url, $method = 'GET', $data = [], $cookieJar = null, $followRedirect = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);

    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return ['code' => $httpCode, 'header' => $header, 'body' => $body];
}

function extract_csrf($body) {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $body, $matches)) {
        return $matches[1];
    }
    return '';
}

echo "=== STARTING AUTOMATED PLATFORM VERIFICATION ===" . PHP_EOL . PHP_EOL;

$passCount = 0;
$failCount = 0;

function assert_test($description, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $description" . PHP_EOL;
        $passCount++;
    } else {
        echo " [FAIL] $description: $details" . PHP_EOL;
        $failCount++;
    }
}

// 1. Public Pages (Guest Access)
$publicPages = [
    '/index.php' => 'Welcome to <span class="gradient-text">MukeshSahu</span>',
    '/about.php' => 'Mukesh Sahu',
    '/blogs.php' => 'Technology & Engineering Insights',
    '/contact.php' => 'Contact Mukesh Sahu',
    '/blog-detail.php?slug=building-high-performance-php-applications-pdo' => 'Building High-Performance PHP Applications'
];

foreach ($publicPages as $path => $expectedText) {
    $res = http_request($baseUrl . $path);
    assert_test("Public page $path responds 200 OK", $res['code'] === 200, "Got code {$res['code']}");
    assert_test("Public page $path contains expected text", strpos($res['body'], $expectedText) !== false, "Expected '$expectedText'");
}

// 2. Restricted Pages (Guest Access must be 302 to login.php)
$restrictedPages = [
    '/jobs.php',
    '/courses.php',
    '/ai-tools.php',
    '/watch.php?course_id=1',
    '/user/dashboard.php',
    '/admin/dashboard.php'
];

foreach ($restrictedPages as $path) {
    $res = http_request($baseUrl . $path, 'GET', [], null, false);
    assert_test("Restricted page $path redirects guest with 302", $res['code'] === 302, "Got code {$res['code']}");
    assert_test("Restricted page $path redirects to login.php", strpos($res['header'], 'auth/login.php') !== false, "Header location check");
}

// 3. User Login
$loginPage = http_request($baseUrl . '/auth/login.php', 'GET', [], $cookieFileUser);
$csrf = extract_csrf($loginPage['body']);
assert_test("Extracted CSRF token from login page", !empty($csrf), "CSRF was empty");

$loginPost = http_request($baseUrl . '/auth/login.php', 'POST', [
    'email' => 'user@mukeshsahu.in',
    'password' => 'User@12345',
    'csrf_token' => $csrf
], $cookieFileUser, false);

assert_test("User login responds with 302 redirect on success", $loginPost['code'] === 302, "Got code {$loginPost['code']}");

// 4. Test Authenticated User Access to Restricted Portals
$userAccessPages = [
    '/jobs.php' => 'Explore IT Opportunities',
    '/courses.php' => 'Technology & Engineering Masterclasses',
    '/ai-tools.php' => 'Curated AI Tools & Coding Assistants',
    '/user/dashboard.php' => 'Welcome back',
    '/user/my-courses.php' => 'My Enrolled Courses',
    '/user/saved-jobs.php' => 'Saved IT Jobs',
    '/user/saved-tools.php' => 'Favorite AI Tools',
    '/user/profile.php' => 'Account & Profile Settings'
];

foreach ($userAccessPages as $path => $expectedText) {
    $res = http_request($baseUrl . $path, 'GET', [], $cookieFileUser);
    assert_test("Authenticated user can access $path (200 OK)", $res['code'] === 200, "Got code {$res['code']}");
    assert_test("Page $path contains expected content for user", strpos($res['body'], $expectedText) !== false, "Expected '$expectedText'");
}

// Normal user denied admin access
$adminAccessByNormalUser = http_request($baseUrl . '/admin/dashboard.php', 'GET', [], $cookieFileUser, false);
assert_test("Normal user denied access to /admin/dashboard.php (redirects)", $adminAccessByNormalUser['code'] === 302, "Got code {$adminAccessByNormalUser['code']}");

// 5. Admin Login & Access
$adminLoginPage = http_request($baseUrl . '/auth/login.php', 'GET', [], $cookieFileAdmin);
$csrfAdmin = extract_csrf($adminLoginPage['body']);

$adminLoginPost = http_request($baseUrl . '/auth/login.php', 'POST', [
    'email' => 'admin@mukeshsahu.in',
    'password' => 'Admin@12345',
    'csrf_token' => $csrfAdmin
], $cookieFileAdmin, false);

assert_test("Admin login responds with 302 redirect on success", $adminLoginPost['code'] === 302, "Got code {$adminLoginPost['code']}");

$adminPages = [
    '/admin/dashboard.php' => 'Platform Overview &amp; Analytics',
    '/admin/users/index.php' => 'User Management',
    '/admin/jobs/index.php' => 'IT Job Openings Management',
    '/admin/courses/index.php' => 'Course &amp; Video Curriculum Management',
    '/admin/courses/modules.php?course_id=1' => 'Curriculum &amp; Video Integration',
    '/admin/ai-tools/index.php' => 'AI Platforms &amp; Tools Directory Management',
    '/admin/blogs/index.php' => 'Technology Articles &amp; Tutorials Management',
    '/admin/contacts/index.php' => 'User Inquiries &amp; Messages',
    '/admin/reports/index.php' => 'Platform Reports &amp; Analytics',
    '/admin/settings/index.php' => 'General &amp; Personal Brand Configurations'
];

foreach ($adminPages as $path => $expectedText) {
    $res = http_request($baseUrl . $path, 'GET', [], $cookieFileAdmin);
    assert_test("Admin can access $path (200 OK)", $res['code'] === 200, "Got code {$res['code']}");
    assert_test("Admin page $path contains expected module header", strpos($res['body'], $expectedText) !== false, "Expected '$expectedText'");
}

// 6. Test CSV Export functionality
$csvTypes = ['users', 'enrollments', 'jobs', 'contacts'];
foreach ($csvTypes as $t) {
    $csvRes = http_request($baseUrl . '/admin/reports/export.php?type=' . $t, 'GET', [], $cookieFileAdmin);
    assert_test("Admin can export $t CSV (200 OK)", $csvRes['code'] === 200, "Got code {$csvRes['code']}");
    assert_test("Admin CSV for $t has Content-Type text/csv", strpos($csvRes['header'], 'text/csv') !== false, "Header check");
}

echo PHP_EOL . "=== VERIFICATION SUMMARY ===" . PHP_EOL;
echo "Passed: $passCount" . PHP_EOL;
echo "Failed: $failCount" . PHP_EOL;

if ($failCount === 0) {
    echo "SUCCESS: ALL 62 SYSTEM TESTS PASSED!" . PHP_EOL;
} else {
    echo "WARNING: SOME TESTS FAILED!" . PHP_EOL;
}
