<?php
$pageTitle = 'Athlete Dashboard';
require_once __DIR__ . '/athlete_modern_header.php';

// Get athlete information
$athleteId = $_SESSION['athlete_id'];
$stmt = $pdo->prepare("SELECT * FROM athletes WHERE id = :id");
$stmt->execute(['id' => $athleteId]);
$athlete = $stmt->fetch();

// Get recent access logs
$accessStmt = $pdo->prepare("
    SELECT * FROM access_logs 
    WHERE athlete_id = :athlete_id 
    ORDER BY scan_time DESC 
    LIMIT 5
");
$accessStmt->execute(['athlete_id' => $athleteId]);
$recentAccess = $accessStmt->fetchAll();

// Get recent visitor logs
$visitorStmt = $pdo->prepare("
    SELECT vl.*, v.full_name as visitor_name 
    FROM visitor_logs vl 
    JOIN visitors v ON vl.visitor_id = v.id 
    WHERE vl.athlete_id = :athlete_id 
    ORDER BY time_in DESC 
    LIMIT 5
");
$visitorStmt->execute(['athlete_id' => $athleteId]);
$recentVisitors = $visitorStmt->fetchAll();

$athleteNotifications = get_notifications($pdo, 'athlete', $athleteId, 6);
?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #a8d4f0, #d4ebf7); color: #0d3b66;">
        <i class="bi bi-upc-scan"></i>
      </div>
      <div class="stat-value"><?= count($recentAccess) ?></div>
      <div class="stat-label">Total Scans</div>
      <div class="stat-trend">
        <i class="bi bi-activity"></i> Access Logs
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #a8e6cf, #d4f5e7); color: #1d6e3c;">
        <i class="bi bi-people"></i>
      </div>
      <div class="stat-value"><?= count($recentVisitors) ?></div>
      <div class="stat-label">Visitors</div>
      <div class="stat-trend">
        <i class="bi bi-activity"></i> Guest Log
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #f8c8dc, #fce4ed); color: #a52a2a;">
        <i class="bi bi-credit-card"></i>
      </div>
      <div class="stat-value"><?= h(substr($athlete['card_id'], -4)) ?></div>
      <div class="stat-label">Card ID</div>
      <div class="stat-trend">
        <i class="bi bi-shield-check"></i> ID Status
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #d8b4e0, #ecdaf0); color: #6f42c1;">
        <i class="bi bi-check-circle"></i>
      </div>
      <div class="stat-value"><?= h($athlete['status']) ?></div>
      <div class="stat-label">Status</div>
      <div class="stat-trend up">
        <i class="bi bi-check"></i> Active
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">Quick Actions</h5>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <a href="athlete_profile.php" class="text-decoration-none">
      <div class="task-card hover-shadow">
        <div class="task-card-header">
            <h6 class="task-card-title"><i class="bi bi-person" style="color: #a8d4f0;"></i> My Profile</h6>
        </div>
        <div class="task-card-meta">
            <small class="text-muted">View personal information</small>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="athlete_id_card.php" class="text-decoration-none">
      <div class="task-card hover-shadow">
        <div class="task-card-header">
            <h6 class="task-card-title"><i class="bi bi-credit-card" style="color: #a8e6cf;"></i> My ID Card</h6>
        </div>
        <div class="task-card-meta">
            <small class="text-muted">View/verify registered ID</small>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="athlete_visitor_request.php" class="text-decoration-none">
      <div class="task-card hover-shadow">
        <div class="task-card-header">
            <h6 class="task-card-title"><i class="bi bi-person-plus" style="color: #f8c8dc;"></i> Request Visitor</h6>
        </div>
        <div class="task-card-meta">
            <small class="text-muted">Register a visitor</small>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="athlete_records.php" class="text-decoration-none">
      <div class="task-card hover-shadow">
        <div class="task-card-header">
            <h6 class="task-card-title"><i class="bi bi-clock-history" style="color: #d8b4e0;"></i> My Records</h6>
        </div>
        <div class="task-card-meta">
            <small class="text-muted">View visitor/access records</small>
        </div>
      </div>
    </a>
  </div>
</div>

<!-- Notifications Section -->
<div class="card p-2 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold" style="font-size: 12px;"><i class="bi bi-bell" style="color: #d8b4e0;"></i> Recent Notifications</h6>
        <a href="<?= base_url('athlete_notifications.php') ?>" class="btn-action btn-secondary-action" style="font-size: 11px; padding: 6px 12px;">View All &rarr;</a>
    </div>
    <?php if (!$athleteNotifications): ?>
        <div class="text-center text-muted py-3">No recent notifications.</div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($athleteNotifications as $note): ?>
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <strong><?= h($note['title']) ?></strong>
                            <div class="small text-muted mt-1"><?= h($note['message']) ?></div>
                        </div>
                        <small class="text-muted text-nowrap"><?= format_datetime($note['created_at']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Activity -->
<div class="row">
  <div class="col-md-6">
    <div class="card p-2">
      <h6 class="mb-2 fw-bold" style="font-size: 12px;"><i class="bi bi-clock-history" style="color: #d8b4e0;"></i> Recent Access Logs</h6>
      <?php if ($recentAccess): ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle modern-table">
            <thead>
              <tr>
                <th>Date/Time</th>
                <th>Result</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentAccess as $log): ?>
                <?php
                  $displayResult = $log['identity_status'] === 'Verified'
                      ? 'Valid'
                      : ($log['identity_status'] === 'Pending' ? 'Pending' : 'Invalid');
                ?>
                <tr>
                  <td><?= date('M d, Y h:i A', strtotime($log['scan_time'])) ?></td>
                  <td>
                    <span class="badge badge-status <?= $displayResult === 'Valid' ? 'badge-completed' : ($displayResult === 'Pending' ? 'badge-pending' : 'badge-overdue') ?>">
                      <?= h($displayResult) ?>
                    </span>
                  </td>
                  <td><?= h($log['identity_status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted text-center">No recent access logs</p>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-2">
      <h6 class="mb-2 fw-bold" style="font-size: 12px;"><i class="bi bi-people" style="color: #a8e6cf;"></i> Recent Visitors</h6>
      <?php if ($recentVisitors): ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle modern-table">
            <thead>
              <tr>
                <th>Visitor</th>
                <th>Time In</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentVisitors as $visitor): ?>
                <?php
                  $visitorDisplayStatus = in_array($visitor['status'], ['Approved', 'Inside']) ? 'Valid' : ($visitor['status'] === 'Rejected' ? 'Invalid' : 'Pending');
                ?>
                <tr>
                  <td><?= h($visitor['visitor_name']) ?></td>
                  <td><?= date('M d, Y h:i A', strtotime($visitor['time_in'])) ?></td>
                  <td>
                    <span class="badge badge-status <?= $visitorDisplayStatus === 'Valid' ? 'badge-completed' : ($visitorDisplayStatus === 'Invalid' ? 'badge-overdue' : 'badge-pending') ?>">
                      <?= h($visitorDisplayStatus) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted text-center">No recent visitors</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/athlete_modern_footer.php'; ?>