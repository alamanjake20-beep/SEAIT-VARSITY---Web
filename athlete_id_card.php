<?php
$pageTitle = 'My ID Card';
require_once __DIR__ . '/athlete_modern_header.php';

// Get athlete information
$athleteId = $_SESSION['athlete_id'];
$stmt = $pdo->prepare("SELECT * FROM athletes WHERE id = :id");
$stmt->execute(['id' => $athleteId]);
$athlete = $stmt->fetch();

if (!$athlete) {
    $_SESSION['flash_error'] = 'Athlete record not found.';
    header('Location: athlete_dashboard.php');
    exit;
}

$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($athlete['card_id']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print ID Card</button>
    
  </div>
</div>

<div class="id-card">
  <div class="text-center mb-2">
    <small class="text-uppercase" style="letter-spacing:1px;">SEAIT VARSITY ATHLETE ID CARD</small>
  </div>
  <div class="d-flex gap-3 align-items-center">
    <?php if ($athlete['photo']): ?>
      <img src="<?= h($athlete['photo']) ?>" class="photo-box">
    <?php else: ?>
      <div class="photo-box d-flex align-items-center justify-content-center">
        <i class="bi bi-person-fill fs-1 text-secondary"></i>
      </div>
    <?php endif; ?>
    <div>
      <h5 class="mb-0"><?= h(full_name($athlete)) ?></h5>
      <div class="small"><?= h($athlete['sport']) ?></div>
      <div class="small"><?= h($athlete['course'] ?: '') ?> <?= $athlete['year_level'] ? '• '.h($athlete['year_level']) : '' ?></div>
    </div>
  </div>
  <hr style="border-color: rgba(255,255,255,.3);">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <div class="small">Card ID</div>
      <div class="fw-bold fs-5"><?= h($athlete['card_id']) ?></div>
      <div class="small mt-2">Status: <?= h($athlete['status']) ?></div>
    </div>
    <div class="qr-box">
      <img src="<?= h($qrUrl) ?>" alt="QR Code" width="90" height="90">
    </div>
  </div>
</div>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  
  .id-card, .id-card * {
    visibility: visible;
  }
  
  .id-card {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    margin: 0;
    padding: 18px;
    width: 340px;
    background: linear-gradient(135deg, #ffd4a0, #ffe8c8) !important;
    border: 2px solid #e0b080 !important;
    border-radius: 16px !important;
    color: #212529 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  .id-card .photo-box {
    width: 100px !important;
    height: 100px !important;
    border-radius: 10px !important;
    background: #fff !important;
    object-fit: cover !important;
    border: 3px solid #e0b080 !important;
  }
  
  .id-card .qr-box {
    background: #fff !important;
    padding: 6px !important;
    border-radius: 8px !important;
    border: 2px solid #e0b080 !important;
  }
  
  .id-card .small, .id-card .fw-bold, .id-card .fs-5 {
    color: #212529 !important;
    visibility: visible !important;
  }
  
  .id-card .qr-box img {
    visibility: visible !important;
  }
  
  body {
    background: #fff !important;
    margin: 0;
    padding: 0;
  }
  
  nav, header, footer, .no-print, .btn, a {
    display: none !important;
  }
  
  .id-card .d-flex.justify-content-between {
    display: flex !important;
  }
  
  .id-card hr {
    border-color: rgba(0,0,0,.2) !important;
    visibility: visible !important;
  }
}
</style>

<?php require_once __DIR__ . '/athlete_modern_footer.php'; ?>