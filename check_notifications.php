<?php
require_once __DIR__ . '/config/database.php';

// Check current notification titles
$stmt = $pdo->query("SELECT id, title FROM notifications LIMIT 10");
$notifications = $stmt->fetchAll();

echo "Current notification titles:\n";
foreach ($notifications as $note) {
    echo "ID: {$note['id']} - Title: {$note['title']}\n";
}
