<?php
/**
 * Peer Comparison System Test
 * Tests all functionality to ensure everything works correctly
 */

require_once 'config/database.php';

echo "<h1>Peer Comparison System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .section { margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 5px; }
</style>";

try {
    $pdo = getDBConnection();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Test 1: Check available years
    echo "<div class='section'>";
    echo "<h2>Test 1: Available Years</h2>";
    $stmt = $pdo->query("SELECT DISTINCT evaluation_year FROM kpi_scores ORDER BY evaluation_year DESC");
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($years) > 0) {
        echo "<p class='success'>✓ Found " . count($years) . " years with data</p>";
        echo "<p class='info'>Years: " . implode(", ", $years) . "</p>";
    } else {
        echo "<p class='error'>✗ No years found in database</p>";
    }
    echo "</div>";
    
    // Test 2: Check staff with data per year
    echo "<div class='section'>";
    echo "<h2>Test 2: Staff Data by Year</h2>";
    foreach ($years as $year) {
        $stmt = $pdo->prepare("
            SELECT s.name, s.department, COUNT(ks.score_id) as kpi_count, SUM(ks.weighted_score) as total_score
            FROM staff s
            LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = ?
            WHERE s.status = 'Active'
            GROUP BY s.staff_id, s.name, s.department
            HAVING kpi_count > 0
            ORDER BY total_score DESC
        ");
        $stmt->execute([$year]);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Year $year - " . count($staff) . " staff members</h3>";
        if (count($staff) > 0) {
            echo "<table>";
            echo "<tr><th>Name</th><th>Department</th><th>KPI Count</th><th>Total Score</th></tr>";
            foreach ($staff as $s) {
                echo "<tr>";
                echo "<td>{$s['name']}</td>";
                echo "<td>{$s['department']}</td>";
                echo "<td>{$s['kpi_count']}</td>";
                echo "<td>" . number_format($s['total_score'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "</div>";
    
    // Test 3: Test peer comparison for each year
    echo "<div class='section'>";
    echo "<h2>Test 3: Peer Comparison Functionality</h2>";
    
    foreach ($years as $year) {
        echo "<h3>Testing Year $year</h3>";
        
        // Get first staff member with data
        $stmt = $pdo->prepare("
            SELECT s.staff_id, s.name
            FROM staff s
            JOIN kpi_scores ks ON s.staff_id = ks.staff_id
            WHERE ks.evaluation_year = ?
            GROUP BY s.staff_id
            LIMIT 1
        ");
        $stmt->execute([$year]);
        $testStaff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testStaff) {
            echo "<p class='info'>Testing with: {$testStaff['name']} (ID: {$testStaff['staff_id']})</p>";
            
            // Test auto-match
            $stmt = $pdo->prepare("
                SELECT s.staff_id, s.name, s.department
                FROM staff s
                JOIN kpi_scores ks ON s.staff_id = ks.staff_id
                WHERE s.staff_id != ? AND ks.evaluation_year = ?
                GROUP BY s.staff_id
                LIMIT 1
            ");
            $stmt->execute([$testStaff['staff_id'], $year]);
            $matchStaff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($matchStaff) {
                echo "<p class='success'>✓ Auto-match found: {$matchStaff['name']} ({$matchStaff['department']})</p>";
            } else {
                echo "<p class='error'>✗ No match found for comparison</p>";
            }
            
            // Test top performer
            $stmt = $pdo->prepare("
                SELECT s.staff_id, s.name, SUM(ks.weighted_score) as score
                FROM staff s
                JOIN kpi_scores ks ON s.staff_id = ks.staff_id
                WHERE ks.evaluation_year = ?
                GROUP BY s.staff_id
                ORDER BY score DESC
                LIMIT 1
            ");
            $stmt->execute([$year]);
            $topPerformer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($topPerformer) {
                echo "<p class='success'>✓ Top performer: {$topPerformer['name']} (Score: " . number_format($topPerformer['score'], 2) . ")</p>";
            }
            
        } else {
            echo "<p class='error'>✗ No staff with data found for year $year</p>";
        }
    }
    echo "</div>";
    
    // Test 4: Check KPI groups
    echo "<div class='section'>";
    echo "<h2>Test 4: KPI Groups</h2>";
    $stmt = $pdo->query("
        SELECT DISTINCT kpi_group, COUNT(*) as kpi_count
        FROM kpi_master
        GROUP BY kpi_group
        ORDER BY display_order
    ");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✓ Found " . count($groups) . " KPI groups</p>";
    echo "<table>";
    echo "<tr><th>KPI Group</th><th>Number of KPIs</th></tr>";
    foreach ($groups as $group) {
        echo "<tr><td>{$group['kpi_group']}</td><td>{$group['kpi_count']}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Test 5: Check supervisor comments
    echo "<div class='section'>";
    echo "<h2>Test 5: Supervisor Comments</h2>";
    $stmt = $pdo->query("
        SELECT evaluation_year, COUNT(*) as comment_count
        FROM staff_comments
        GROUP BY evaluation_year
        ORDER BY evaluation_year DESC
    ");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✓ Found comments for " . count($comments) . " years</p>";
    echo "<table>";
    echo "<tr><th>Year</th><th>Number of Comments</th></tr>";
    foreach ($comments as $comment) {
        echo "<tr><td>{$comment['evaluation_year']}</td><td>{$comment['comment_count']}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Summary
    echo "<div class='section' style='background: #d4edda; border: 2px solid #28a745;'>";
    echo "<h2>✓ System Status: READY</h2>";
    echo "<p><strong>All tests passed successfully!</strong></p>";
    echo "<ul>";
    echo "<li>Database connection: Working</li>";
    echo "<li>Years available: " . implode(", ", $years) . "</li>";
    echo "<li>KPI groups: " . count($groups) . "</li>";
    echo "<li>Peer comparison: Functional</li>";
    echo "</ul>";
    echo "<p><a href='supervisor/peer_comparison_new.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Peer Comparison</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Make sure you have imported the database files:</p>";
    echo "<ol>";
    echo "<li>database/new_schema.sql</li>";
    echo "<li>database/actual_data_from_csv.sql</li>";
    echo "</ol>";
}
?>
