<?php
$pageTitle = 'Request Visitor';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_athlete_login();

$athleteId = $_SESSION['athlete_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitorName = trim($_POST['visitor_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $idPresented = trim($_POST['id_presented'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if ($visitorName === '') {
        $error = 'Visitor name is required.';
    } else {
        try {
            // Check if visitor already exists (by full_name)
            $checkStmt = $pdo->prepare("SELECT id FROM visitors WHERE full_name = :full_name LIMIT 1");
            $checkStmt->execute(['full_name' => $visitorName]);
            $existingVisitor = $checkStmt->fetch();

            if ($existingVisitor) {
                // Use existing visitor ID
                $visitorId = $existingVisitor['id'];
                // Update visitor info if new details provided
                if ($contactNumber || $idPresented || $purpose) {
                    $updateStmt = $pdo->prepare("
                        UPDATE visitors 
                        SET contact_number = COALESCE(:contact_number, contact_number),
                            id_presented = COALESCE(:id_presented, id_presented),
                            purpose = COALESCE(:purpose, purpose)
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        'contact_number' => $contactNumber ?: null,
                        'id_presented' => $idPresented ?: null,
                        'purpose' => $purpose ?: null,
                        'id' => $visitorId
                    ]);
                }
            } else {
                // Insert new visitor record
                $stmt = $pdo->prepare("
                    INSERT INTO visitors (full_name, contact_number, id_presented, purpose)
                    VALUES (:full_name, :contact_number, :id_presented, :purpose)
                ");
                $stmt->execute([
                    'full_name' => $visitorName,
                    'contact_number' => $contactNumber,
                    'id_presented' => $idPresented,
                    'purpose' => $purpose
                ]);
                $visitorId = $pdo->lastInsertId();
            }

            // Create visitor log entry (pending staff approval)
            $logStmt = $pdo->prepare("
                INSERT INTO visitor_logs (athlete_id, visitor_id, status, remarks)
                VALUES (:athlete_id, :visitor_id, 'Pending', 'Visitor request submitted by athlete')
            ");
            $logStmt->execute([
                'athlete_id' => $athleteId,
                'visitor_id' => $visitorId
            ]);

            $requestId = $pdo->lastInsertId();
            $athleteName = $pdo->prepare("SELECT first_name, last_name FROM athletes WHERE id = :id LIMIT 1");
            $athleteName->execute(['id' => $athleteId]);
            $athleteRecord = $athleteName->fetch();
            $athleteDisplayName = trim(($athleteRecord['first_name'] ?? '') . ' ' . ($athleteRecord['last_name'] ?? '')) ?: 'An athlete';

            create_notification(
                $pdo,
                'admin',
                0,
                'Visitor Request Pending Review',
                $athleteDisplayName . ' requested approval for visitor ' . $visitorName . '.',
                'visitor_log',
                (int) $requestId
            );

            $_SESSION['flash_success'] = 'Visitor request submitted successfully. Staff will review and approve the request.';
            header('Location: athlete_records.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Error submitting visitor request. Please try again.';
        }
    }
}

// Now include the header after POST processing (no more header() calls)
require_once __DIR__ . '/athlete_modern_header.php';
?>

<div class="row">
  <div class="col-md-8">
    <div class="card p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
      <?php endif; ?>
      
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Visitor Name <span class="text-danger">*</span></label>
          <input type="text" name="visitor_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Contact Number</label>
          <input type="text" name="contact_number" class="form-control" placeholder="Optional">
        </div>
        <div class="mb-3">
          <label class="form-label">ID Presented</label>
          <input type="text" name="id_presented" class="form-control" placeholder="e.g., Driver's License, Student ID">
        </div>
        <div class="mb-3">
          <label class="form-label">Purpose of Visit</label>
          <textarea name="purpose" class="form-control" rows="3" placeholder="Reason for visit"></textarea>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send"></i> Submit Request
          </button>
          <a href="athlete_dashboard.php" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-4">
      <h5 class="mb-3"><i class="bi bi-info-circle"></i> Visitor Request Guidelines</h5>
      <ul class="small text-muted">
        <li class="mb-2">All visitor requests must be approved by dorm staff before access is granted.</li>
        <li class="mb-2">Provide accurate visitor information for security purposes.</li>
        <li class="mb-2">Visitors must present valid identification upon arrival.</li>
        <li class="mb-2">You will be notified when your visitor request is approved.</li>
        <li>Visitors must check out when leaving the premises.</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/athlete_modern_footer.php'; ?>