<?php
require_once 'config/database.php';

$pdo = getDBConnection();

echo "🔍 Testing API Endpoints\n\n";

// Test 1: Staff List API
echo "1. Testing get_staff_list API:\n";
try {
    $stmt = $pdo->query("
        SELECT staff_id, staff_code, name, department 
        FROM staff 
        WHERE status = 'Active' 
        ORDER BY name
    ");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Found " . count($staff) . " active staff\n";
    if (count($staff) > 0) {
        echo "📋 Sample: " . $staff[0]['name'] . " (" . $staff[0]['staff_code'] . ")\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Training Recommendations API
echo "\n2. Testing training recommendations query:\n";
try {
    $year = 2025;
    $stmt = $pdo->prepare("
        SELECT 
            s.staff_id,
            s.staff_code,
            s.name,
            km.kpi_group,
            ks.score,
            COUNT(*) as gap_count
        FROM staff s
        JOIN kpi_scores ks ON s.staff_id = ks.staff_id
        JOIN kpi_master km ON ks.kpi_code = km.kpi_code
        WHERE s.status = 'Active' AND ks.evaluation_year = ? AND ks.score < 4
        GROUP BY s.staff_id, km.kpi_group
        ORDER BY s.staff_id, ks.score ASC
        LIMIT 5
    ");
    $stmt->execute([$year]);
    $weaknesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Found " . count($weaknesses) . " skill gaps\n";
    foreach ($weaknesses as $w) {
        echo "📉 {$w['name']}: {$w['kpi_group']} (Score: {$w['score']})\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Peer Comparison API
echo "\n3. Testing peer comparison query:\n";
try {
    $year = 2025;
    $staff_id = 1;
    
    $stmt = $pdo->prepare("
        SELECT 
            s.staff_id,
            s.staff_code,
            s.name,
            s.department,
            SUM(ks.weighted_score) as total_score
        FROM staff s
        JOIN kpi_scores ks ON s.staff_id = ks.staff_id
        WHERE s.staff_id = ? AND ks.evaluation_year = ?
        GROUP BY s.staff_id
    ");
    $stmt->execute([$staff_id, $year]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff) {
        echo "✅ Staff data found: {$staff['name']} - Score: {$staff['total_score']}\n";
    } else {
        echo "❌ No staff data found for ID $staff_id in year $year\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🔧 If all tests pass, the issue is in JavaScript/API calls\n";
echo "🔧 If tests fail, the issue is in database/KPI codes\n";
?>
