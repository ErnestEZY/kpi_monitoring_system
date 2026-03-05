<?php
require_once 'config/database.php';

$pdo = getDBConnection();

echo "🔍 Checking KPI Master table...\n";

// Check if kpi_master has data
$stmt = $pdo->query("SELECT COUNT(*) as count FROM kpi_master");
$master_count = $stmt->fetch()['count'];
echo "📋 KPI Master records: $master_count\n";

if ($master_count === 0) {
    echo "❌ PROBLEM: KPI Master table is empty!\n";
    echo "💡 SOLUTION: KPI Master table needs to be populated with KPI codes\n";
    
    // Show what kpi_codes exist in scores
    $stmt = $pdo->query("SELECT DISTINCT kpi_code FROM kpi_scores ORDER BY kpi_code");
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📝 KPI codes found in scores: " . implode(', ', $codes) . "\n";
    
    // Check if any kpi_master records exist
    $stmt = $pdo->query("SELECT * FROM kpi_master LIMIT 5");
    $masters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($masters)) {
        echo "🚨 CRITICAL: No KPI Master records found!\n";
        echo "🔧 The JOIN in staff performance query fails because kpi_master is empty\n";
    }
} else {
    echo "✅ KPI Master table has data\n";
    
    // Show sample data
    $stmt = $pdo->query("SELECT * FROM kpi_master LIMIT 5");
    $masters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "📊 Sample KPI Master records:\n";
    foreach ($masters as $master) {
        echo "  - {$master['kpi_code']}: {$master['section']} (Section {$master['section_number']})\n";
    }
}

// Test the actual query
echo "\n🧪 Testing staff performance query...\n";
$year = 2025;
$stmt = $pdo->prepare("
    SELECT 
        s.staff_id,
        s.staff_code,
        s.name,
        s.department,
        SUM(CASE WHEN km.section_number = 1 THEN ks.weighted_score ELSE 0 END) as core_competencies_score,
        SUM(CASE WHEN km.section_number = 2 THEN ks.weighted_score ELSE 0 END) as kpi_achievement_score,
        SUM(ks.weighted_score) as final_score
    FROM staff s
    LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = ?
    LEFT JOIN kpi_master km ON ks.kpi_code = km.kpi_code
    WHERE s.status = 'Active'
    GROUP BY s.staff_id, s.staff_code, s.name, s.department
    HAVING final_score IS NOT NULL
    ORDER BY final_score DESC
");

$stmt->execute([$year]);
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📈 Query returned " . count($staff) . " staff records for year $year\n";

if (count($staff) > 0) {
    echo "✅ Sample staff data:\n";
    foreach ($staff as $s) {
        echo "  - {$s['staff_code']}: {$s['name']} - Score: {$s['final_score']}\n";
    }
}
?>
