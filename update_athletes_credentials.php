<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Update Existing Athletes with Login Credentials</h2>";
echo "<p>Generating usernames and passwords for athletes without credentials...</p>";

try {
    // Get all athletes without username/password
    $stmt = $pdo->query("SELECT * FROM athletes WHERE username IS NULL OR username = ''");
    $athletes = $stmt->fetchAll();
    
    if (empty($athletes)) {
        echo "<p style='color: blue;'>✓ All athletes already have login credentials!</p>";
        
        // Show all existing credentials
        echo "<hr>";
        echo "<h3>Existing Athlete Credentials:</h3>";
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Card ID</th><th>Name</th><th>Username</th><th>Password Status</th></tr></thead>";
        echo "<tbody>";
        
        $allStmt = $pdo->query("SELECT card_id, first_name, last_name, username, password FROM athletes WHERE username IS NOT NULL AND username != ''");
        $allAthletes = $allStmt->fetchAll();
        
        foreach ($allAthletes as $athlete) {
            echo "<tr>";
            echo "<td>" . h($athlete['card_id']) . "</td>";
            echo "<td>" . h($athlete['first_name'] . ' ' . $athlete['last_name']) . "</td>";
            echo "<td>" . h($athlete['username']) . "</td>";
            echo "<td>" . ($athlete['password'] ? '<span class="text-success">Set</span>' : '<span class="text-danger">Not Set</span>') . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "<p><a href='index.php'>Go to Admin Dashboard</a> | <a href='athlete_login.php'>Go to Athlete Login</a></p>";
        exit;
    }
    
    echo "<p>Found " . count($athletes) . " athletes without login credentials.</p>";
    echo "<hr>";
    
    $updatedCount = 0;
    $credentials = [];
    
    foreach ($athletes as $athlete) {
        // Generate username from name (first name + last initial)
        $username = strtolower($athlete['first_name']) . strtolower(substr($athlete['last_name'], 0, 1));
        $username = preg_replace('/[^a-z0-9]/', '', $username); // Remove special characters
        
        // Make username unique by adding number if needed
        $uniqueUsername = $username;
        $counter = 1;
        while (true) {
            $check = $pdo->prepare("SELECT id FROM athletes WHERE username = :username AND id != :id");
            $check->execute(['username' => $uniqueUsername, 'id' => $athlete['id']]);
            if (!$check->fetch()) {
                break;
            }
            $uniqueUsername = $username . $counter;
            $counter++;
        }
        
        // Generate default password (athlete + card_id last 4 digits)
        $defaultPassword = 'athlete' . substr($athlete['card_id'], -4);
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        // Update athlete
        $update = $pdo->prepare("
            UPDATE athletes 
            SET username = :username, password = :password 
            WHERE id = :id
        ");
        $update->execute([
            'username' => $uniqueUsername,
            'password' => $hashedPassword,
            'id' => $athlete['id']
        ]);
        
        $credentials[] = [
            'name' => $athlete['first_name'] . ' ' . $athlete['last_name'],
            'card_id' => $athlete['card_id'],
            'username' => $uniqueUsername,
            'password' => $defaultPassword
        ];
        
        $updatedCount++;
    }
    
    echo "<p style='color: green;'>✓ Successfully updated $updatedCount athletes with login credentials!</p>";
    echo "<hr>";
    echo "<h3>Generated Athlete Credentials:</h3>";
    echo "<div class='alert alert-info'>";
    echo "<p><strong>Important:</strong> Save these credentials securely. Athletes should change their passwords after first login.</p>";
    echo "</div>";
    
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>Card ID</th><th>Name</th><th>Username</th><th>Password</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($credentials as $cred) {
        echo "<tr>";
        echo "<td>" . h($cred['card_id']) . "</td>";
        echo "<td>" . h($cred['name']) . "</td>";
        echo "<td><strong>" . h($cred['username']) . "</strong></td>";
        echo "<td><strong>" . h($cred['password']) . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    echo "<hr>";
    echo "<p><a href='athlete_login.php' class='btn btn-primary'>Test Athlete Login</a></p>";
    echo "<p><a href='index.php' class='btn btn-secondary'>Go to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . h($e->getMessage()) . "</p>";
}
?>