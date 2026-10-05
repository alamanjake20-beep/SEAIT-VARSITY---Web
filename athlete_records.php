<?php
$pageTitle = 'My Records';
require_once __DIR__ . '/athlete_modern_header.php';

$athleteId = $_SESSION['athlete_id'];

// Get access logs
$accessStmt = $pdo->prepare("
    SELECT * FROM access_logs 
    WHERE athlete_id = :athlete_id 
    ORDER BY scan_time DESC 
    LIMIT 20
");
$accessStmt->execute(['athlete_id' => $athleteId]);
$accessLogs = $accessStmt->fetchAll();

// Get visitor logs
$visitorStmt = $pdo->prepare("
    SELECT vl.*, v.full_name as visitor_name, v.contact_number 
    FROM visitor_logs vl 
    JOIN visitors v ON vl.visitor_id = v.id 
    WHERE vl.athlete_id = :athlete_id 
    ORDER BY time_in DESC 
    LIMIT 20
");
$visitorStmt->execute(['athlete_id' => $athleteId]);
$visitorLogs = $visitorStmt->fetchAll();
?>

<ul class="nav nav-tabs mb-4" id="recordsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="access-tab" data-bs-toggle="tab" data-bs-target="#access" type="button" role="tab">
      <i class="bi bi-upc-scan"></i> Access Logs
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="visitor-tab" data-bs-toggle="tab" data-bs-target="#visitor" type="button" role="tab">
      <i class="bi bi-people"></i> Visitor Records
    </button>
  </li>
</ul>

<div class="tab-content" id="recordsTabsContent">
  <!-- Access Logs Tab -->
  <div class="tab-pane fade show active" id="access" role="tabpanel">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Access Logs</h5>
        <span class="badge bg-info"><?= count($accessLogs) ?> records</span>
      </div>
      
      <?php if ($accessLogs): ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date/Time</th>
                <th>Card ID</th>
                <th>Scan Result</th>
                <th>Identity Status</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($accessLogs as $log): ?>
                <?php
                  $displayResult = $log['identity_status'] === 'Verified'
                      ? 'Valid'
                      : ($log['identity_status'] === 'Pending' ? 'Pending' : 'Invalid');
                ?>
                <tr>
                  <td><?= date('M d, Y h:i A', strtotime($log['scan_time'])) ?></td>
                  <td><?= h($log['card_id']) ?></td>
                  <td>
                    <span class="badge <?= $displayResult === 'Valid' ? 'badge-valid' : ($displayResult === 'Pending' ? 'badge-pending' : 'badge-invalid') ?>">
                      <?= h($displayResult) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= $log['identity_status'] === 'Verified' ? 'bg-success' : ($log['identity_status'] === 'Not Verified' ? 'bg-danger' : 'badge-pending') ?>">
                      <?= h($log['identity_status']) ?>
                    </span>
                  </td>
                  <td><?= h($log['remarks'] ?: '-') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4">
          <i class="bi bi-upc-scan fs-1 text-muted mb-2"></i>
          <p class="text-muted">No access logs found</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Visitor Records Tab -->
  <div class="tab-pane fade" id="visitor" role="tabpanel">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Visitor Records</h5>
        <div>
          <a href="athlete_visitor_request.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus"></i> Request Visitor
          </a>
        </div>
      </div>
      
      <?php if ($visitorLogs): ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Visitor Name</th>
                <th>Contact</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($visitorLogs as $visitor): ?>
                <?php
                  $visitorDisplayStatus = in_array($visitor['status'], ['Approved', 'Inside']) ? 'Valid' : ($visitor['status'] === 'Rejected' ? 'Invalid' : 'Pending');
                  $visitorBadgeClass = $visitorDisplayStatus === 'Valid' ? 'bg-success' : ($visitorDisplayStatus === 'Invalid' ? 'bg-danger' : 'badge-pending');
                ?>
                <tr>
                  <td><?= h($visitor['visitor_name']) ?></td>
                  <td><?= h($visitor['contact_number'] ?: '-') ?></td>
                  <td><?= date('M d, Y h:i A', strtotime($visitor['time_in'])) ?></td>
                  <td><?= $visitor['time_out'] ? date('M d, Y h:i A', strtotime($visitor['time_out'])) : '-' ?></td>
                  <td>
                    <span class="badge <?= $visitorBadgeClass ?>">
                      <?= h($visitorDisplayStatus) ?>
                    </span>
                  </td>
                  <td><?= h($visitor['remarks'] ?: '-') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4">
          <i class="bi bi-people fs-1 text-muted mb-2"></i>
          <p class="text-muted">No visitor records found</p>
          <a href="athlete_visitor_request.php" class="btn btn-primary">
            <i class="bi bi-plus"></i> Request Your First Visitor
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/athlete_modern_footer.php'; ?>