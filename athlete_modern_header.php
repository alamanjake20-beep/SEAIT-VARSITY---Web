<?php
// Expects $pageTitle to be set by the including page.
if (!isset($pageTitle)) { $pageTitle = 'Athlete Dashboard'; }
require_once __DIR__ . '/includes/auth.php';
require_athlete_login();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Get current page for active state highlighting
$requestUri = $_SERVER['REQUEST_URI'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Get athlete photo for navbar
$athletePhoto = '';
try {
    $photoStmt = $pdo->prepare("SELECT photo FROM athletes WHERE id = :id");
    $photoStmt->execute(['id' => $_SESSION['athlete_id']]);
    $athletePhotoData = $photoStmt->fetch();
    if ($athletePhotoData && $athletePhotoData['photo']) {
        $athletePhoto = h($athletePhotoData['photo']);
    }
} catch (Exception $e) {
    // Handle database error gracefully
    $athletePhoto = '';
}

// Get unread notification count for athlete
$athleteId = (int) ($_SESSION['athlete_id'] ?? 0);
$unreadCount = get_unread_notification_count($pdo, 'athlete', $athleteId);

// Helper function to check if current page matches
function isAthleteCurrentPage($requestUri, $path) {
    return strpos($requestUri, $path) !== false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> | SEAIT VARSITY DORM VISITOR MONITORING SYSTEM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/modern-sidebar.css') ?>?v=3.0">
</head>
<body class="athlete-layout">
<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="assets/upload/cyrel.jpg" alt="Logo" style="border-radius: 50%; object-fit: cover;">
            <div class="sidebar-logo-text">
                SEAIT VARSITY DORM<br>VISITOR MONITORING SYSTEM
            </div>
        </div>
        <div class="sidebar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search menu..." id="sidebarSearch">
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="athlete_dashboard.php" class="sidebar-menu-link <?= isAthleteCurrentPage($requestUri, 'athlete_dashboard.php') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="athlete_profile.php" class="sidebar-menu-link <?= isAthleteCurrentPage($requestUri, 'athlete_profile.php') ? 'active' : '' ?>">
                    <i class="bi bi-person"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="athlete_id_card.php" class="sidebar-menu-link <?= isAthleteCurrentPage($requestUri, 'athlete_id_card.php') ? 'active' : '' ?>">
                    <i class="bi bi-credit-card"></i>
                    <span>My ID Card</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="athlete_scan.php" class="sidebar-menu-link <?= isAthleteCurrentPage($requestUri, 'athlete_scan.php') ? 'active' : '' ?>">
                    <i class="bi bi-upc-scan"></i>
                    <span>Scan My ID</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-divider"></div>
        <div class="sidebar-section-label">Visitor Management</div>

        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="athlete_visitor_request.php" class="sidebar-menu-link <?= isAthleteCurrentPage($requestUri, 'athlete_visitor_request.php') ? 'active' : '' ?>">
                    <i class="bi bi-person-plus"></i>
                    <span>Request Visitor</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="athlete_records.php" class="sidebar-menu-link <?= isAthleteCurrentPage($requestUri, 'athlete_records.php') ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>My Records</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-divider"></div>
        <div class="sidebar-section-label">Account</div>

        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="athlete_logout.php" class="sidebar-menu-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
    </div>
</aside>

<!-- Main Content -->
<main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h1><?= h($pageTitle) ?></h1>
        </div>
        <div class="top-bar-right">
            <!-- Notification Bell -->
            <div class="notification-wrapper">
                <button class="notification-bell" id="notificationBell" type="button">
                    <i class="bi bi-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                    <?php endif; ?>
                </button>
            </div>
            
            <div class="user-info">
                <div class="user-info-text">
                    <div class="name"><?= h($_SESSION['athlete_name'] ?? 'Athlete') ?></div>
                    <div class="role">Athlete</div>
                </div>
                <?php if ($athletePhoto): ?>
                    <img src="<?= h($athletePhoto) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef;">
                <?php else: ?>
                    <i class="bi bi-person-circle fs-2 text-muted"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= h($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= h($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Page Content -->
    <div class="page-content">

<!-- Notification Modal -->
<?php 
$userType = 'athlete';
$userId = $athleteId;
$notificationsPage = base_url('athlete_notifications.php');
require_once __DIR__ . '/includes/notification_modal.php';
?>

<!-- Notification Modal JavaScript -->
<script src="assets/js/notification-modal.js"></script>