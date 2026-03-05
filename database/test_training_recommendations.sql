-- ============================================
-- TEST DATA FOR TRAINING RECOMMENDATIONS
-- ============================================
-- This creates 6 staff with different skill gaps to test the feature

-- First, let's check what staff and KPI groups exist
-- Run this to see available staff:
-- SELECT staff_id, staff_code, name, department FROM staff WHERE status = 'Active' LIMIT 10;

-- Run this to see available KPI groups:
-- SELECT DISTINCT kpi_group FROM kpi_master ORDER BY kpi_group;

-- ============================================
-- SCENARIO 1: Critical Customer Service Gap
-- ============================================
-- Staff 1: Very weak in Customer Service (score 2.0)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.0, 
    ks.weighted_score = 2.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 1 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Customer%' OR km.kpi_group LIKE '%Service%');

-- ============================================
-- SCENARIO 2: Critical Sales Gap
-- ============================================
-- Staff 2: Very weak in Sales (score 2.2)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.2, 
    ks.weighted_score = 2.2 * (km.weight_percentage / 100)
WHERE ks.staff_id = 2 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Sales%' OR km.kpi_group LIKE '%Target%');

-- ============================================
-- SCENARIO 3: Moderate Operations Gap
-- ============================================
-- Staff 3: Moderate weakness in Operations (score 3.0)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.0, 
    ks.weighted_score = 3.0 * (km.weight_percentage / 100)
WHERE ks.staff_id = 3 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Operation%' OR km.kpi_group LIKE '%Store%');

-- ============================================
-- SCENARIO 4: Multiple Critical Gaps
-- ============================================
-- Staff 4: Weak in BOTH Customer Service AND Sales (score 2.3)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.3, 
    ks.weighted_score = 2.3 * (km.weight_percentage / 100)
WHERE ks.staff_id = 4 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Customer%' 
       OR km.kpi_group LIKE '%Service%'
       OR km.kpi_group LIKE '%Sales%');

-- ============================================
-- SCENARIO 5: Minor Leadership Gap
-- ============================================
-- Staff 5: Minor weakness in Leadership/Teamwork (score 3.8)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 3.8, 
    ks.weighted_score = 3.8 * (km.weight_percentage / 100)
WHERE ks.staff_id = 5 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Leadership%' 
       OR km.kpi_group LIKE '%Team%'
       OR km.kpi_group LIKE '%Initiative%');

-- ============================================
-- SCENARIO 6: Critical All-Around Poor Performance
-- ============================================
-- Staff 6: Poor across ALL categories (score 2.5)
UPDATE kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
SET ks.score = 2.5, 
    ks.weighted_score = 2.5 * (km.weight_percentage / 100)
WHERE ks.staff_id = 6 
  AND ks.evaluation_year = 2025;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Query 1: Check which staff have low scores
SELECT 
    s.staff_id,
    s.staff_code,
    s.name,
    s.department,
    ROUND(AVG(ks.score), 2) as avg_score,
    COUNT(CASE WHEN ks.score < 2.5 THEN 1 END) as critical_count,
    COUNT(CASE WHEN ks.score >= 2.5 AND ks.score < 3.5 THEN 1 END) as moderate_count,
    COUNT(CASE WHEN ks.score >= 3.5 AND ks.score < 4.0 THEN 1 END) as minor_count
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE ks.evaluation_year = 2025
  AND s.staff_id IN (1, 2, 3, 4, 5, 6)
GROUP BY s.staff_id, s.staff_code, s.name, s.department
ORDER BY avg_score ASC;

-- Query 2: Check skill gaps by category
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
WHERE ks.evaluation_year = 2025
  AND s.staff_id IN (1, 2, 3, 4, 5, 6)
GROUP BY s.staff_id, s.staff_code, s.name, km.kpi_group
HAVING avg_score < 4.0
ORDER BY s.staff_id, avg_score ASC;

-- Query 3: Summary of expected results
SELECT 
    'Expected Results' as info,
    '6 staff with training needs' as staff_count,
    '2-3 with HIGH priority (critical gaps)' as `high_priority`,
    '2-3 with MEDIUM priority (moderate gaps)' as `medium_priority`,
    '1-2 with LOW priority (minor gaps)' as `low_priority`;

-- Query 4: Check if data was actually updated
SELECT 
    'Staff 1' as staff,
    'Customer Service gaps' as expected_gap,
    COUNT(*) as affected_kpis,
    ROUND(AVG(ks.score), 2) as avg_score
FROM kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.staff_id = 1 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Customer%' OR km.kpi_group LIKE '%Service%')

UNION ALL

SELECT 
    'Staff 2' as staff,
    'Sales gaps' as expected_gap,
    COUNT(*) as affected_kpis,
    ROUND(AVG(ks.score), 2) as avg_score
FROM kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.staff_id = 2 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Sales%' OR km.kpi_group LIKE '%Target%')

UNION ALL

SELECT 
    'Staff 3' as staff,
    'Operations gaps' as expected_gap,
    COUNT(*) as affected_kpis,
    ROUND(AVG(ks.score), 2) as avg_score
FROM kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.staff_id = 3 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Operation%' OR km.kpi_group LIKE '%Store%')

UNION ALL

SELECT 
    'Staff 4' as staff,
    'Multiple gaps' as expected_gap,
    COUNT(*) as affected_kpis,
    ROUND(AVG(ks.score), 2) as avg_score
FROM kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.staff_id = 4 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Customer%' OR km.kpi_group LIKE '%Sales%')

UNION ALL

SELECT 
    'Staff 5' as staff,
    'Leadership gaps' as expected_gap,
    COUNT(*) as affected_kpis,
    ROUND(AVG(ks.score), 2) as avg_score
FROM kpi_scores ks
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE ks.staff_id = 5 
  AND ks.evaluation_year = 2025
  AND (km.kpi_group LIKE '%Leadership%' OR km.kpi_group LIKE '%Team%')

UNION ALL

SELECT 
    'Staff 6' as staff,
    'All categories' as expected_gap,
    COUNT(*) as affected_kpis,
    ROUND(AVG(ks.score), 2) as avg_score
FROM kpi_scores ks
WHERE ks.staff_id = 6 
  AND ks.evaluation_year = 2025;

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT 
    '✅ Test data created successfully!' as status,
    'Now visit: supervisor/training_recommendations.php' as next_step,
    'You should see 6 staff with various training needs' as expected_result;
