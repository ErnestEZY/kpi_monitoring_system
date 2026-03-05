<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'overall_score':
            // Calculate overall score for a staff member in a specific year
            $staff_id = $_GET['staff_id'] ?? null;
            $year = $_GET['year'] ?? date('Y');
            
            if (!$staff_id) {
                throw new Exception('Staff ID is required');
            }
            
            $result = $calculator->calculateOverallScore($staff_id, $year);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'performance_trend':
            // Get performance trend across all years
            $staff_id = $_GET['staff_id'] ?? null;
            
            if (!$staff_id) {
                throw new Exception('Staff ID is required');
            }
            
            $trend = $calculator->getPerformanceTrend($staff_id);
            echo json_encode(['success' => true, 'data' => $trend]);
            break;
            
        case 'at_risk_staff':
            // Get list of at-risk staff members
            $year = $_GET['year'] ?? date('Y');
            $at_risk = $calculator->getAtRiskStaff($year);
            echo json_encode(['success' => true, 'data' => $at_risk]);
            break;
            
        case 'top_performers':
            // Get top performers for a specific year
            $year = $_GET['year'] ?? date('Y');
            $threshold = $_GET['threshold'] ?? 85;
            $top_performers = $calculator->getTopPerformers($year, $threshold);
            echo json_encode(['success' => true, 'data' => $top_performers]);
            break;
            
        case 'category_averages':
            // Get category-wise averages
            $year = $_GET['year'] ?? date('Y');
            $averages = $calculator->getCategoryAverages($year);
            echo json_encode(['success' => true, 'data' => $averages]);
            break;
            
        case 'narrative':
            // Generate narrative insights
            $staff_id = $_GET['staff_id'] ?? null;
            
            if (!$staff_id) {
                throw new Exception('Staff ID is required');
            }
            
            $narrative = $calculator->generateNarrative($staff_id);
            echo json_encode(['success' => true, 'data' => $narrative]);
            break;
            
        case 'compare_to_team':
            // Compare staff to team average
            $staff_id = $_GET['staff_id'] ?? null;
            $year = $_GET['year'] ?? date('Y');
            
            if (!$staff_id) {
                throw new Exception('Staff ID is required');
            }
            
            $comparison = $calculator->compareToTeamAverage($staff_id, $year);
            echo json_encode(['success' => true, 'data' => $comparison]);
            break;
            
        case 'all_staff_scores':
            // Get all staff scores for a specific year
            $year = $_GET['year'] ?? date('Y');
            
            $sql = "SELECT DISTINCT s.staff_id, s.staff_code, s.name as full_name, 
                    s.department, s.status
                    FROM staff s
                    ORDER BY s.name";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $staff_list = $stmt->fetchAll();
            
            $results = [];
            foreach ($staff_list as $staff) {
                $score_data = $calculator->calculateOverallScore($staff['staff_id'], $year);
                $classification = $calculator->getPerformanceClassification($score_data['overall_score']);
                
                $results[] = [
                    'staff_id' => $staff['staff_id'],
                    'staff_number' => $staff['staff_code'],
                    'full_name' => $staff['full_name'],
                    'department' => $staff['department'],
                    'department_id' => $staff['department'], // For compatibility
                    'status' => $staff['status'],
                    'overall_score' => $score_data['overall_score'],
                    'classification' => $classification['label'],
                    'badge_class' => $classification['badge'],
                    'has_data' => $score_data['has_data']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $results]);
            break;
            
        case 'performance_distribution':
            // Get performance distribution for a year
            $year = $_GET['year'] ?? date('Y');
            
            $sql = "SELECT DISTINCT s.staff_id
                    FROM staff s
                    WHERE s.status = 'Active'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $staff_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $distribution = [
                'Top Performer' => 0,
                'Good Performer' => 0,
                'Satisfactory' => 0,
                'Needs Improvement' => 0,
                'Critical' => 0
            ];
            
            foreach ($staff_ids as $staff_id) {
                $result = $calculator->calculateOverallScore($staff_id, $year);
                if ($result['has_data']) {
                    $class = $calculator->classifyPerformance($result['overall_score']);
                    $distribution[$class]++;
                }
            }
            
            echo json_encode(['success' => true, 'data' => $distribution]);
            break;
            
        case 'year_over_year_comparison':
            // Compare performance across multiple years
            $staff_id = $_GET['staff_id'] ?? null;
            
            if (!$staff_id) {
                throw new Exception('Staff ID is required');
            }
            
            $sql = "SELECT DISTINCT evaluation_year 
                    FROM kpi_scores 
                    WHERE staff_id = ? 
                    ORDER BY evaluation_year";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$staff_id]);
            $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $comparison = [];
            foreach ($years as $year) {
                $result = $calculator->calculateOverallScore($staff_id, $year);
                $comparison[$year] = [
                    'overall_score' => $result['overall_score'],
                    'categories' => $result['category_scores']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $comparison]);
            break;
            
        case 'save_comment':
            // Save supervisor comment and training recommendation
            $staff_id = $_POST['staff_id'] ?? null;
            $supervisor_id = $_POST['supervisor_id'] ?? null;
            $evaluation_year = $_POST['evaluation_year'] ?? null;
            $supervisor_comment = $_POST['supervisor_comment'] ?? '';
            $training_recommendation = $_POST['training_recommendation'] ?? '';
            
            if (!$staff_id || !$supervisor_id || !$evaluation_year) {
                throw new Exception('Missing required fields');
            }
            
            // Check if comment already exists for this year
            $sql = "SELECT comment_id FROM staff_comments 
                    WHERE staff_id = ? AND evaluation_year = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$staff_id, $evaluation_year]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing comment
                $sql = "UPDATE staff_comments 
                        SET supervisor_comment = ?, training_recommendation = ?, 
                            supervisor_id = ?, created_at = NOW()
                        WHERE comment_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$supervisor_comment, $training_recommendation, 
                               $supervisor_id, $existing['comment_id']]);
            } else {
                // Insert new comment
                $sql = "INSERT INTO staff_comments 
                        (staff_id, supervisor_id, evaluation_year, supervisor_comment, training_recommendation, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$staff_id, $supervisor_id, $evaluation_year, 
                               $supervisor_comment, $training_recommendation]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Comment saved successfully']);
            break;
            
        case 'get_comment':
            // Get existing comment for a specific year
            $staff_id = $_GET['staff_id'] ?? null;
            $year = $_GET['year'] ?? null;
            
            if (!$staff_id || !$year) {
                throw new Exception('Staff ID and year are required');
            }
            
            $sql = "SELECT * FROM staff_comments 
                    WHERE staff_id = ? AND evaluation_year = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$staff_id, $year]);
            $comment = $stmt->fetch();
            
            if ($comment) {
                echo json_encode(['success' => true, 'comment' => $comment]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No comment found']);
            }
            break;
            
        case 'predict_performance':
            // Predict future performance using linear regression
            $staff_id = $_GET['staff_id'] ?? null;
            
            if (!$staff_id) {
                throw new Exception('Staff ID is required');
            }
            
            $prediction = $calculator->predictPerformance($staff_id);
            echo json_encode($prediction);
            break;
            
        case 'smart_training_recommendations':
            // Get smart training recommendations with compatibility scoring
            $staff_id = $_GET['staff_id'] ?? null;
            $year = $_GET['year'] ?? date('Y');
            
            if (!$staff_id) {
                throw new Exception('Staff ID is required');
            }
            
            $recommendations = $calculator->getSmartTrainingRecommendations($staff_id, $year);
            echo json_encode($recommendations);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
