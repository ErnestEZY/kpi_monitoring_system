<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Checking Supervisors Table</h2>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'supervisors'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>❌ Table 'supervisors' does not exist!</p>";
        echo "<p>Please import database/schema.sql first.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Table 'supervisors' exists</p>";
    
    // Check supervisor records
    $stmt = $pdo->query("SELECT supervisor_id, username, full_name, email, status, created_at FROM supervisors");
    $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($supervisors) == 0) {
        echo "<p style='color: red;'>❌ No supervisors found in database!</p>";
        echo "<p>Please import database/sample_data.sql</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Found " . count($supervisors) . " supervisor(s)</p>";
    
    echo "<h3>Supervisor Accounts:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($supervisors as $sup) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($sup['supervisor_id']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['username']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['email']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['status']) . "</td>";
        echo "<td>" . htmlspecialchars($sup['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification
    echo "<h3>Password Hash Test:</h3>";
    $test_password = 'password123';
    
    $stmt = $pdo->prepare("SELECT username, password_hash FROM supervisors WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>Testing password 'password123' for user 'admin'...</p>";
        
        if (password_verify($test_password, $admin['password_hash'])) {
            echo "<p style='color: green;'>✓ Password verification SUCCESSFUL!</p>";
            echo "<p>You should be able to login with: admin / password123</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification FAILED!</p>";
            echo "<p>The password hash in the database doesn't match 'password123'</p>";
            echo "<p>Hash in database: " . htmlspecialchars(substr($admin['password_hash'], 0, 50)) . "...</p>";
            
            // Generate correct hash
            $correct_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "<h4>Fix: Run this SQL to update the password:</h4>";
            echo "<pre>UPDATE supervisors SET password_hash = '" . $correct_hash . "' WHERE username = 'admin';</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Admin user not found!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
