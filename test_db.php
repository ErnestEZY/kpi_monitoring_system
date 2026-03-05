<?php
/**
 * Database Test Script
 * Check if database has data and tables exist
 */

require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    echo "✅ Database connection: SUCCESS\n\n";
    
    // Check if tables exist
    $tables = ['staff', 'kpi_master', 'kpi_scores', 'staff_comments', 'supervisors'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "📋 Table '$table': " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    
    echo "\n📊 Data Counts:\n";
    
    // Check staff table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff");
    $staff_count = $stmt->fetch()['count'];
    echo "👥 Staff members: $staff_count\n";
    
    // Check kpi_scores table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM kpi_scores");
    $scores_count = $stmt->fetch()['count'];
    echo "📈 KPI scores: $scores_count\n";
    
    // Check years available
    $stmt = $pdo->query("SELECT DISTINCT evaluation_year FROM kpi_scores ORDER BY evaluation_year");
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📅 Years available: " . implode(', ', $years) . "\n";
    
    // Check sample data
    if ($staff_count === 0) {
        echo "\n❌ PROBLEM: No staff data found!\n";
        echo "💡 SOLUTION: Import database/sample_data.sql\n";
    }
    
    if ($scores_count === 0) {
        echo "\n❌ PROBLEM: No KPI scores found!\n";
        echo "💡 SOLUTION: Import database/updated_sample_data.sql\n";
    }
    
    if (empty($years)) {
        echo "\n❌ PROBLEM: No evaluation years found!\n";
        echo "💡 SOLUTION: Run database/generate_multiyear_data.php\n";
    }
    
    echo "\n✅ Database test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Database connection FAILED: " . $e->getMessage() . "\n";
    echo "💡 SOLUTION: Check XAMPP MySQL service and database credentials\n";
}
?>
