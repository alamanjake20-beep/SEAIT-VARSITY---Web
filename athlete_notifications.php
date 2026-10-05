<?php
$pageTitle = 'My Notifications';
require_once __DIR__ . '/includes/auth.php';
require_athlete_login();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$athleteId = (int) ($_SESSION['athlete_id'] ?? 0);

// Mark all notifications as read when viewing the page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    mark_notifications_read($pdo, 'athlete', $athleteId);
    $_SESSION['flash_success'] = 'All notifications marked as read.';
    header('Location: athlete_notifications.php');
    exit;
}

// Also mark notifications as read when viewing the page
mark_notifications_read($pdo, 'athlete', $athleteId);

// Get all notifications for this athlete
$notifications = get_notifications($pdo, 'athlete', $athleteId, 50);

require_once __DIR__ . '/athlete_modern_header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-actions">
        <?php if (!empty($notifications)): ?>
            <form method="post" class="d-inline">
                <button type="submit" name="mark_read" value="1" class="btn-action btn-secondary-action">
                    <i class="bi bi-check2-all"></i> Mark All as Read
                </button>
            </form>
        <?php endif; ?>
        <a href="athlete_dashboard.php" class="btn-action btn-secondary-action">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="card p-4">
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="bi bi-bell-slash empty-state-icon"></i>
            <div class="empty-state-title">No Notifications</div>
            <div class="empty-state-text">You're all caught up! No new notifications.</div>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $note): ?>
                <div class="list-group-item px-0 <?= $note['is_read'] == 0 ? 'bg-light' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <strong><?= h($note['title']) ?></strong>
                                <?php if ($note['is_read'] == 0): ?>
                                    <span class="badge badge-status badge-pending rounded-pill">New</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted mb-1"><?= h($note['message']) ?></div>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> <?= format_datetime($note['created_at']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/athlete_modern_footer.php'; ?>
