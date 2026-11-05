<?php
/**
 * Admin Panel Dashboard
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Overview Dashboard';
$pageHeading = 'Platform Overview & Analytics';
require_once __DIR__ . '/includes/header.php';

$db = get_db();

// 1. Fetch Key Stats
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalJobs = (int)$db->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$totalCourses = (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalLectures = (int)$db->query("SELECT COUNT(*) FROM course_lectures")->fetchColumn();
$totalAiTools = (int)$db->query("SELECT COUNT(*) FROM ai_tools")->fetchColumn();
$totalBlogs = (int)$db->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$totalEnrollments = (int)$db->query("SELECT COUNT(*) FROM course_enrollments")->fetchColumn();
$totalInquiries = (int)$db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadInquiries = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

// 2. Job Categories Distribution for Chart.js
$jobCatsStmt = $db->query("
    SELECT c.name, COUNT(j.id) as job_count 
    FROM job_categories c 
    LEFT JOIN jobs j ON c.id = j.category_id 
    GROUP BY c.id 
    HAVING job_count > 0 
    LIMIT 6
");
$jobCats = $jobCatsStmt->fetchAll();
$jobCatLabels = array_column($jobCats, 'name');
$jobCatData = array_column($jobCats, 'job_count');

// 3. Course Enrollment Breakdown for Chart.js
$courseEnrollStmt = $db->query("
    SELECT c.title, COUNT(e.id) as enroll_count 
    FROM courses c 
    LEFT JOIN course_enrollments e ON c.id = e.course_id 
    GROUP BY c.id 
    ORDER BY enroll_count DESC 
    LIMIT 5
");
$courseEnrolls = $courseEnrollStmt->fetchAll();
$courseLabels = array_map(function($t) { return truncate_text($t, 20); }, array_column($courseEnrolls, 'title'));
$courseData = array_column($courseEnrolls, 'enroll_count');

// 4. Fetch Recent Activity Logs
$logsStmt = $db->query("
    SELECT l.*, u.name as admin_name 
    FROM admin_activity_logs l 
    LEFT JOIN users u ON l.admin_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 6
");
$recentLogs = $logsStmt->fetchAll();

// 5. Recent Inquiries
$inquiriesStmt = $db->query("
    SELECT id, name, email, subject, is_read, created_at 
    FROM contact_messages 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentInquiries = $inquiriesStmt->fetchAll();
?>

<!-- Analytical Stat Cards Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Users</span>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <h3 class="fs-2 fw-bold text-dark mb-1"><?= $totalUsers ?></h3>
            <div class="small text-muted">
                <a href="<?= ADMIN_URL ?>/users/index.php" class="text-primary fw-semibold">Manage Users &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">IT Jobs</span>
                <div class="stat-icon-wrapper bg-success-subtle text-success">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
            </div>
            <h3 class="fs-2 fw-bold text-dark mb-1"><?= $totalJobs ?></h3>
            <div class="small text-muted">
                <a href="<?= ADMIN_URL ?>/jobs/index.php" class="text-success fw-semibold">View Job Listings &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Courses</span>
                <div class="stat-icon-wrapper bg-purple-subtle text-purple" style="background-color: var(--accent-purple-light); color: var(--accent-purple);">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>
            <h3 class="fs-2 fw-bold text-dark mb-1"><?= $totalCourses ?></h3>
            <div class="small text-muted">
                <span><?= $totalLectures ?> Total Lectures &bull; </span>
                <a href="<?= ADMIN_URL ?>/courses/index.php" class="fw-semibold">Manage &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">AI Tools</span>
                <div class="stat-icon-wrapper bg-info-subtle text-info">
                    <i class="fa-solid fa-robot"></i>
                </div>
            </div>
            <h3 class="fs-2 fw-bold text-dark mb-1"><?= $totalAiTools ?></h3>
            <div class="small text-muted">
                <a href="<?= ADMIN_URL ?>/ai-tools/index.php" class="text-info fw-semibold">Manage Directory &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- Analytical Stat Cards Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Blogs</span>
                <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                    <i class="fa-solid fa-newspaper"></i>
                </div>
            </div>
            <h3 class="fs-2 fw-bold text-dark mb-1"><?= $totalBlogs ?></h3>
            <div class="small text-muted">
                <a href="<?= ADMIN_URL ?>/blogs/index.php" class="text-warning fw-semibold">Write / Edit Blogs &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='<?= ADMIN_URL ?>/courses/enrollments.php'">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Enrollments</span>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            </div>
            <h3 class="fs-2 fw-bold text-dark mb-1"><?= $totalEnrollments ?></h3>
            <div class="small text-muted">
                <a href="<?= ADMIN_URL ?>/courses/enrollments.php" class="text-primary fw-semibold text-decoration-none">View Enrollments &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Messages</span>
                <div class="stat-icon-wrapper bg-danger-subtle text-danger">
                    <i class="fa-solid fa-envelope"></i>
                </div>
            </div>
            <h3 class="fs-2 fw-bold text-dark mb-1"><?= $totalInquiries ?></h3>
            <div class="small text-muted">
                <span class="badge bg-danger"><?= $unreadInquiries ?> Unread</span>
                <a href="<?= ADMIN_URL ?>/contacts/index.php" class="ms-1 fw-semibold text-danger">Inbox &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">System</span>
                <div class="stat-icon-wrapper bg-dark-subtle text-dark">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
            <h3 class="fs-5 fw-bold text-dark mb-1">PHP <?= PHP_VERSION ?></h3>
            <div class="small text-muted">MySQL 8.0 / MariaDB Active</div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
    <!-- User Registration & Enrollments Chart -->
    <div class="col-lg-7">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fs-6 fw-bold text-dark mb-0">Platform Growth & Activity</h5>
                <span class="badge bg-light text-secondary border">Year 2026</span>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="growthChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Job Categories Distribution Chart -->
    <div class="col-lg-5">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fs-6 fw-bold text-dark mb-0">Job Openings by Category</h5>
                <span class="badge bg-light text-secondary border">Distribution</span>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="jobCategoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Inquiries & Activity Logs Row -->
<div class="row g-4">
    <!-- Recent Inquiries -->
    <div class="col-lg-6">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="fs-6 fw-bold text-dark mb-0">Recent Contact Inquiries</h5>
                <a href="<?= ADMIN_URL ?>/contacts/index.php" class="small fw-semibold text-primary">View All</a>
            </div>

            <?php if (!empty($recentInquiries)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead>
                            <tr class="text-muted">
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInquiries as $inq): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($inq['name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= e($inq['email']) ?></div>
                                    </td>
                                    <td><?= e(truncate_text($inq['subject'], 25)) ?></td>
                                    <td><?= time_ago($inq['created_at']) ?></td>
                                    <td>
                                        <?php if ($inq['is_read']): ?>
                                            <span class="badge bg-light text-secondary border">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">New</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted small my-3">No contact inquiries yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin Activity Logs -->
    <div class="col-lg-6">
        <div class="custom-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="fs-6 fw-bold text-dark mb-0">System Activity Logs</h5>
                <span class="badge bg-light text-muted border">Real-time</span>
            </div>

            <?php if (!empty($recentLogs)): ?>
                <div class="timeline ps-3" style="font-size: 0.85rem;">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="timeline-item mb-3">
                            <div class="timeline-dot" style="top: 2px;"></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark"><?= e($log['action']) ?></span>
                                <span class="text-muted small"><?= time_ago($log['created_at']) ?></span>
                            </div>
                            <div class="text-secondary small"><?= e($log['details']) ?></div>
                            <div class="text-muted small">By: <?= e($log['admin_name'] ?? 'System') ?> (<?= e($log['ip_address']) ?>)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted small my-3">No recorded admin activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Scripts Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Growth Line/Bar Chart
    const ctxGrowth = document.getElementById('growthChart');
    if (ctxGrowth) {
        new Chart(ctxGrowth, {
            type: 'line',
            data: {
                labels: ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'],
                datasets: [
                    {
                        label: 'User Signups',
                        data: [12, 19, 28, 42, 65, 84],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Course Enrollments',
                        data: [8, 14, 22, 35, 52, 70],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // 2. Job Categories Doughnut Chart
    const ctxJobs = document.getElementById('jobCategoryChart');
    if (ctxJobs) {
        new Chart(ctxJobs, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(!empty($jobCatLabels) ? $jobCatLabels : ['PHP', 'AI/ML', 'React', 'Python', 'Internships']) ?>,
                datasets: [{
                    data: <?= json_encode(!empty($jobCatData) ? $jobCatData : [2, 1, 1, 1, 1]) ?>,
                    backgroundColor: [
                        '#2563eb',
                        '#8b5cf6',
                        '#06b6d4',
                        '#10b981',
                        '#f59e0b',
                        '#64748b'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
