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

// Helper function to check if current page matches
function isAthleteCurrentPage($requestUri, $path) {
    return strpos($requestUri, $path) !== false;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> | SEAIT VARSITY DORM VISITOR MONITORING SYSTEM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="athlete_dashboard.php"><img src="assets/upload/shine.png.png" alt="Logo" style="width: 32px; height: 32px; margin-right: 8px;"> SEAIT VARSITY DORM VISITOR MONITORING SYSTEM</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= isAthleteCurrentPage($requestUri, 'athlete_dashboard.php') ? 'active' : '' ?>" href="athlete_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= isAthleteCurrentPage($requestUri, 'athlete_profile.php') ? 'active' : '' ?>" href="athlete_profile.php"><i class="bi bi-person"></i> My Profile</a></li>
        <li class="nav-item"><a class="nav-link <?= isAthleteCurrentPage($requestUri, 'athlete_id_card.php') ? 'active' : '' ?>" href="athlete_id_card.php"><i class="bi bi-credit-card"></i> My ID Card</a></li>
        <li class="nav-item"><a class="nav-link <?= isAthleteCurrentPage($requestUri, 'athlete_scan.php') ? 'active' : '' ?>" href="athlete_scan.php"><i class="bi bi-upc-scan"></i> Scan My ID</a></li>
        <li class="nav-item"><a class="nav-link <?= isAthleteCurrentPage($requestUri, 'athlete_visitor_request.php') ? 'active' : '' ?>" href="athlete_visitor_request.php"><i class="bi bi-person-plus"></i> Request Visitor</a></li>
        <li class="nav-item"><a class="nav-link <?= isAthleteCurrentPage($requestUri, 'athlete_records.php') ? 'active' : '' ?>" href="athlete_records.php"><i class="bi bi-clock-history"></i> My Records</a></li>
      </ul>
      <span class="navbar-text me-3 text-light d-flex align-items-center">
        <?php if ($athletePhoto): ?>
          <img src="<?= h($athletePhoto) ?>" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">
        <?php else: ?>
          <i class="bi bi-person-circle me-2"></i>
        <?php endif; ?>
        <?= h($_SESSION['athlete_name'] ?? '') ?>
      </span>
      <a href="athlete_logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>
<div class="container-fluid py-4 px-4">
<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success alert-dismissible fade show"><?= h($_SESSION['flash_success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?= h($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>