-- ============================================
-- DIAGNOSTIC QUERIES FOR TRAINING RECOMMENDATIONS
-- ============================================
-- Run these to see what's happening with your data

-- Query 1: Check if staff 1-6 exist and are active
SELECT 
    staff_id,
    staff_code,
    name,
    department,
    status
FROM staff
WHERE staff_id IN (1, 2, 3, 4, 5, 6)
ORDER BY staff_id;

-- Query 2: Check if they have 2025 data
SELECT 
    s.staff_id,
    s.name,
    COUNT(ks.score_id) as total_kpis,
    COUNT(CASE WHEN ks.score < 4.0 THEN 1 END) as low_scores,
    ROUND(AVG(ks.score), 2) as avg_score,
    ROUND(SUM(ks.weighted_score), 2) as total_weighted
FROM staff s
LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = 2025
WHERE s.staff_id IN (1, 2, 3, 4, 5, 6)
GROUP BY s.staff_id, s.name
ORDER BY s.staff_id;

-- Query 3: Check what the API query would return (this is what the API uses)
SELECT 
    s.staff_id,
    s.staff_code,
    s.name as staff_name,
    s.department,
    km.kpi_group,
    ks.score,
    SUM(ks.weighted_score) as total_score
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE s.status = 'Active' 
  AND ks.evaluation_year = 2025
  AND s.staff_id IN (1, 2, 3, 4, 5, 6)
GROUP BY s.staff_id, km.kpi_group
HAVING ks.score < 4
ORDER BY s.staff_id, ks.score ASC;

-- Query 4: Check ALL staff with scores < 4.0 (what API should find)
SELECT 
    s.staff_id,
    s.staff_code,
    s.name,
    s.department,
    km.kpi_group,
    ROUND(AVG(ks.score), 2) as avg_score
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE s.status = 'Active' 
  AND ks.evaluation_year = 2025
GROUP BY s.staff_id, s.staff_code, s.name, s.department, km.kpi_group
HAVING avg_score < 4.0
ORDER BY s.staff_id, avg_score ASC;

-- Query 5: Check what year has the most data
SELECT 
    evaluation_year,
    COUNT(DISTINCT staff_id) as staff_count,
    COUNT(*) as total_scores,
    ROUND(AVG(score), 2) as avg_score
FROM kpi_scores
GROUP BY evaluation_year
ORDER BY evaluation_year DESC;

-- Query 6: Check all unique departments
SELECT DISTINCT department
FROM staff
WHERE status = 'Active'
ORDER BY department;

-- Query 7: Simple check - do we have ANY staff with low scores?
SELECT 
    COUNT(DISTINCT s.staff_id) as staff_with_low_scores
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
WHERE s.status = 'Active'
  AND ks.evaluation_year = 2025
  AND ks.score < 4.0;

-- Query 8: What does the exact API query return?
SELECT 
    s.staff_id,
    s.staff_code,
    s.name,
    s.department,
    km.kpi_group,
    ks.score,
    SUM(ks.weighted_score) as total_score
FROM staff s
JOIN kpi_scores ks ON s.staff_id = ks.staff_id
JOIN kpi_master km ON ks.kpi_code = km.kpi_code
WHERE s.status = 'Active' AND ks.evaluation_year = 2025
GROUP BY s.staff_id, km.kpi_group
HAVING ks.score < 4
ORDER BY s.staff_id;
