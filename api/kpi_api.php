<?php
/**
 * KPI API Endpoint
 * Handles all AJAX requests for the KPI dashboard
 */

session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_statistics':
        getStatistics($pdo);
        break;
    
    case 'get_chart_data':
        getChartData($pdo);
        break;
    
    case 'get_staff_performance':
        getStaffPerformance($pdo);
        break;
    
    case 'get_staff_comment':
        getStaffComment($pdo);
        break;
    
    case 'save_comment':
        saveComment($pdo);
        break;
    
    case 'save_scores':
        saveScores($pdo);
        break;
    
    case 'get_years':
        getYears($pdo);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Get dashboard statistics
 */
function getStatistics($pdo) {
    $year = $_GET['year'] ?? date('Y');
    
    try {
        // Total staff
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff WHERE status = 'Active'");
        $total_staff = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get staff with scores for the year
        $stmt = $pdo->prepare("
            SELECT 
                s.staff_id,
                SUM(ks.weighted_score) as final_score
            FROM staff s
            LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = ?
            WHERE s.status = 'Active'
            GROUP BY s.staff_id
            HAVING final_score IS NOT NULL
        ");
        $stmt->execute([$year]);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        $top_performers = 0;
        $at_risk = 0;
        $total_score = 0;
        
        foreach ($scores as $score) {
            $final = $score['final_score'];
            $total_score += $final;
            
            if ($final >= 4.5) {
                $top_performers++;
            } elseif ($final < 2.5) {
                $at_risk++;
            }
        }
        
        $average_score = count($scores) > 0 ? $total_score / count($scores) : 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_staff' => $total_staff,
                'top_performers' => $top_performers,
                'at_risk' => $at_risk,
                'average_score' => $average_score
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Get chart data
 */
function getChartData($pdo) {
    $year = $_GET['year'] ?? date('Y');
    
    try {
        // Performance distribution
        $stmt = $pdo->prepare("
            SELECT 
                s.name,
                SUM(ks.weighted_score) as final_score
            FROM staff s
            LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = ?
            WHERE s.status = 'Active'
            GROUP BY s.staff_id, s.name
            HAVING final_score IS NOT NULL
            ORDER BY final_score DESC
        ");
        $stmt->execute([$year]);
        $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $values = [];
        foreach ($performance as $p) {
            $labels[] = $p['name'];
            $values[] = round($p['final_score'], 2);
        }
        
        // Score categories
        $categories = [
            'Excellent' => 0,
            'Good' => 0,
            'Satisfactory' => 0,
            'Poor' => 0,
            'Very Poor' => 0
        ];
        
        foreach ($performance as $p) {
            $score = $p['final_score'];
            if ($score >= 4.5) {
                $categories['Excellent']++;
            } elseif ($score >= 3.5) {
                $categories['Good']++;
            } elseif ($score >= 2.5) {
                $categories['Satisfactory']++;
            } elseif ($score >= 1.5) {
                $categories['Poor']++;
            } else {
                $categories['Very Poor']++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'performance_distribution' => [
                    'labels' => $labels,
                    'values' => $values
                ],
                'score_categories' => [
                    'labels' => array_keys($categories),
                    'values' => array_values($categories)
                ]
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Get staff performance data
 */
function getStaffPerformance($pdo) {
    $year = $_GET['year'] ?? date('Y');
    
    try {
        // Check if database connection is working
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
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
            WHERE s.status = 'Active' AND ks.kpi_code IS NOT NULL
            GROUP BY s.staff_id, s.staff_code, s.name, s.department
            HAVING final_score IS NOT NULL AND final_score > 0
            ORDER BY final_score DESC
        ");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement');
        }
        
        $stmt->execute([$year]);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the count
        error_log("Staff performance query returned " . count($staff) . " records for year $year");
        
        // Add rating description
        foreach ($staff as &$s) {
            $score = $s['final_score'];
            if ($score >= 4.5) {
                $s['rating'] = 'Excellent';
            } elseif ($score >= 3.5) {
                $s['rating'] = 'Good';
            } elseif ($score >= 2.5) {
                $s['rating'] = 'Satisfactory';
            } elseif ($score >= 1.5) {
                $s['rating'] = 'Poor';
            } else {
                $s['rating'] = 'Very Poor';
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $staff,
            'debug' => [
                'year' => $year,
                'count' => count($staff)
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in getStaffPerformance: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("General error in getStaffPerformance: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Get staff comment
 */
function getStaffComment($pdo) {
    $staff_id = $_GET['staff_id'] ?? 0;
    $year = $_GET['year'] ?? date('Y');
    
    try {
        $stmt = $pdo->prepare("
            SELECT supervisor_comment, training_recommendation
            FROM staff_comments
            WHERE staff_id = ? AND evaluation_year = ?
        ");
        $stmt->execute([$staff_id, $year]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comment) {
            $comment = [
                'supervisor_comment' => '',
                'training_recommendation' => ''
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $comment
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Save staff comment
 */
function saveComment($pdo) {
    $staff_id = $_POST['staff_id'] ?? 0;
    $year = $_POST['year'] ?? date('Y');
    $supervisor_comment = $_POST['supervisor_comment'] ?? '';
    $training_recommendation = $_POST['training_recommendation'] ?? '';
    $supervisor_id = getSupervisorId();
    
    try {
        // Check if comment exists
        $stmt = $pdo->prepare("
            SELECT comment_id FROM staff_comments 
            WHERE staff_id = ? AND evaluation_year = ?
        ");
        $stmt->execute([$staff_id, $year]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE staff_comments 
                SET supervisor_comment = ?, 
                    training_recommendation = ?,
                    supervisor_id = ?,
                    updated_at = NOW()
                WHERE staff_id = ? AND evaluation_year = ?
            ");
            $stmt->execute([
                $supervisor_comment,
                $training_recommendation,
                $supervisor_id,
                $staff_id,
                $year
            ]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO staff_comments 
                (staff_id, evaluation_year, supervisor_comment, training_recommendation, supervisor_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $staff_id,
                $year,
                $supervisor_comment,
                $training_recommendation,
                $supervisor_id
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment saved successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}


/**
 * Get available years with data
 */
function getYears($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT evaluation_year 
            FROM kpi_scores 
            ORDER BY evaluation_year DESC
        ");
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'success' => true,
            'data' => $years
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Save KPI scores for a staff member
 */
function saveScores($pdo) {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid input data']);
            return;
        }
        
        $staff_id = $input['staff_id'] ?? null;
        $evaluation_year = $input['evaluation_year'] ?? null;
        $evaluation_date = $input['evaluation_date'] ?? date('Y-m-d');
        $scores = $input['scores'] ?? [];
        
        if (!$staff_id || !$evaluation_year || empty($scores)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        $saved_count = 0;
        $errors = [];
        
        foreach ($scores as $kpi_code => $score) {
            // Validate score
            if ($score < 1 || $score > 5) {
                $errors[] = "Invalid score for $kpi_code: $score";
                continue;
            }
            
            // Get KPI weight
            $stmt = $pdo->prepare("SELECT weight_percentage FROM kpi_master WHERE kpi_code = ?");
            $stmt->execute([$kpi_code]);
            $kpi = $stmt->fetch();
            
            if (!$kpi) {
                $errors[] = "KPI not found: $kpi_code";
                continue;
            }
            
            // Calculate weighted score
            $weighted_score = ($score / 5) * ($kpi['weight_percentage'] / 100);
            
            // Insert or update score
            $sql = "INSERT INTO kpi_scores (staff_id, kpi_code, evaluation_date, evaluation_year, score, weighted_score)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        score = VALUES(score),
                        weighted_score = VALUES(weighted_score),
                        updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $staff_id,
                $kpi_code,
                $evaluation_date,
                $evaluation_year,
                $score,
                $weighted_score
            ]);
            
            $saved_count++;
        }
        
        // Commit transaction
        $pdo->commit();
        
        $response = [
            'success' => true,
            'message' => "Successfully saved $saved_count KPI scores",
            'saved_count' => $saved_count
        ];
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Error saving scores: ' . $e->getMessage()
        ]);
    }
}
