<?php
require_once __DIR__ . '/includes/auth.php';
require_athlete_login();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Scan My ID';
$athleteId = $_SESSION['athlete_id'];
$cardId = $_SESSION['athlete_card_id'];

// Get athlete information
$stmt = $pdo->prepare("SELECT * FROM athletes WHERE id = :id");
$stmt->execute(['id' => $athleteId]);
$athlete = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scannedCardId = trim($_POST['card_id'] ?? '');
    $isAjax = !empty($_POST['ajax']);

    if ($scannedCardId === '') {
        if ($isAjax) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please scan your card ID.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Please scan your card ID.';
        header('Location: athlete_scan.php');
        exit;
    }

    // Verify the scanned card matches the logged-in athlete
    if ($scannedCardId === $cardId && $athlete['status'] === 'Active') {
        // Record the scan as pending approval; the card is not marked valid until the admin confirms it.
        $insert = $pdo->prepare("
            INSERT INTO access_logs (card_id, athlete_id, scan_result, identity_status, remarks)
            VALUES (:card_id, :athlete_id, 'Invalid', 'Pending', 'Waiting for admin approval after self-scan')
        ");
        $insert->execute([
            'card_id' => $scannedCardId,
            'athlete_id' => $athleteId
        ]);

        $logId = $pdo->lastInsertId();
        $athleteName = trim(($athlete['first_name'] ?? '') . ' ' . ($athlete['last_name'] ?? '')) ?: 'An athlete';

        // Create notification for admin about athlete ID scan
        create_notification(
            $pdo,
            'admin',
            0,
            'Athlete ID Scan Pending Review',
            $athleteName . ' (' . $scannedCardId . ') scanned their ID and is waiting for admin verification.',
            'access_log',
            (int) $logId
        );

        // Create notification for the athlete about their scan
        create_notification(
            $pdo,
            'athlete',
            $athleteId,
            'ID Scan Recorded',
            'Your ID card scan has been recorded and is pending admin verification.',
            'access_log',
            (int) $logId
        );

        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'message' => 'Your ID has been submitted for admin approval. Please wait.',
                'auto_redirect' => true
            ]);
            exit;
        }

        $_SESSION['flash_success'] = 'Your ID has been submitted for admin approval. Please wait for confirmation.';
        header('Location: athlete_dashboard.php');
        exit;
    } else {
        if ($isAjax) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Card does not match your profile.']);
            exit;
        }

        $_SESSION['flash_error'] = 'Card does not match your profile or account is inactive.';
        header('Location: athlete_scan.php');
        exit;
    }
}

require_once __DIR__ . '/athlete_modern_header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card p-4">
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Scan your ID card to verify your identity and access your dashboard.
      </div>

      <!-- QR Code Scanner -->
      <div id="scannerContainer" class="text-center" style="position: relative;">
        <div id="qrVideo" style="width: 100%; height: 500px; background: #000; border-radius: 8px; position: relative; overflow: hidden;"></div>
        <div id="loadingIndicator" class="text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; width: 100%; pointer-events: none;">
          <div class="spinner-border text-light mb-2" role="status" style="width: 3rem; height: 3rem;"></div>
          <p class="text-light small">Initializing camera...</p>
        </div>
        
        <p class="text-muted small mb-3 mt-3">
          <i class="bi bi-info-circle"></i> Point camera at QR code on your ID card
        </p>
        
        <div class="d-flex gap-2 mb-2">
          <button type="button" class="btn btn-sm btn-secondary flex-fill" id="toggleCameraBtn">
            <i class="bi bi-camera-video-off"></i> Stop Camera
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" id="switchCameraBtn">
            <i class="bi bi-camera"></i> Switch Camera
          </button>
        </div>
        
        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="toggleManualBtn">
          <i class="bi bi-keyboard"></i> Enter Manually Instead
        </button>
      </div>

      <!-- Manual Entry Fallback -->
      <div id="manualEntryContainer" class="d-none">
        <p class="text-muted small mb-2">Enter your Card ID manually:</p>
        <form method="post" id="scanForm">
          <input type="text" name="card_id" id="card_id" class="form-control form-control-lg text-center mb-3"
                 placeholder="Your Card ID (e.g. VAR-2026-0001)" autocomplete="off" autofocus>
          <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-search"></i> Verify My Card</button>
        </form>
        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="toggleScannerBtn">
          <i class="bi bi-camera"></i> Back to Camera
        </button>
      </div>
    </div>
  </div>
</div>

<!-- QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrCode = null;
let cameraActive = true;
let lastScanCardId = null;
let lastScanTime = null;
let scanningActive = false;
let currentCameraId = null;
let cameras = [];

const qrVideo = document.getElementById('qrVideo');
const scannerContainer = document.getElementById('scannerContainer');
const manualEntryContainer = document.getElementById('manualEntryContainer');
const toggleCameraBtn = document.getElementById('toggleCameraBtn');
const toggleManualBtn = document.getElementById('toggleManualBtn');
const toggleScannerBtn = document.getElementById('toggleScannerBtn');
const switchCameraBtn = document.getElementById('switchCameraBtn');
const loadingIndicator = document.getElementById('loadingIndicator');
const scanForm = document.getElementById('scanForm');
const cardIdInput = document.getElementById('card_id');

loadingIndicator.style.display = 'flex';

function initScanner(cameraId = null) {
    if (typeof Html5Qrcode === 'undefined') {
        console.error('Html5Qrcode library not loaded');
        loadingIndicator.style.display = 'none';
        showAlert('❌ Scanner library not loaded. Please refresh the page.', 'danger');
        toggleToManual();
        return;
    }
    
    if (html5QrCode) {
        try {
            html5QrCode.stop();
            html5QrCode.clear();
        } catch (e) {
            console.log('Clearing previous scanner');
        }
        html5QrCode = null;
    }
    
    loadingIndicator.style.display = 'flex';
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        loadingIndicator.style.display = 'none';
        showAlert('❌ Your browser does not support camera access.', 'danger');
        toggleToManual();
        return;
    }
    
    try {
        html5QrCode = new Html5Qrcode("qrVideo");
        const config = { fps: 10, qrbox: { width: 300, height: 300 } };
        
        let cameraConfig = cameraId 
            ? { deviceId: { exact: cameraId } }
            : { facingMode: "environment" };
        
        html5QrCode.start(
            cameraConfig,
            config,
            (decodedText, decodedResult) => {
                onScanSuccess(decodedText);
            },
            (errorMessage) => {
                console.debug('Scan attempt:', errorMessage);
            }
        ).then(() => {
            console.log('Camera started successfully');
            scanningActive = true;
            currentCameraId = cameraId;
            loadingIndicator.style.display = 'none';
            showAlert('✓ Camera ready! Point at QR code to scan.', 'info');
        }).catch((error) => {
            loadingIndicator.style.display = 'none';
            console.error('Camera initialization error:', error);
            showAlert('❌ Unable to access camera. Check your camera and browser settings.', 'warning');
            toggleToManual();
        });
    } catch (error) {
        loadingIndicator.style.display = 'none';
        console.error('Error creating scanner:', error);
        showAlert('❌ Failed to initialize scanner. Try manual entry instead.', 'danger');
        toggleToManual();
    }
}

async function getCameras() {
    try {
        const devices = await Html5Qrcode.getCameras();
        cameras = devices;
        return devices;
    } catch (error) {
        console.error('Error getting cameras:', error);
        return [];
    }
}

function switchCamera() {
    if (cameras.length === 0) {
        getCameras().then(() => {
            if (cameras.length > 1) {
                switchCamera();
            } else {
                showAlert('❌ Only one camera available.', 'warning');
            }
        });
        return;
    }
    
    if (cameras.length === 1) {
        showAlert('❌ Only one camera available.', 'warning');
        return;
    }
    
    let currentIndex = cameras.findIndex(cam => cam.id === currentCameraId);
    if (currentIndex === -1) currentIndex = 0;
    
    const nextIndex = (currentIndex + 1) % cameras.length;
    const nextCamera = cameras[nextIndex];
    
    showAlert('🔄 Switching camera...', 'info');
    initScanner(nextCamera.id);
}

function stopScanner() {
    if (html5QrCode) {
        try {
            html5QrCode.stop();
            html5QrCode.clear();
            html5QrCode = null;
        } catch (error) {
            console.log('Error stopping scanner:', error);
            html5QrCode = null;
        }
    }
    scanningActive = false;
    loadingIndicator.style.display = 'none';
}

function toggleToManual() {
    scannerContainer.classList.add('d-none');
    manualEntryContainer.classList.remove('d-none');
    cardIdInput.focus();
}

function onScanSuccess(decodedText) {
    const cardId = decodedText.trim();
    
    if (cardId === '') return;
    
    if (lastScanCardId === cardId && lastScanTime && (Date.now() - lastScanTime) < 5000) {
        console.log('Duplicate scan detected within 5 seconds');
        return;
    }
    
    if (html5QrCode && scanningActive) {
        try {
            html5QrCode.pause();
        } catch (e) {
            console.log('Could not pause scanner');
        }
    }
    
    lastScanCardId = cardId;
    lastScanTime = Date.now();
    
    const formData = new FormData();
    formData.append('card_id', cardId);
    formData.append('ajax', '1');

    showAlert('🔄 Verifying card...', 'info');

    fetch('athlete_scan.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('✓ Identity verified successfully! Redirecting to dashboard...', 'success');
            
            // Immediate redirect to dashboard
            window.location.href = 'athlete_dashboard.php';
        } else {
            showAlert('❌ ' + data.message, 'danger');
            setTimeout(() => {
                if (html5QrCode && scanningActive) {
                    try {
                        html5QrCode.resume();
                    } catch (e) {
                        console.log('Could not resume scanner');
                    }
                }
                lastScanCardId = null;
                lastScanTime = null;
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('❌ Connection error. Please try again.', 'danger');
        if (html5QrCode && scanningActive) {
            try {
                html5QrCode.resume();
            } catch (e) {
                console.log('Could not resume scanner');
            }
        }
        lastScanCardId = null;
        lastScanTime = null;
    });
}

toggleCameraBtn.addEventListener('click', () => {
    if (cameraActive) {
        stopScanner();
        cameraActive = false;
        toggleCameraBtn.innerHTML = '<i class="bi bi-camera-video"></i> Start Camera';
        showAlert('Camera stopped', 'info');
    } else {
        cameraActive = true;
        initScanner();
        toggleCameraBtn.innerHTML = '<i class="bi bi-camera-video-off"></i> Stop Camera';
    }
});

switchCameraBtn.addEventListener('click', () => {
    switchCamera();
});

toggleManualBtn.addEventListener('click', () => {
    stopScanner();
    cameraActive = false;
    scannerContainer.classList.add('d-none');
    manualEntryContainer.classList.remove('d-none');
    cardIdInput.focus();
});

toggleScannerBtn.addEventListener('click', () => {
    manualEntryContainer.classList.add('d-none');
    scannerContainer.classList.remove('d-none');
    cameraActive = true;
    initScanner();
});

scanForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const cardId = cardIdInput.value.trim();
    if (cardId) {
        cardIdInput.value = '';
        onScanSuccess(cardId);
    }
});

function showAlert(message, type) {
    const oldAlerts = document.querySelectorAll('.alert-floating');
    oldAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-floating`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    alertDiv.innerHTML = `
        <div style="text-align: center; padding: 5px 0;">
            ${message}
        </div>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    if (type === 'info') {
        setTimeout(() => {
            if (alertDiv && alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 2500);
    } else if (type === 'success') {
        setTimeout(() => {
            if (alertDiv && alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    function checkLibraryLoaded() {
        if (typeof Html5Qrcode !== 'undefined') {
            getCameras();
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(() => initScanner())
                    .catch(() => {
                        showAlert('❌ Camera permission denied. Please allow camera access.', 'warning');
                        toggleToManual();
                    });
            } else {
                toggleToManual();
            }
        } else {
            setTimeout(checkLibraryLoaded, 100);
        }
    }
    checkLibraryLoaded();
});
</script>

<?php require_once __DIR__ . '/athlete_modern_footer.php'; ?>