<?php
require_once 'config/database.php';

$pdo = getDBConnection();

echo "🔍 Testing Staff Performance Query\n\n";

// Check if kpi_master has the right codes
$stmt = $pdo->query("SELECT kpi_code, section_number FROM kpi_master ORDER BY kpi_code");
$master_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 KPI Master codes:\n";
foreach ($master_codes as $code) {
    echo "  - {$code['kpi_code']} (Section {$code['section_number']})\n";
}

// Check what kpi_codes exist in scores for 2025
$stmt = $pdo->query("SELECT DISTINCT kpi_code FROM kpi_scores WHERE evaluation_year = 2025 ORDER BY kpi_code");
$score_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\n📊 KPI codes in scores (2025):\n";
foreach ($score_codes as $code) {
    echo "  - $code\n";
}

// Test the actual query with debug
echo "\n🧪 Testing staff performance query with debug:\n";

$year = 2025;
$stmt = $pdo->prepare("
    SELECT 
        s.staff_id,
        s.staff_code,
        s.name,
        s.department,
        ks.kpi_code,
        ks.score,
        ks.weighted_score,
        km.section_number,
        km.kpi_group
    FROM staff s
    LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = ?
    LEFT JOIN kpi_master km ON ks.kpi_code = km.kpi_code
    WHERE s.status = 'Active'
    ORDER BY s.staff_code, km.display_order
");

$stmt->execute([$year]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📈 Raw query results: " . count($results) . " records\n";

// Group by staff
$staff_data = [];
foreach ($results as $row) {
    $staff_id = $row['staff_id'];
    if (!isset($staff_data[$staff_id])) {
        $staff_data[$staff_id] = [
            'staff_code' => $row['staff_code'],
            'name' => $row['name'],
            'department' => $row['department'],
            'core_competencies_score' => 0,
            'kpi_achievement_score' => 0,
            'final_score' => 0,
            'kpi_count' => 0
        ];
    }
    
    if ($row['section_number'] == 1) {
        $staff_data[$staff_id]['core_competencies_score'] += $row['weighted_score'];
    } elseif ($row['section_number'] == 2) {
        $staff_data[$staff_id]['kpi_achievement_score'] += $row['weighted_score'];
    }
    
    $staff_data[$staff_id]['final_score'] += $row['weighted_score'];
    $staff_data[$staff_id]['kpi_count']++;
}

echo "\n👥 Staff summary:\n";
foreach ($staff_data as $staff) {
    if ($staff['final_score'] > 0) {
        echo "  - {$staff['staff_code']}: {$staff['name']} - Score: {$staff['final_score']} ({$staff['kpi_count']} KPIs)\n";
    }
}

echo "\n✅ Test completed!\n";
?>
