<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Verification Requests';

// Get status filters
$athleteStatusFilter = $_GET['athlete_status'] ?? '';
$visitorStatusFilter = $_GET['visitor_status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_type'], $_POST['request_id'], $_POST['decision'])) {
    $requestType = $_POST['request_type'];
    $requestId = (int) $_POST['request_id'];
    $decision = $_POST['decision'];

    if ($requestType === 'athlete') {
        $stmt = $pdo->prepare("SELECT al.*, a.first_name, a.last_name FROM access_logs al LEFT JOIN athletes a ON a.id = al.athlete_id WHERE al.id = :id LIMIT 1");
        $stmt->execute(['id' => $requestId]);
        $log = $stmt->fetch();

        if ($log) {
            $status = $decision === 'approve' ? 'Verified' : 'Not Verified';
            $note = $decision === 'approve'
                ? ' | Identity confirmed by staff.'
                : ' | Identity mismatch flagged by staff.';

            $pdo->prepare("UPDATE access_logs SET scan_result = :scan_result, identity_status = :status, verified_by = :uid, remarks = CONCAT(COALESCE(remarks,''), :note) WHERE id = :id")
                ->execute([
                    'scan_result' => $decision === 'approve' ? 'Valid' : 'Invalid',
                    'status' => $status,
                    'uid' => $_SESSION['user_id'],
                    'note' => $note,
                    'id' => $requestId,
                ]);

            $athleteId = (int) ($log['athlete_id'] ?? 0);
            $athleteName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'An athlete';

            if ($athleteId > 0) {
                create_notification(
                    $pdo,
                    'athlete',
                    $athleteId,
                    $decision === 'approve' ? 'ID Approved' : 'ID Rejected',
                    $decision === 'approve'
                        ? 'Your athlete ID verification was approved by the admin. Your scan result is now valid.'
                        : 'Your athlete ID verification was rejected by the admin. Please contact the dorm office for review.',
                    'access_log',
                    $requestId
                );
            }

            // Create notification for admin about athlete verification decision
            create_notification(
                $pdo,
                'admin',
                0,
                $decision === 'approve' ? 'Athlete ID Verified' : 'Athlete ID Rejected',
                'Athlete ' . $athleteName . ' ID verification has been ' . $decision . 'ed.',
                'access_log',
                $requestId
            );

            $_SESSION['flash_success'] = $decision === 'approve'
                ? 'Athlete verification approved successfully.'
                : 'Athlete verification rejected successfully.';
        }
    } elseif ($requestType === 'visitor') {
        $stmt = $pdo->prepare("SELECT vl.*, v.full_name as visitor_name FROM visitor_logs vl JOIN visitors v ON v.id = vl.visitor_id WHERE vl.id = :id LIMIT 1");
        $stmt->execute(['id' => $requestId]);
        $log = $stmt->fetch();

        if ($log) {
            $status = $decision === 'approve' ? 'Inside' : 'Rejected';
            $remark = $decision === 'approve'
                ? 'Visitor request approved by staff. Visitor is now inside the premises.'
                : 'Visitor request rejected by staff.';

            $pdo->prepare("UPDATE visitor_logs SET status = :status, remarks = :remark, logged_by = :uid WHERE id = :id")
                ->execute([
                    'status' => $status,
                    'remark' => $remark,
                    'uid' => $_SESSION['user_id'],
                    'id' => $requestId,
                ]);

            $athleteId = (int) ($log['athlete_id'] ?? 0);
            $visitorName = $log['visitor_name'] ?? 'A visitor';

            if ($athleteId > 0) {
                create_notification(
                    $pdo,
                    'athlete',
                    $athleteId,
                    $decision === 'approve' ? 'Visitor Request Approved' : 'Visitor Request Rejected',
                    $decision === 'approve'
                        ? 'Your visitor request for ' . $visitorName . ' was approved. Visitor is now inside the premises.'
                        : 'Your visitor request for ' . $visitorName . ' was rejected by the admin. Please contact the dorm office for more details.',
                    'visitor_log',
                    $requestId
                );
            }

            // Create notification for admin about visitor request decision
            create_notification(
                $pdo,
                'admin',
                0,
                $decision === 'approve' ? 'Visitor Request Approved' : 'Visitor Request Rejected',
                'Visitor request for ' . $visitorName . ' has been ' . $decision . 'ed.',
                'visitor_log',
                $requestId
            );

            $_SESSION['flash_success'] = $decision === 'approve'
                ? 'Visitor request approved successfully.'
                : 'Visitor request rejected successfully.';
        }
    }

    header('Location: verification_requests.php');
    exit;
}

// Build athlete query with status filter
$athleteSql = "
    SELECT al.id, al.card_id, al.scan_time, al.identity_status, al.remarks,
           a.first_name, a.last_name, a.sport, a.course, a.year_level
    FROM access_logs al
    LEFT JOIN athletes a ON a.id = al.athlete_id
";
$athleteParams = [];

if ($athleteStatusFilter) {
    $athleteSql .= " WHERE al.identity_status = :status";
    $athleteParams['status'] = $athleteStatusFilter;
}
$athleteSql .= " ORDER BY al.scan_time DESC";

$athleteStmt = $pdo->prepare($athleteSql);
$athleteStmt->execute($athleteParams);
$athleteRequests = $athleteStmt->fetchAll();

// Build visitor query with status filter
$visitorSql = "
    SELECT vl.id, vl.status, vl.time_in, vl.remarks,
           v.full_name AS visitor_name, v.contact_number, v.purpose,
           a.first_name AS athlete_first_name, a.last_name AS athlete_last_name, a.card_id
    FROM visitor_logs vl
    JOIN visitors v ON v.id = vl.visitor_id
    JOIN athletes a ON a.id = vl.athlete_id
";
$visitorParams = [];

if ($visitorStatusFilter) {
    $visitorSql .= " WHERE vl.status = :status";
    $visitorParams['status'] = $visitorStatusFilter;
}
$visitorSql .= " ORDER BY vl.time_in DESC";

$visitorStmt = $pdo->prepare($visitorSql);
$visitorStmt->execute($visitorParams);
$visitorRequests = $visitorStmt->fetchAll();

require_once __DIR__ . '/includes/modern_header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-actions">
        <a href="index.php" class="btn-action btn-secondary-action">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row g-4">
  <div class="col-xl-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-person-check"></i> Athlete Verification</h6>
        <span class="badge bg-secondary"><?= count($athleteRequests) ?> records</span>
      </div>
      <div class="mb-3" style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
        <strong>Filter by Status:</strong>
        <form method="get" style="margin-top: 5px;">
          <select name="athlete_status" class="form-control" onchange="this.form.submit()">
            <option value="">All Statuses (Pending, Rejected, Approved)</option>
            <option value="Pending" <?= $athleteStatusFilter==='Pending'?'selected':'' ?>>Pending</option>
            <option value="Not Verified" <?= $athleteStatusFilter==='Not Verified'?'selected':'' ?>>Rejected</option>
            <option value="Verified" <?= $athleteStatusFilter==='Verified'?'selected':'' ?>>Approved</option>
          </select>
          <?php if ($athleteStatusFilter): ?>
            <a href="verification_requests.php" class="btn btn-sm btn-outline-secondary" style="margin-top: 5px;">Clear Filter</a>
          <?php endif; ?>
        </form>
      </div>
      <?php if (!$athleteRequests): ?>
        <div class="empty-state">
            <i class="bi bi-check-circle empty-state-icon"></i>
            <div class="empty-state-title">No Records Found</div>
            <div class="empty-state-text">No athlete verification records with the selected status.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle modern-table">
            <thead>
              <tr>
                <th>Card ID</th>
                <th>Athlete</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($athleteRequests as $r): ?>
              <tr>
                <td><span class="fw-semibold"><?= h($r['card_id']) ?></span></td>
                <td>
                  <div class="fw-semibold"><?= h(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))) ?></div>
                  <small class="text-muted"><?= h($r['sport'] ?: '—') ?> • <?= h($r['course'] ?: '—') ?></small>
                </td>
                <td>
                  <?php
                  $badgeClass = 'badge-pending';
                  if ($r['identity_status'] === 'Verified') {
                      $badgeClass = 'badge-completed';
                  } elseif ($r['identity_status'] === 'Not Verified') {
                      $badgeClass = 'badge-overdue';
                  }
                  ?>
                  <span class="badge badge-status <?= $badgeClass ?>">
                    <?= h($r['identity_status']) ?>
                  </span>
                </td>
                <td>
                  <?php if (in_array($r['identity_status'], ['Pending', 'Not Verified'])): ?>
                  <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="request_type" value="athlete">
                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" name="decision" value="approve" class="action-btn action-btn-primary">Approve</button>
                    <button type="submit" name="decision" value="reject" class="action-btn action-btn-danger">Reject</button>
                  </form>
                  <?php else: ?>
                  <span class="text-muted small">Completed</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-people"></i> Visitor Requests</h6>
        <span class="badge bg-secondary"><?= count($visitorRequests) ?> records</span>
      </div>
      <div class="mb-3" style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
        <strong>Filter by Status:</strong>
        <form method="get" style="margin-top: 5px;">
          <select name="visitor_status" class="form-control" onchange="this.form.submit()">
            <option value="">All Statuses (Pending, Rejected, Approved, Completed)</option>
            <option value="Pending" <?= $visitorStatusFilter==='Pending'?'selected':'' ?>>Pending</option>
            <option value="Rejected" <?= $visitorStatusFilter==='Rejected'?'selected':'' ?>>Rejected</option>
            <option value="Inside" <?= $visitorStatusFilter==='Inside'?'selected':'' ?>>Approved (Inside)</option>
            <option value="Checked Out" <?= $visitorStatusFilter==='Checked Out'?'selected':'' ?>>Completed (Checked Out)</option>
          </select>
          <?php if ($visitorStatusFilter): ?>
            <a href="verification_requests.php" class="btn btn-sm btn-outline-secondary" style="margin-top: 5px;">Clear Filter</a>
          <?php endif; ?>
        </form>
      </div>
      <?php if (!$visitorRequests): ?>
        <div class="empty-state">
            <i class="bi bi-check-circle empty-state-icon"></i>
            <div class="empty-state-title">No Records Found</div>
            <div class="empty-state-text">No visitor records with the selected status.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle modern-table">
            <thead>
              <tr>
                <th>Visitor</th>
                <th>Athlete</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($visitorRequests as $r): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= h($r['visitor_name']) ?></div>
                  <small class="text-muted"><?= h($r['contact_number'] ?: 'No contact') ?></small>
                </td>
                <td>
                  <div class="fw-semibold"><?= h(trim(($r['athlete_first_name'] ?? '') . ' ' . ($r['athlete_last_name'] ?? ''))) ?></div>
                  <small class="text-muted"><?= h($r['card_id']) ?></small>
                </td>
                <td>
                  <?php
                  $badgeClass = 'badge-pending';
                  if ($r['status'] === 'Inside') {
                      $badgeClass = 'badge-in-progress';
                  } elseif ($r['status'] === 'Checked Out') {
                      $badgeClass = 'badge-completed';
                  } elseif ($r['status'] === 'Rejected') {
                      $badgeClass = 'badge-overdue';
                  }
                  ?>
                  <span class="badge badge-status <?= $badgeClass ?>">
                    <?= h($r['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($r['status'] === 'Pending'): ?>
                  <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="request_type" value="visitor">
                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" name="decision" value="approve" class="action-btn action-btn-primary">Approve</button>
                    <button type="submit" name="decision" value="reject" class="action-btn action-btn-danger">Reject</button>
                  </form>
                  <?php elseif ($r['status'] === 'Inside'): ?>
                  <a href="visitors/checkout.php?id=<?= (int)$r['id'] ?>" class="action-btn action-btn-primary" onclick="return confirm('Check out this visitor?');">Check Out</a>
                  <?php else: ?>
                  <span class="text-muted small">Completed</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/modern_footer.php'; ?>
