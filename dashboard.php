<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Dashboard';

// Get status filters
$athleteStatusFilter = $_GET['athlete_status'] ?? '';
$visitorStatusFilter = $_GET['visitor_status'] ?? '';

// Build athlete query with status filter
$athleteSql = "SELECT COUNT(*) FROM access_logs";
$athleteParams = [];

if ($athleteStatusFilter) {
    $athleteSql .= " WHERE identity_status = :status";
    $athleteParams['status'] = $athleteStatusFilter;
}

$athleteStmt = $pdo->prepare($athleteSql);
$athleteStmt->execute($athleteParams);
$pendingAthleteChecks = $athleteStmt->fetchColumn();

// Build visitor query with status filter
$visitorSql = "SELECT COUNT(*) FROM visitor_logs";
$visitorParams = [];

if ($visitorStatusFilter) {
    $visitorSql .= " WHERE status = :status";
    $visitorParams['status'] = $visitorStatusFilter;
}

$visitorStmt = $pdo->prepare($visitorSql);
$visitorStmt->execute($visitorParams);
$pendingVisitorRequests = $visitorStmt->fetchColumn();

$accessLogTotal = $pdo->query("SELECT COUNT(*) FROM access_logs")->fetchColumn();
$visitorLogTotal = $pdo->query("SELECT COUNT(*) FROM visitor_logs")->fetchColumn();

// Build recent athletes query with status filter
$recentAthleteSql = "
    SELECT al.id, al.card_id, al.scan_time, a.first_name, a.last_name, a.sport, al.identity_status
    FROM access_logs al
    LEFT JOIN athletes a ON a.id = al.athlete_id
";
$recentAthleteParams = [];

if ($athleteStatusFilter) {
    $recentAthleteSql .= " WHERE al.identity_status = :status";
    $recentAthleteParams['status'] = $athleteStatusFilter;
}
$recentAthleteSql .= " ORDER BY al.scan_time DESC LIMIT 5";

$recentAthleteStmt = $pdo->prepare($recentAthleteSql);
$recentAthleteStmt->execute($recentAthleteParams);
$recentPendingAthletes = $recentAthleteStmt->fetchAll();

// Build recent visitors query with status filter
$recentVisitorSql = "
    SELECT vl.id, v.full_name AS visitor_name, a.first_name AS athlete_first_name, a.last_name AS athlete_last_name, 
           a.card_id, vl.status, vl.time_in
    FROM visitor_logs vl
    JOIN visitors v ON v.id = vl.visitor_id
    JOIN athletes a ON a.id = vl.athlete_id
";
$recentVisitorParams = [];

if ($visitorStatusFilter) {
    $recentVisitorSql .= " WHERE vl.status = :status";
    $recentVisitorParams['status'] = $visitorStatusFilter;
}
$recentVisitorSql .= " ORDER BY vl.time_in DESC LIMIT 5";

$recentVisitorStmt = $pdo->prepare($recentVisitorSql);
$recentVisitorStmt->execute($recentVisitorParams);
$recentPendingVisitors = $recentVisitorStmt->fetchAll();

$adminNotifications = get_notifications($pdo, 'admin', 0, 6);

require_once __DIR__ . '/includes/modern_header.php';
?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #a8d4f0, #d4ebf7); color: #0d3b66;">
        <i class="bi bi-person-check"></i>
      </div>
      <div class="stat-value"><?= (int)$pendingAthleteChecks ?></div>
      <div class="stat-label">Pending Athlete Checks</div>
      <div class="stat-trend up">
        <i class="bi bi-arrow-up"></i> Needs Attention
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #a8e6cf, #d4f5e7); color: #1d6e3c;">
        <i class="bi bi-people"></i>
      </div>
      <div class="stat-value"><?= (int)$pendingVisitorRequests ?></div>
      <div class="stat-label">Pending Visitor Requests</div>
      <div class="stat-trend up">
        <i class="bi bi-arrow-up"></i> Needs Review
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #f8c8dc, #fce4ed); color: #a52a2a;">
        <i class="bi bi-clipboard-data"></i>
      </div>
      <div class="stat-value"><?= (int)$accessLogTotal ?></div>
      <div class="stat-label">Access Records</div>
      <div class="stat-trend">
        <i class="bi bi-activity"></i> Total Logs
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card-enhanced">
      <div class="stat-icon" style="background: linear-gradient(135deg, #d8b4e0, #ecdaf0); color: #6f42c1;">
        <i class="bi bi-file-earmark-text"></i>
      </div>
      <div class="stat-value"><?= (int)$visitorLogTotal ?></div>
      <div class="stat-label">Visitor Records</div>
      <div class="stat-trend">
        <i class="bi bi-activity"></i> Total Visits
      </div>
    </div>
  </div>
</div>

<!-- Recent Activity Section -->
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-2">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold" style="font-size: 12px;"><i class="bi bi-person-check" style="color: #a8d4f0;"></i> Athlete Verification</h6>
        <a href="verification_requests.php" class="btn-action btn-secondary-action" style="font-size: 11px; padding: 6px 12px;">Manage &rarr;</a>
      </div>
      <div class="mb-2">
        <form method="get" class="d-flex gap-2 align-items-center">
          <select name="athlete_status" class="form-control form-control-sm" style="font-size: 11px; display: block;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Pending" <?= $athleteStatusFilter==='Pending'?'selected':'' ?>>Pending</option>
            <option value="Not Verified" <?= $athleteStatusFilter==='Not Verified'?'selected':'' ?>>Rejected</option>
            <option value="Verified" <?= $athleteStatusFilter==='Verified'?'selected':'' ?>>Approved</option>
          </select>
          <?php if ($athleteStatusFilter): ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary" style="font-size: 10px;">Clear</a>
          <?php endif; ?>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle modern-table">
          <thead><tr><th>Card ID</th><th>Athlete</th><th>Status</th><th>Time</th></tr></thead>
          <tbody>
          <?php if (!$recentPendingAthletes): ?>
            <tr><td colspan="4" class="text-center text-muted">No athlete verification requests.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentPendingAthletes as $log): ?>
            <tr>
              <td><span class="fw-semibold"><?= h($log['card_id']) ?></span></td>
              <td><?= h(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'Unknown') ?></td>
              <?php
              $badgeClass = 'badge-pending';
              if ($log['identity_status'] === 'Verified') {
                  $badgeClass = 'badge-completed';
              } elseif ($log['identity_status'] === 'Not Verified') {
                  $badgeClass = 'badge-overdue';
              }
              ?>
              <td><span class="badge badge-status <?= $badgeClass ?>"><?= h($log['identity_status']) ?></span></td>
              <td class="small text-muted"><?= format_datetime($log['scan_time']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-2">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold" style="font-size: 12px;"><i class="bi bi-people" style="color: #a8e6cf;"></i> Visitor Requests</h6>
        <a href="verification_requests.php" class="btn-action btn-secondary-action" style="font-size: 11px; padding: 6px 12px;">Manage &rarr;</a>
      </div>
      <div class="mb-2">
        <form method="get" class="d-flex gap-2 align-items-center">
          <select name="visitor_status" class="form-control form-control-sm" style="font-size: 11px; display: block;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Pending" <?= $visitorStatusFilter==='Pending'?'selected':'' ?>>Pending</option>
            <option value="Rejected" <?= $visitorStatusFilter==='Rejected'?'selected':'' ?>>Rejected</option>
            <option value="Inside" <?= $visitorStatusFilter==='Inside'?'selected':'' ?>>Approved (Inside)</option>
            <option value="Checked Out" <?= $visitorStatusFilter==='Checked Out'?'selected':'' ?>>Completed (Checked Out)</option>
          </select>
          <?php if ($visitorStatusFilter): ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary" style="font-size: 10px;">Clear</a>
          <?php endif; ?>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle modern-table">
          <thead><tr><th>Visitor</th><th>Athlete</th><th>Status</th><th>Time In</th></tr></thead>
          <tbody>
          <?php if (!$recentPendingVisitors): ?>
            <tr><td colspan="4" class="text-center text-muted">No visitor requests pending.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentPendingVisitors as $v): ?>
            <tr>
              <td><?= h($v['visitor_name']) ?></td>
              <td><?= h(($v['athlete_first_name'] ?? '') . ' ' . ($v['athlete_last_name'] ?? '')) ?> <small class="text-muted d-block"><?= h($v['card_id']) ?></small></td>
              <?php
              $badgeClass = 'badge-pending';
              if ($v['status'] === 'Inside') {
                  $badgeClass = 'badge-in-progress';
              } elseif ($v['status'] === 'Checked Out') {
                  $badgeClass = 'badge-completed';
              } elseif ($v['status'] === 'Rejected') {
                  $badgeClass = 'badge-overdue';
              }
              ?>
              <td><span class="badge badge-status <?= $badgeClass ?>"><?= h($v['status']) ?></span></td>
              <td class="small text-muted"><?= format_datetime($v['time_in']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Notifications Section -->
<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card p-2">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-bold" style="font-size: 12px;"><i class="bi bi-bell" style="color: #d8b4e0;"></i> Admin Notifications</h6>
        <a href="<?= base_url('notifications.php') ?>" class="btn-action btn-secondary-action" style="font-size: 11px; padding: 6px 12px;">View All &rarr;</a>
      </div>
      <?php if (!$adminNotifications): ?>
        <div class="text-center text-muted py-3">No recent admin notifications.</div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($adminNotifications as $note): ?>
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
  </div>
</div>

<?php require_once __DIR__ . '/includes/modern_footer.php'; ?>
