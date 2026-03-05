-- SIMPLIFIED Test Data for Predictive Alerts and Training Recommendations
-- This version is easier to understand and debug

-- ============================================
-- STEP 1: PREDICTIVE ALERTS TEST DATA
-- ============================================
-- Create staff with different risk levels

-- CRITICAL RISK: Staff 1 (all scores = 2.0)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.0, 
    ks.weighted_score = 2.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 1 
  AND ks.evaluation_year = 2025;

-- HIGH RISK: Staff 2 (all scores = 3.0)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.0, 
    ks.weighted_score = 3.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 2 
  AND ks.evaluation_year = 2025;

-- MEDIUM RISK: Staff 3 (all scores = 3.7)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.7, 
    ks.weighted_score = 3.7 * (km.weight_percentage / 100)
WHERE ks.staff_id = 3 
  AND ks.evaluation_year = 2025;

-- ============================================
-- STEP 2: TRAINING RECOMMENDATIONS TEST DATA
-- ============================================
-- Create specific skill gaps

-- Staff 4: Weak in Customer Service areas
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.5, 
    ks.weighted_score = 2.5 * (km.weight_percentage / 100)
WHERE ks.staff_id = 4 
  AND ks.evaluation_year = 2025
  AND km.kpi_group LIKE '%Customer%';

-- Staff 5: Weak in Sales areas
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.8, 
    ks.weighted_score = 2.8 * (km.weight_percentage / 100)
WHERE ks.staff_id = 5 
  AND ks.evaluation_year = 2025
  AND km.kpi_group LIKE '%Sales%';

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Query 1: Check overall scores (for Predictive Alerts)
SELECT 
    s.staff_id,
    s.staff_code,
    s.name,
    s.department,
    ROUND(SUM(ks.weighted_score), 2) as total_score,
    CASE 
        WHEN SUM(ks.weighted_score) < 2.5 THEN '🔴 CRITICAL RISK'
        WHEN SUM(ks.weighted_score) < 3.5 THEN '🟠 HIGH RISK'
        WHEN SUM(ks.weighted_score) < 4.0 THEN '🟡 MEDIUM RISK'
        ELSE '🟢 LOW RISK'
    END as risk_level
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE ks.evaluation_year = 2025
GROUP BY s.staff_id, s.staff_code, s.name, s.department
ORDER BY total_score ASC
LIMIT 10;

-- Query 2: Check skill gaps by category (for Training Recommendations)
SELECT 
    s.staff_id,
    s.staff_code,
    s.name,
    km.kpi_group,
    ROUND(AVG(ks.score), 2) as avg_score,
    CASE 
        WHEN AVG(ks.score) < 2.5 THEN '🔴 CRITICAL'
        WHEN AVG(ks.score) < 3.5 THEN '🟡 MODERATE'
        WHEN AVG(ks.score) < 4.0 THEN '🔵 MINOR'
        ELSE '🟢 GOOD'
    END as gap_severity
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.evaluation_year = 2025
GROUP BY s.staff_id, s.staff_code, s.name, km.kpi_group
HAVING avg_score < 4.0
ORDER BY s.staff_id, avg_score ASC;

-- Query 3: Summary counts
SELECT 
    'Predictive Alerts' as feature,
    COUNT(*) as affected_staff,
    'Staff with total score < 4.0' as criteria
FROM (
    SELECT s.staff_id
    FROM staff s
    JOIN kpi_scores ks ON s.staff_id = ks.staff_id
    WHERE ks.evaluation_year = 2025
    GROUP BY s.staff_id
    HAVING SUM(ks.weighted_score) < 4.0
) as alerts

UNION ALL

SELECT 
    'Training Recommendations' as feature,
    COUNT(DISTINCT s.staff_id) as affected_staff,
    'Staff with any category score < 4.0' as criteria
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE ks.evaluation_year = 2025 
  AND ks.score < 4.0;

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT 
    '✅ Test data imported successfully!' as status,
    'Now visit the Predictive Alerts and Training Recommendations pages' as next_step;
