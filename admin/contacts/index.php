<?php
/**
 * Admin Contact Management - Index
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'Contact Messages';
$pageHeading = 'User Inquiries & Messages';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Delete Message
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $msgId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $msgId > 0) {
        $del = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
        $del->execute([':id' => $msgId]);
        log_activity('Delete Contact Message', 'contacts', 'Deleted message #' . $msgId);
        set_flash('success', 'Message deleted.');
    } else {
        set_flash('danger', 'Invalid security token.');
    }
    header('Location: ' . ADMIN_URL . '/contacts/index.php');
    exit;
}

// Handle Mark Read / Unread
if (isset($_GET['action']) && $_GET['action'] === 'toggle_read') {
    $msgId = (int)($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (verify_csrf_token($token) && $msgId > 0) {
        $upd = $db->prepare("UPDATE contact_messages SET is_read = NOT is_read WHERE id = :id");
        $upd->execute([':id' => $msgId]);
        set_flash('success', 'Message read status updated.');
    }
    header('Location: ' . ADMIN_URL . '/contacts/index.php');
    exit;
}

// Fetch all messages
$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
?>

<div class="custom-card p-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 pb-2 border-bottom gap-3">
        <div>
            <h4 class="fs-5 fw-bold text-dark mb-1">Contact Messages & Inquiries</h4>
            <p class="text-muted small mb-0">Total <?= count($messages) ?> inquiries received through the contact form</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#ID</th>
                    <th>Sender Details</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Date Received</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr class="<?= !$m['is_read'] ? 'fw-bold table-light' : '' ?>">
                        <td class="text-muted"><?= $m['id'] ?></td>
                        <td>
                            <div class="text-dark"><?= e($m['name']) ?></div>
                            <div class="text-muted small fw-normal">
                                <a href="mailto:<?= e($m['email']) ?>" class="text-secondary"><?= e($m['email']) ?></a>
                                &bull; <?= e($m['mobile']) ?>
                            </div>
                        </td>
                        <td>
                            <a href="<?= ADMIN_URL ?>/contacts/view.php?id=<?= $m['id'] ?>" class="text-dark hover-primary text-decoration-none">
                                <?= e(truncate_text($m['subject'], 35)) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($m['is_read']): ?>
                                <span class="badge bg-light text-secondary border fw-normal">Read</span>
                            <?php else: ?>
                                <span class="badge bg-danger">New Unread</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($m['reply_notes'])): ?>
                                <i class="fa-solid fa-comment-dots text-primary" title="<?= e($m['reply_notes']) ?>" data-bs-toggle="tooltip"></i>
                            <?php else: ?>
                                <span class="text-muted small fw-normal">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="small text-muted fw-normal"><?= format_date($m['created_at']) ?></span>
                        </td>
                        <td class="text-end fw-normal">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= ADMIN_URL ?>/contacts/view.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Message">
                                    <i class="fa-solid fa-envelope-open-text"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/contacts/index.php?action=toggle_read&id=<?= $m['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-secondary" title="Toggle Read Status">
                                    <i class="fa-solid <?= $m['is_read'] ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                                </a>
                                <a href="<?= ADMIN_URL ?>/contacts/index.php?action=delete&id=<?= $m['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" title="Delete Message" onclick="return confirm('Delete this inquiry?');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
