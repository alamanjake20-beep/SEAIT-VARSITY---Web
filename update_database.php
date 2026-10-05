<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Database Update for Athlete Authentication</h2>";
echo "<p>Adding username and password columns to athletes table...</p>";

try {
    // Add username column
    $pdo->exec("ALTER TABLE athletes ADD COLUMN username VARCHAR(50) NULL AFTER card_id");
    echo "<p style='color: green;'>✓ Added username column to athletes table</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<p style='color: blue;'>✓ Username column already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding username column: " . h($e->getMessage()) . "</p>";
    }
}

try {
    // Add password column
    $pdo->exec("ALTER TABLE athletes ADD COLUMN password VARCHAR(255) NULL AFTER username");
    echo "<p style='color: green;'>✓ Added password column to athletes table</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<p style='color: blue;'>✓ Password column already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding password column: " . h($e->getMessage()) . "</p>";
    }
}

try {
    // Add unique index on username
    $pdo->exec("ALTER TABLE athletes ADD UNIQUE INDEX idx_athletes_username (username)");
    echo "<p style='color: green;'>✓ Added unique index on username</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "<p style='color: blue;'>✓ Username index already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding username index: " . h($e->getMessage()) . "</p>";
    }
}

echo "<hr>";
echo "<p style='color: green; font-weight: bold;'>Database update completed successfully!</p>";
echo "<p>Next step: <a href='update_athletes_credentials.php'>Update existing athletes with credentials</a></p>";
echo "<p>Or go to: <a href='index.php'>Admin Dashboard</a> | <a href='athlete_login.php'>Athlete Login</a></p>";
?>