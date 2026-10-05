<?php
$pageTitle = 'My Profile';
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
?>

<div class="row">
  <div class="col-md-4">
    <div class="card p-4 text-center stat-card-enhanced">
      <?php if ($athlete['photo']): ?>
        <img src="<?= h($athlete['photo']) ?>" class="img-fluid rounded mb-3" style="max-width: 200px; border-radius: 12px; border: 3px solid #e9ecef;">
      <?php else: ?>
        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="width: 200px; height: 200px; margin: 0 auto; border-radius: 12px;">
          <i class="bi bi-person-fill fs-1 text-muted"></i>
        </div>
      <?php endif; ?>
      <h5 class="fw-bold"><?= h(full_name($athlete)) ?></h5>
      <p class="text-muted mb-2"><?= h($athlete['sport']) ?></p>
      <span class="badge badge-status badge-completed"><?= h($athlete['status']) ?></span>
      <div class="mt-3">
        <small class="text-muted">Card ID: <strong><?= h($athlete['card_id']) ?></strong></small>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-4">
      <h5 class="mb-4 fw-bold"><i class="bi bi-person"></i> Personal Information</h5>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Card ID</label>
          <div class="fw-bold"><?= h($athlete['card_id']) ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Full Name</label>
          <div class="fw-bold"><?= h(full_name($athlete)) ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Sport</label>
          <div><span class="badge bg-light text-dark"><?= h($athlete['sport']) ?></span></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Course</label>
          <div><?= h($athlete['course'] ?: 'N/A') ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Year Level</label>
          <div><?= h($athlete['year_level'] ?: 'N/A') ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Gender</label>
          <div><?= h($athlete['gender']) ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Birthdate</label>
          <div><?= h($athlete['birthdate'] ?: 'N/A') ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Contact Number</label>
          <div><?= h($athlete['contact_number'] ?: 'N/A') ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Guardian Name</label>
          <div><?= h($athlete['guardian_name'] ?: 'N/A') ?></div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Guardian Contact</label>
          <div><?= h($athlete['guardian_contact'] ?: 'N/A') ?></div>
        </div>
        <div class="col-12 mb-3">
          <label class="form-label-modern">Address</label>
          <div><?= h($athlete['address'] ?: 'N/A') ?></div>
        </div>
      </div>
      
      <hr>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Status</label>
          <div>
            <span class="badge badge-status <?= $athlete['status'] === 'Active' ? 'badge-completed' : 'badge-overdue' ?>">
              <?= h($athlete['status']) ?>
            </span>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-modern">Registered Since</label>
          <div><?= date('F d, Y', strtotime($athlete['created_at'])) ?></div>
        </div>
      </div>
      
     
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/athlete_modern_footer.php'; ?>