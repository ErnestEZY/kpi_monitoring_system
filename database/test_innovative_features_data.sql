-- Test Data for Predictive Alerts and Training Recommendations
-- This script adds specific performance patterns to trigger the innovative features

-- ============================================
-- PREDICTIVE ALERTS TEST DATA
-- ============================================
-- We'll create staff with declining performance to trigger alerts

-- Staff with CRITICAL RISK (score < 2.5, declining trend)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.0, 
    ks.weighted_score = 2.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 1 AND ks.evaluation_year = 2025;

-- Staff with HIGH RISK (score 2.5-3.5, declining)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.0, 
    ks.weighted_score = 3.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 2 AND ks.evaluation_year = 2025;

-- Staff with MEDIUM RISK (score 3.5-4.0, slight decline)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.7, 
    ks.weighted_score = 3.7 * (km.weight_percentage / 100)
WHERE ks.staff_id = 3 AND ks.evaluation_year = 2025;

-- ============================================
-- TRAINING RECOMMENDATIONS TEST DATA
-- ============================================
-- Create specific skill gaps to trigger training recommendations

-- Staff 1: Weak in Customer Service (KPI Group 3)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.5, 
    ks.weighted_score = 2.5 * (km.weight_percentage / 100)
WHERE ks.staff_id = 1 
  AND ks.evaluation_year = 2025
  AND km.kpi_group LIKE '%Customer%';

-- Staff 2: Weak in Sales Performance (KPI Group 4)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.8, 
    ks.weighted_score = 2.8 * (km.weight_percentage / 100)
WHERE ks.staff_id = 2 
  AND ks.evaluation_year = 2025
  AND km.kpi_group LIKE '%Sales%';

-- Staff 3: Weak in Operations (KPI Group 5)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.0, 
    ks.weighted_score = 3.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 3 
  AND ks.evaluation_year = 2025
  AND km.kpi_group LIKE '%Operations%';

-- Staff 4: Multiple weak areas (high priority for training)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.3, 
    ks.weighted_score = 2.3 * (km.weight_percentage / 100)
WHERE ks.staff_id = 4 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Customer%' OR km.kpi_group LIKE '%Sales%');

-- Staff 5: Critical gaps (score < 2.5)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.0, 
    ks.weighted_score = 2.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 5 
  AND ks.evaluation_year = 2025
  AND km.section_number = 2;  -- KPI Achievement section

-- ============================================
-- VERIFY THE CHANGES
-- ============================================

-- Check staff with low scores (should trigger alerts)
SELECT 
    s.staff_id,
    s.name,
    s.department,
    SUM(ks.weighted_score) as total_score,
    CASE 
        WHEN SUM(ks.weighted_score) < 2.5 THEN 'CRITICAL RISK'
        WHEN SUM(ks.weighted_score) < 3.5 THEN 'HIGH RISK'
        WHEN SUM(ks.weighted_score) < 4.0 THEN 'MEDIUM RISK'
        ELSE 'LOW RISK'
    END as risk_level
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE ks.evaluation_year = 2025
GROUP BY s.staff_id
HAVING total_score < 4.0
ORDER BY total_score ASC;

-- Check staff with skill gaps (should trigger training recommendations)
SELECT 
    s.staff_id,
    s.name,
    km.kpi_group,
    AVG(ks.score) as avg_score,
    CASE 
        WHEN AVG(ks.score) < 2.5 THEN 'CRITICAL GAP'
        WHEN AVG(ks.score) < 3.5 THEN 'MODERATE GAP'
        ELSE 'MINOR GAP'
    END as gap_severity
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.evaluation_year = 2025
GROUP BY s.staff_id, km.kpi_group
HAVING avg_score < 4.0
ORDER BY s.staff_id, avg_score ASC;

-- Summary report
SELECT 
    'Predictive Alerts' as feature,
    COUNT(DISTINCT s.staff_id) as staff_count,
    'Staff with scores < 4.0 will trigger alerts' as description
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE ks.evaluation_year = 2025
GROUP BY s.staff_id
HAVING SUM(ks.weighted_score) < 4.0

UNION ALL

SELECT 
    'Training Recommendations' as feature,
    COUNT(DISTINCT s.staff_id) as staff_count,
    'Staff with category scores < 4.0 will get training suggestions' as description
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE ks.evaluation_year = 2025 AND ks.score < 4.0;
