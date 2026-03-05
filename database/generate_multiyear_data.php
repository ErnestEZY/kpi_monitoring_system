<?php
/**
 * Generate Multi-Year KPI Data (2023-2025)
 * This script generates progressive performance data showing improvements over time
 * Run this after importing updated_sample_data.sql
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // Define KPI codes
    $kpiCodes = ['CC1', 'CC2', 'CC3', 'KPI1', 'KPI2', 'KPI3', 'KPI4', 'KPI5', 'KPI6', 
                 'KPI7', 'KPI8', 'KPI9', 'KPI10', 'KPI11', 'KPI12', 'KPI13', 'KPI14', 
                 'KPI15', 'KPI16', 'KPI17', 'KPI18'];
    
    // Get KPI weights
    $stmt = $pdo->query("SELECT kpi_code, weight_percentage FROM kpi_master");
    $weights = [];
    while ($row = $stmt->fetch()) {
        $weights[$row['kpi_code']] = (float)$row['weight_percentage'];
    }
    
    // Generate data for years 2023-2025
    for ($year = 2023; $year <= 2025; $year++) {
        echo "Generating data for year $year...\n";
        
        // For each staff member (1-13)
        for ($staffId = 1; $staffId <= 13; $staffId++) {
            // Get 2022 baseline scores
            $stmt = $pdo->prepare("SELECT kpi_code, score FROM kpi_scores 
                                   WHERE staff_id = ? AND evaluation_year = 2022");
            $stmt->execute([$staffId]);
            $baselineScores = [];
            while ($row = $stmt->fetch()) {
                $baselineScores[$row['kpi_code']] = (float)$row['score'];
            }
            
            // Calculate improvement factor based on years passed
            $yearsPassed = $year - 2022;
            
            // Insert scores for each KPI
            foreach ($kpiCodes as $kpiCode) {
                $baseScore = $baselineScores[$kpiCode] ?? 3.0;
                
                // Progressive improvement logic
                if ($baseScore < 3.0) {
                    // Poor performers improve more
                    $improvement = 0.3 * $yearsPassed;
                } elseif ($baseScore < 4.0) {
                    // Average performers improve moderately
                    $improvement = 0.2 * $yearsPassed;
                } else {
                    // Good performers improve slightly
                    $improvement = 0.1 * $yearsPassed;
                }
                
                // Add some randomness (-0.1 to +0.1)
                $randomFactor = (mt_rand(-10, 10) / 100);
                $newScore = $baseScore + $improvement + $randomFactor;
                
                // Cap at 5.0
                $newScore = min(5.0, max(1.0, $newScore));
                $newScore = round($newScore, 1);
                
                // Calculate weighted score
                $weight = $weights[$kpiCode] / 100;
                $weightedScore = $newScore * $weight;
                
                // Insert
                $sql = "INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $staffId,
                    $kpiCode,
                    "$year-12-31",
                    $year,
                    $newScore,
                    round($weightedScore, 4)
                ]);
            }
            
            // Add comment for this year
            $comments = [
                1 => "Continues to excel with outstanding performance. Leadership skills developing well.",
                2 => "Showing improvement in customer service after training. Keep up the progress.",
                3 => "Sales performance improving steadily. Good response to training initiatives.",
                4 => "Operational efficiency showing positive trends. Continue development.",
                5 => "Time management improving. Consistency is getting better.",
                6 => "Product knowledge expanding. Good engagement with training materials.",
                7 => "Sales targets improving. Training having positive impact.",
                8 => "Inventory management skills developing. Good progress in operations.",
                9 => "Overall performance trending upward. Continued effort needed.",
                10 => "Exceptional performance maintained. Strong leadership demonstrated.",
                11 => "Customer service skills improving significantly. Positive trajectory.",
                12 => "Operational support getting better. Time management improving.",
                13 => "Consistent performer. Steady improvement across all areas."
            ];
            
            $sql = "INSERT INTO staff_comments (staff_id, supervisor_id, comment_date, comment_year, comment_text) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $staffId,
                (($staffId - 1) % 3) + 1, // Rotate supervisors
                "$year-12-31",
                $year,
                $comments[$staffId]
            ]);
        }
    }
    
    $pdo->commit();
    echo "\nSuccess! Multi-year data generated for 2023-2025.\n";
    echo "Total records created:\n";
    echo "- KPI Scores: " . (13 * 21 * 3) . " records\n";
    echo "- Comments: " . (13 * 3) . " records\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
