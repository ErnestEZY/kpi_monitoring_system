-- ============================================
-- TEST DATA FOR TRAINING RECOMMENDATIONS - 2026
-- ============================================
-- This creates test data for the CURRENT YEAR (2026)

-- SCENARIO 1: Critical Customer Service Gap
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.0, 
    ks.weighted_score = 2.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 1 
  AND ks.evaluation_year = 2026
  AND (km.kpi_group LIKE '%Customer%' OR km.kpi_group LIKE '%Service%');

-- SCENARIO 2: Critical Sales Gap
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.2, 
    ks.weighted_score = 2.2 * (km.weight_percentage / 100)
WHERE ks.staff_id = 2 
  AND ks.evaluation_year = 2026
  AND (km.kpi_group LIKE '%Sales%' OR km.kpi_group LIKE '%Target%');

-- SCENARIO 3: Moderate Operations Gap
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.0, 
    ks.weighted_score = 3.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 3 
  AND ks.evaluation_year = 2026
  AND (km.kpi_group LIKE '%Operation%' OR km.kpi_group LIKE '%Store%');

-- SCENARIO 4: Multiple Critical Gaps
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.3, 
    ks.weighted_score = 2.3 * (km.weight_percentage / 100)
WHERE ks.staff_id = 4 
  AND ks.evaluation_year = 2026
  AND (km.kpi_group LIKE '%Customer%' 
       OR km.kpi_group LIKE '%Service%'
       OR km.kpi_group LIKE '%Sales%');

-- SCENARIO 5: Minor Leadership Gap
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.8, 
    ks.weighted_score = 3.8 * (km.weight_percentage / 100)
WHERE ks.staff_id = 5 
  AND ks.evaluation_year = 2026
  AND (km.kpi_group LIKE '%Leadership%' 
       OR km.kpi_group LIKE '%Team%'
       OR km.kpi_group LIKE '%Initiative%');

-- SCENARIO 6: Critical All-Around Poor Performance
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.5, 
    ks.weighted_score = 2.5 * (km.weight_percentage / 100)
WHERE ks.staff_id = 6 
  AND ks.evaluation_year = 2026;

-- Verification
SELECT 
    'Test Data Created for 2026' as status,
    COUNT(DISTINCT s.staff_id) as staff_with_gaps
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE ks.evaluation_year = 2026
  AND ks.score < 4.0
  AND s.staff_id IN (1, 2, 3, 4, 5, 6);

-- Show results
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
    END as severity
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.evaluation_year = 2026
  AND s.staff_id IN (1, 2, 3, 4, 5, 6)
GROUP BY s.staff_id, s.staff_code, s.name, km.kpi_group
HAVING avg_score < 4.0
ORDER BY s.staff_id, avg_score ASC;
