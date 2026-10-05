<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== Notification System Test ===\n\n";

// Test 1: Create a test notification for admin
echo "Test 1: Creating test notification for admin...\n";
create_notification(
    $pdo,
    'admin',
    0,
    'Test Admin Notification',
    'This is a test notification for admin to verify the system is working.',
    'test',
    0
);
echo "✓ Admin notification created\n\n";

// Test 2: Create a test notification for athlete (using athlete ID 1 if exists)
echo "Test 2: Creating test notification for athlete...\n";
$athleteStmt = $pdo->query("SELECT id FROM athletes LIMIT 1");
$athlete = $athleteStmt->fetch();
if ($athlete) {
    create_notification(
        $pdo,
        'athlete',
        $athlete['id'],
        'Test Athlete Notification',
        'This is a test notification for athlete to verify the system is working.',
        'test',
        0
    );
    echo "✓ Athlete notification created for athlete ID: " . $athlete['id'] . "\n\n";
} else {
    echo "⚠ No athletes found in database, skipping athlete notification test\n\n";
}

// Test 3: Check admin notifications
echo "Test 3: Retrieving admin notifications...\n";
$adminNotifications = get_notifications($pdo, 'admin', 0, 5);
echo "Found " . count($adminNotifications) . " admin notifications:\n";
foreach ($adminNotifications as $note) {
    echo "  - " . $note['title'] . " (Read: " . ($note['is_read'] ? 'Yes' : 'No') . ")\n";
}
echo "\n";

// Test 4: Check unread count for admin
echo "Test 4: Checking unread count for admin...\n";
$adminUnread = get_unread_notification_count($pdo, 'admin', 0);
echo "Admin unread count: " . $adminUnread . "\n\n";

// Test 5: Check athlete notifications if athlete exists
if ($athlete) {
    echo "Test 5: Retrieving athlete notifications...\n";
    $athleteNotifications = get_notifications($pdo, 'athlete', $athlete['id'], 5);
    echo "Found " . count($athleteNotifications) . " athlete notifications:\n";
    foreach ($athleteNotifications as $note) {
        echo "  - " . $note['title'] . " (Read: " . ($note['is_read'] ? 'Yes' : 'No') . ")\n";
    }
    echo "\n";

    echo "Test 6: Checking unread count for athlete...\n";
    $athleteUnread = get_unread_notification_count($pdo, 'athlete', $athlete['id']);
    echo "Athlete unread count: " . $athleteUnread . "\n\n";
}

echo "=== Notification System Test Complete ===\n";
echo "If you see the test notifications above, the system is working correctly.\n";
echo "You can now check the admin dashboard (index.php) and athlete dashboard to see the notifications.\n";
