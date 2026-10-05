<?php
require_once __DIR__ . '/config/database.php';

// Check for any recent changes to notification titles
$stmt = $pdo->query("SELECT id, title, is_read, created_at FROM notifications ORDER BY id DESC LIMIT 10");
$notifications = $stmt->fetchAll();

echo "Recent notifications:\n";
foreach ($notifications as $note) {
    echo "ID: {$note['id']} - Title: {$note['title']} - Read: {$note['is_read']} - Created: {$note['created_at']}\n";
}
