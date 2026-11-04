<?php
/**
 * Admin Contact Management - View Inquiry
 * MukeshSahu Platform
 */

declare(strict_types=1);

$pageTitle = 'View Contact Message';
$pageHeading = 'Inquiry Details';
require_once __DIR__ . '/../includes/header.php';

$msgId = (int)($_GET['id'] ?? 0);
$db = get_db();

$stmt = $db->prepare("SELECT * FROM contact_messages WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $msgId]);
$msg = $stmt->fetch();

if (!$msg) {
    set_flash('danger', 'Message not found.');
    header('Location: ' . ADMIN_URL . '/contacts/index.php');
    exit;
}

// Mark as read automatically when viewed
if (!$msg['is_read']) {
    $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id")->execute([':id' => $msgId]);
}

// Handle updating reply notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $notes = trim($_POST['reply_notes'] ?? '');
        $upd = $db->prepare("UPDATE contact_messages SET reply_notes = :notes WHERE id = :id");
        $upd->execute([':notes' => $notes, ':id' => $msgId]);
        log_activity('Update Inquiry Notes', 'contacts', 'Updated admin notes on message #' . $msgId);
        set_flash('success', 'Admin follow-up notes updated.');
        header('Location: ' . ADMIN_URL . '/contacts/view.php?id=' . $msgId);
        exit;
    }
}
?>

<div class="row g-4">
    <!-- Message Content -->
    <div class="col-lg-8">
        <div class="custom-card p-4 p-md-5 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <span class="badge bg-primary-subtle text-primary border small mb-1">Inquiry #<?= $msg['id'] ?></span>
                    <h3 class="fs-4 fw-bold text-dark mb-0"><?= e($msg['subject']) ?></h3>
                </div>
                <a href="<?= ADMIN_URL ?>/contacts/index.php" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Inquiries
                </a>
            </div>

            <!-- Sender Info Card -->
            <div class="p-3 bg-light rounded-3 border mb-4">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="small text-muted">Sender Name:</div>
                        <div class="fw-bold text-dark"><?= e($msg['name']) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-muted">Email:</div>
                        <a href="mailto:<?= e($msg['email']) ?>" class="fw-semibold text-primary"><?= e($msg['email']) ?></a>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-muted">Mobile:</div>
                        <div class="fw-bold text-dark"><?= e($msg['mobile']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted">Received Date:</div>
                        <div class="text-secondary small"><?= format_date($msg['created_at'], 'M d, Y - h:i A') ?> (<?= time_ago($msg['created_at']) ?>)</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted">Sender IP Address:</div>
                        <div class="text-secondary small font-monospace"><?= e($msg['ip_address'] ?? '127.0.0.1') ?></div>
                    </div>
                </div>
            </div>

            <!-- Message Body -->
            <h5 class="fs-6 fw-bold text-dark mb-2">Message Content:</h5>
            <div class="p-4 bg-white rounded-3 border mb-4" style="line-height: 1.8; min-height: 160px;">
                <?= nl2br(e($msg['message'])) ?>
            </div>

            <!-- Action buttons -->
            <div class="d-flex gap-2">
                <a href="mailto:<?= e($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn btn-primary-custom">
                    <i class="fa-solid fa-reply me-1"></i> Send Reply via Email
                </a>
                <a href="<?= ADMIN_URL ?>/contacts/index.php?action=delete&id=<?= $msg['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this message?');">
                    <i class="fa-solid fa-trash-can me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>

    <!-- Admin Follow-up Notes Column -->
    <div class="col-lg-4">
        <div class="custom-card p-4">
            <h5 class="fs-6 fw-bold text-dark mb-3">
                <i class="fa-solid fa-clipboard-list text-primary me-2"></i> Admin Reply / Follow-up Notes
            </h5>
            <p class="small text-muted mb-3">Keep internal tracking notes about communication with this user.</p>

            <form method="POST" action="<?= ADMIN_URL ?>/contacts/view.php?id=<?= $msg['id'] ?>">
                <?= csrf_field(); ?>

                <div class="mb-3">
                    <textarea class="form-control" name="reply_notes" rows="6" placeholder="e.g. Called user on mobile, discussed AI course requirements on Nov 4..."><?= e($msg['reply_notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-outline-primary w-100 py-2 btn-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Follow-up Notes
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
