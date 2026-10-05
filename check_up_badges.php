<?php
require_once __DIR__ . '/config/database.php';

// Check for any notifications with UP or IN in the title
$stmt = $pdo->query("SELECT id, title FROM notifications WHERE title LIKE '%UP%' OR title LIKE '%IN%'");
$notifications = $stmt->fetchAll();

echo "Notifications with UP or IN in title:\n";
foreach ($notifications as $note) {
    echo "ID: {$note['id']} - Title: {$note['title']}\n";
}

if (empty($notifications)) {
    echo "No notifications found with UP or IN in title.\n";
}
