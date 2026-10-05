<?php
require_once __DIR__ . '/config/database.php';

// Update all notification titles to remove "IN - " and "UP - " prefixes
$pdo->exec("UPDATE notifications SET title = REPLACE(title, 'IN - ', '') WHERE title LIKE 'IN - %'");
$pdo->exec("UPDATE notifications SET title = REPLACE(title, 'UP - ', '') WHERE title LIKE 'UP - %'");

echo "Notification titles updated successfully!";
