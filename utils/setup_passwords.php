<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // Generate proper password hash for 'password123'
    $password = 'password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<h2>Setting Up Supervisor Passwords</h2>";
    
    // Update all supervisors with the correct hash
    $supervisors = ['admin', 'supervisor1', 'supervisor2'];
    
    foreach ($supervisors as $username) {
        $stmt = $pdo->prepare("UPDATE supervisors SET password_hash = ? WHERE username = ?");
        $stmt->execute([$hash, $username]);
        
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Updated password for: <strong>$username</strong></p>";
        } else {
            echo "<p style='color: orange;'>⚠ User not found: $username</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Verification Test:</h3>";
    
    // Verify the password works
    $stmt = $pdo->prepare("SELECT username, password_hash FROM supervisors WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        echo "<p style='color: green; font-size: 18px;'>✓ <strong>SUCCESS!</strong> Password verification works!</p>";
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>You can now login with:</h3>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> password123</p>";
        echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Verification still failed. Please check your PHP installation.</p>";
    }
    
    echo "<hr>";
    echo "<h3>All Supervisor Accounts:</h3>";
    $stmt = $pdo->query("SELECT username, full_name, email, status FROM supervisors ORDER BY username");
    $all_supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Username</th><th>Password</th><th>Full Name</th><th>Email</th><th>Status</th></tr>";
    
    foreach ($all_supervisors as $sup) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($sup['username']) . "</strong></td>";
        echo "<td>password123</td>";
        echo "<td>" . htmlspecialchars($sup['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['email']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
