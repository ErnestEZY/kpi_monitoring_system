<?php
require_once 'config/database.php';

echo "<h2>Converting to Plain Text Passwords</h2>";
echo "<p style='color: orange;'>⚠️ WARNING: Plain text passwords are NOT secure. Use only for development/assignment.</p>";
echo "<hr>";

try {
    $pdo = getDBConnection();
    
    // Check if password column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM supervisors LIKE 'password'");
    $passwordExists = $stmt->rowCount() > 0;
    
    if (!$passwordExists) {
        echo "<p>Step 1: Adding 'password' column...</p>";
        $pdo->exec("ALTER TABLE supervisors ADD COLUMN password VARCHAR(255) NULL AFTER username");
        echo "<p style='color: green;'>✓ Column added</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'password' column already exists</p>";
    }
    
    echo "<p>Step 2: Setting plain text passwords...</p>";
    $stmt = $pdo->prepare("UPDATE supervisors SET password = 'password123' WHERE username = ?");
    
    $usernames = ['admin', 'supervisor1', 'supervisor2'];
    foreach ($usernames as $username) {
        $stmt->execute([$username]);
        echo "<p style='color: green;'>✓ Updated password for: <strong>$username</strong></p>";
    }
    
    echo "<p>Step 3: Making password column NOT NULL...</p>";
    $pdo->exec("ALTER TABLE supervisors MODIFY COLUMN password VARCHAR(255) NOT NULL");
    echo "<p style='color: green;'>✓ Column updated</p>";
    
    // Check if password_hash column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM supervisors LIKE 'password_hash'");
    $hashExists = $stmt->rowCount() > 0;
    
    if ($hashExists) {
        echo "<p>Step 4: Removing old 'password_hash' column...</p>";
        $pdo->exec("ALTER TABLE supervisors DROP COLUMN password_hash");
        echo "<p style='color: green;'>✓ Old column removed</p>";
    } else {
        echo "<p style='color: blue;'>ℹ 'password_hash' column already removed</p>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ Conversion Complete!</h3>";
    
    // Display all supervisors
    echo "<h3>Supervisor Accounts:</h3>";
    $stmt = $pdo->query("SELECT supervisor_id, username, password, full_name, email, status FROM supervisors");
    $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password</th><th>Full Name</th><th>Email</th><th>Status</th></tr>";
    
    foreach ($supervisors as $sup) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($sup['supervisor_id']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($sup['username']) . "</strong></td>";
        echo "<td><strong>" . htmlspecialchars($sup['password']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($sup['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['email']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>You can now login with:</h3>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> password123</p>";
    echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Login Page</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
