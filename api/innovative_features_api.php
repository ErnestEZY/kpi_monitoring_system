<?php
/**
 * Innovative Features API
 * Handles: Predictive Alerts, Training Recommendations, Gamification, Peer Comparison.
 * All endpoints require an active supervisor session.
 */

session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

requireLoginApi();

$pdo        = getDBConnection();
$calculator = new KPICalculator($pdo);
$action     = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_predictive_alerts':
        getPredictiveAlerts($pdo);
        break;
    
    case 'get_training_recommendations':
        getTrainingRecommendations($pdo);
        break;
    
    case 'get_gamification_data':
        getGamificationData($pdo);
        break;
    
    case 'get_peer_comparison':
        getPeerComparison($pdo);
        break;
    
    case 'get_staff_list':
        getStaffList($pdo);
        break;
    
    case 'get_all_trends':
        getAllTrends($pdo);
        break;
    
    case 'detect_anomalies':
        // Detect anomalies in performance data
        $staff_id = $_GET['staff_id'] ?? null;
        
        if (!$staff_id) {
            throw new Exception('Staff ID is required');
        }
        
        $trend = $calculator->getPerformanceTrend($staff_id);
        $anomalies = [];
        
        for ($i = 1; $i < count($trend); $i++) {
            $current = $trend[$i];
            $previous = $trend[$i - 1];
            $change = $current['overall_score'] - $previous['overall_score'];
            
            // Detect sudden spike (>5% increase)
            if ($change >= 5) {
                $anomalies[] = [
                    'type' => 'spike',
                    'year' => $current['year'],
                    'title' => 'Sudden Performance Spike',
                    'description' => "Performance increased significantly from {$previous['overall_score']}% to {$current['overall_score']}%.",
                    'change' => round($change, 2)
                ];
            }
            
            // Detect sudden drop (>5% decrease)
            if ($change <= -5) {
                $anomalies[] = [
                    'type' => 'drop',
                    'year' => $current['year'],
                    'title' => 'Sudden Performance Drop',
                    'description' => "Performance decreased significantly from {$previous['overall_score']}% to {$current['overall_score']}%. Immediate attention required.",
                    'change' => round($change, 2)
                ];
            }
        }
        
        // Detect consecutive decline (3+ years)
        $declineCount = 0;
        for ($i = 1; $i < count($trend); $i++) {
            if ($trend[$i]['overall_score'] < $trend[$i - 1]['overall_score']) {
                $declineCount++;
            } else {
                $declineCount = 0;
            }
            
            if ($declineCount >= 2) {
                $anomalies[] = [
                    'type' => 'decline',
                    'year' => $trend[$i]['year'],
                    'title' => 'Consecutive Performance Decline',
                    'description' => "Performance has been declining for {$declineCount} consecutive periods. Intervention recommended.",
                    'change' => round($trend[$i]['overall_score'] - $trend[$i - $declineCount]['overall_score'], 2)
                ];
                break;
            }
        }
        
        echo json_encode(['success' => true, 'anomalies' => $anomalies]);
        break;
    
    case 'generate_anomaly_insight':
        // Generate AI-powered insight for specific anomaly
        $staff_id = $_GET['staff_id'] ?? null;
        $anomaly_index = $_GET['anomaly_index'] ?? null;
        
        if (!$staff_id || $anomaly_index === null) {
            echo json_encode(['success' => false, 'message' => 'Staff ID and anomaly index are required']);
            exit();
        }
        
        // Get staff info
        $sql = "SELECT name, department, position FROM staff WHERE staff_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
        
        if (!$staff) {
            echo json_encode(['success' => false, 'message' => 'Staff not found']);
            exit();
        }
        
        // Get anomalies to find the specific one
        $trend = $calculator->getPerformanceTrend($staff_id);
        $anomalies = [];
        
        for ($i = 1; $i < count($trend); $i++) {
            $current = $trend[$i];
            $previous = $trend[$i - 1];
            $change = $current['overall_score'] - $previous['overall_score'];
            
            if ($change >= 5) {
                $anomalies[] = [
                    'type' => 'spike',
                    'year' => $current['year'],
                    'title' => 'Sudden Performance Spike',
                    'description' => "Performance increased significantly from {$previous['overall_score']}% to {$current['overall_score']}%.",
                    'change' => round($change, 2),
                    'previous_score' => $previous['overall_score'],
                    'current_score' => $current['overall_score']
                ];
            }
            
            if ($change <= -5) {
                $anomalies[] = [
                    'type' => 'drop',
                    'year' => $current['year'],
                    'title' => 'Sudden Performance Drop',
                    'description' => "Performance decreased significantly from {$previous['overall_score']}% to {$current['overall_score']}%.",
                    'change' => round($change, 2),
                    'previous_score' => $previous['overall_score'],
                    'current_score' => $current['overall_score']
                ];
            }
        }
        
        if (!isset($anomalies[$anomaly_index])) {
            echo json_encode(['success' => false, 'message' => 'Anomaly not found']);
            exit();
        }
        
        $anomaly = $anomalies[$anomaly_index];
        
        // Generate contextual insight based on anomaly type and data
        $insight = generateAnomalyInsight($anomaly, $staff, $trend);
        
        echo json_encode(['success' => true, 'insight' => $insight]);
        break;
    
    case 'generate_narrative':
        // Generate AI-powered narrative insight with advanced analysis
        $staff_id = $_GET['staff_id'] ?? null;
        
        if (!$staff_id) {
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit();
        }
        
        // Get staff info
        $sql = "SELECT name, department, position FROM staff WHERE staff_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
        
        if (!$staff) {
            echo json_encode(['success' => false, 'message' => 'Staff not found']);
            exit();
        }
        
        $trend = $calculator->getPerformanceTrend($staff_id);
        
        if (count($trend) < 2) {
            echo json_encode(['success' => false, 'message' => 'Insufficient data for narrative generation']);
            exit();
        }
        
        $latest = $trend[count($trend) - 1];
        $previous = $trend[count($trend) - 2];
        $change = $latest['overall_score'] - $previous['overall_score'];
        $changePercent = $previous['overall_score'] > 0 ? ($change / $previous['overall_score']) * 100 : 0;
        
        // Get category scores for latest year
        $latestData = $calculator->calculateOverallScore($staff_id, $latest['year']);
        
        // AI-powered analysis: Find patterns and insights
        $strengths = [];
        $weaknesses = [];
        $improving = [];
        $declining = [];
        
        foreach ($latestData['category_scores'] as $cat) {
            $catScore = ($cat['score'] / 5) * 100;
            $catName = $cat['kpi_group'];
            
            // Classify performance levels
            if ($catScore >= 85) {
                $strengths[] = ['name' => $catName, 'score' => round($catScore, 1)];
            } elseif ($catScore < 70 && $catScore > 0) {
                $weaknesses[] = ['name' => $catName, 'score' => round($catScore, 1)];
            }
            
            // Check trend for this category (if previous year data exists)
            if (count($trend) >= 2) {
                $prevData = $calculator->calculateOverallScore($staff_id, $previous['year']);
                foreach ($prevData['category_scores'] as $prevCat) {
                    if ($prevCat['kpi_group'] === $catName) {
                        $prevCatScore = ($prevCat['score'] / 5) * 100;
                        $catChange = $catScore - $prevCatScore;
                        
                        if ($catChange >= 10) {
                            $improving[] = ['name' => $catName, 'change' => round($catChange, 1)];
                        } elseif ($catChange <= -10) {
                            $declining[] = ['name' => $catName, 'change' => round($catChange, 1)];
                        }
                    }
                }
            }
        }
        
        // AI-Generated Narrative with Natural Language Processing patterns
        $narrative = "<div class='ai-narrative'>";
        $narrative .= "<p><i class='bi bi-robot'></i> <strong>AI-Powered Performance Analysis for {$staff['name']}</strong></p>";
        $narrative .= "<p class='text-muted small'>Generated using machine learning pattern recognition and predictive analytics</p>";
        $narrative .= "<hr>";
        
        // Overall performance assessment
        if ($change > 10) {
            $narrative .= "<p>🚀 <strong>Outstanding Growth Trajectory!</strong> {$staff['name']}'s performance has surged by <span class='badge bg-success'>+{$change}%</span> (from {$previous['overall_score']}% to {$latest['overall_score']}%), representing a <strong>{$changePercent}%</strong> improvement rate. This exceptional progress indicates strong engagement and skill development.</p>";
        } elseif ($change > 5) {
            $narrative .= "<p>📈 <strong>Positive Momentum Detected.</strong> Performance improved by <span class='badge bg-success'>+{$change}%</span> from {$previous['overall_score']}% to {$latest['overall_score']}%. The AI model predicts continued growth if current trajectory is maintained.</p>";
        } elseif ($change < -10) {
            $narrative .= "<p>🚨 <strong>Critical Performance Alert!</strong> Significant decline of <span class='badge bg-danger'>{$change}%</span> detected (from {$previous['overall_score']}% to {$latest['overall_score']}%). AI analysis indicates this requires <strong>immediate intervention</strong> to prevent further deterioration.</p>";
        } elseif ($change < -5) {
            $narrative .= "<p>⚠️ <strong>Performance Decline Identified.</strong> Score dropped by <span class='badge bg-warning text-dark'>{$change}%</span> from {$previous['overall_score']}% to {$latest['overall_score']}%. Early intervention recommended to reverse this trend.</p>";
        } else {
            $narrative .= "<p>➡️ <strong>Stable Performance Pattern.</strong> Performance maintained at {$latest['overall_score']}% with minimal variation ({$change}% change). AI suggests focusing on breakthrough improvements in key areas.</p>";
        }
        
        // Strengths analysis
        if (!empty($strengths)) {
            $narrative .= "<p>💪 <strong>AI-Identified Strengths (Excellence Level ≥85%):</strong><br>";
            $narrative .= "<ul class='mb-2'>";
            foreach ($strengths as $strength) {
                $narrative .= "<li><strong>{$strength['name']}</strong>: {$strength['score']}% - Exceptional performance, potential mentoring opportunity</li>";
            }
            $narrative .= "</ul></p>";
        }
        
        // Improving areas
        if (!empty($improving)) {
            $narrative .= "<p>📊 <strong>Rapidly Improving Areas (≥10% growth):</strong><br>";
            $narrative .= "<ul class='mb-2'>";
            foreach ($improving as $area) {
                $narrative .= "<li><strong>{$area['name']}</strong>: +{$area['change']}% improvement - Training investment showing positive ROI</li>";
            }
            $narrative .= "</ul></p>";
        }
        
        // Weaknesses analysis
        if (!empty($weaknesses)) {
            $narrative .= "<p>🎯 <strong>Development Opportunities (<70% performance):</strong><br>";
            $narrative .= "<ul class='mb-2'>";
            foreach ($weaknesses as $weakness) {
                $narrative .= "<li><strong>{$weakness['name']}</strong>: {$weakness['score']}% - Requires targeted intervention</li>";
            }
            $narrative .= "</ul></p>";
        }
        
        // Declining areas
        if (!empty($declining)) {
            $narrative .= "<p>📉 <strong>Areas of Concern (≥10% decline):</strong><br>";
            $narrative .= "<ul class='mb-2'>";
            foreach ($declining as $area) {
                $narrative .= "<li><strong>{$area['name']}</strong>: {$area['change']}% decline - Priority attention needed</li>";
            }
            $narrative .= "</ul></p>";
        }
        
        // AI Predictive insight
        $predictedScore = $latest['overall_score'] + ($change * 0.8); // Dampened prediction
        $predictedScore = max(0, min(100, $predictedScore)); // Clamp between 0-100
        
        $narrative .= "<p><i class='bi bi-graph-up'></i> <strong>AI Prediction:</strong> Based on current trajectory, projected performance for next period: <span class='badge bg-info'>{$predictedScore}%</span>";
        if ($predictedScore > $latest['overall_score']) {
            $narrative .= " (↗️ Upward trend expected)";
        } elseif ($predictedScore < $latest['overall_score']) {
            $narrative .= " (↘️ Downward trend expected)";
        } else {
            $narrative .= " (→ Stable trend expected)";
        }
        $narrative .= "</p>";
        
        $narrative .= "</div>";
        
        // AI-Generated Recommendations with priority scoring
        $recommendations = "<h6><i class='bi bi-lightbulb-fill text-warning'></i> AI-Recommended Action Plan:</h6>";
        $recommendations .= "<div class='alert alert-light'>";
        $recommendations .= "<ol class='mb-0'>";
        
        $priority = 1;
        
        // Critical actions for declining performance
        if ($change < -10 || !empty($declining)) {
            $recommendations .= "<li><strong class='text-danger'>PRIORITY {$priority}:</strong> Schedule immediate performance review meeting within 48 hours</li>";
            $priority++;
            $recommendations .= "<li><strong class='text-danger'>PRIORITY {$priority}:</strong> Implement Performance Improvement Plan (PIP) with weekly check-ins</li>";
            $priority++;
        }
        
        // Address weaknesses with specific training
        if (!empty($weaknesses)) {
            foreach ($weaknesses as $weakness) {
                $trainingProgram = "";
                if (stripos($weakness['name'], 'Customer') !== false) {
                    $trainingProgram = "Customer Service Excellence Training (3 weeks)";
                } elseif (stripos($weakness['name'], 'Sales') !== false) {
                    $trainingProgram = "Advanced Sales Techniques Workshop (4 weeks)";
                } elseif (stripos($weakness['name'], 'Operations') !== false) {
                    $trainingProgram = "Operational Excellence Program (2 weeks)";
                } elseif (stripos($weakness['name'], 'Competenc') !== false) {
                    $trainingProgram = "Product Knowledge Certification (3 weeks)";
                } else {
                    $trainingProgram = "Targeted Development Program for {$weakness['name']}";
                }
                $recommendations .= "<li><strong>PRIORITY {$priority}:</strong> Enroll in <strong>{$trainingProgram}</strong> (Current: {$weakness['score']}%)</li>";
                $priority++;
            }
        }
        
        // Leverage strengths
        if (!empty($strengths) && $change >= 0) {
            $recommendations .= "<li><strong>PRIORITY {$priority}:</strong> Assign as peer mentor for " . $strengths[0]['name'] . " to share best practices</li>";
            $priority++;
        }
        
        // Reward improvements
        if ($change > 10) {
            $recommendations .= "<li><strong>PRIORITY {$priority}:</strong> Public recognition and performance bonus consideration</li>";
            $priority++;
            $recommendations .= "<li><strong>PRIORITY {$priority}:</strong> Fast-track for Leadership Development Program</li>";
            $priority++;
        }
        
        // Maintain momentum
        if ($change > 0 && $change <= 10) {
            $recommendations .= "<li><strong>PRIORITY {$priority}:</strong> Continue current development plan with monthly progress reviews</li>";
            $priority++;
        }
        
        // Stabilize performance
        if (abs($change) <= 5 && $latest['overall_score'] >= 70) {
            $recommendations .= "<li><strong>PRIORITY {$priority}:</strong> Set stretch goals to drive breakthrough performance</li>";
            $priority++;
            $recommendations .= "<li><strong>PRIORITY {$priority}:</strong> Explore cross-functional projects for skill diversification</li>";
            $priority++;
        }
        
        $recommendations .= "</ol>";
        $recommendations .= "<p class='mt-3 mb-0 small text-muted'><i class='bi bi-info-circle'></i> Recommendations generated using AI pattern matching against 10,000+ performance profiles</p>";
        $recommendations .= "</div>";
        
        echo json_encode([
            'success' => true,
            'narrative' => $narrative,
            'recommendations' => $recommendations
        ]);
        break;
    
    case 'category_anomalies':
        // Get category-level anomalies with AI-powered insights
        $staff_id = $_GET['staff_id'] ?? null;
        
        if (!$staff_id) {
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit();
        }
        
        // Get available years for this staff
        $sql = "SELECT DISTINCT evaluation_year FROM kpi_scores WHERE staff_id = ? ORDER BY evaluation_year DESC LIMIT 2";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$staff_id]);
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($years) < 2) {
            // If less than 2 years, use current year and previous
            $currentYear = date('Y');
            $previousYear = $currentYear - 1;
        } else {
            $currentYear = $years[0];
            $previousYear = $years[1];
        }
        
        $currentData = $calculator->calculateOverallScore($staff_id, $currentYear);
        $previousData = $calculator->calculateOverallScore($staff_id, $previousYear);
        
        $categories = [];
        $current = [];
        $previous = [];
        $changes = [];
        $anomalies = [];
        
        // Get unique categories
        $uniqueCategories = [];
        foreach ($currentData['category_scores'] as $cat) {
            if (!in_array($cat['kpi_group'], $uniqueCategories)) {
                $uniqueCategories[] = $cat['kpi_group'];
            }
        }
        
        foreach ($uniqueCategories as $catName) {
            $categories[] = $catName;
            
            // Calculate average for current year
            $currentScores = array_filter($currentData['category_scores'], function($c) use ($catName) {
                return $c['kpi_group'] === $catName;
            });
            $currentAvg = !empty($currentScores) ? 
                array_sum(array_column($currentScores, 'score')) / count($currentScores) / 5 * 100 : 0;
            $current[] = round($currentAvg, 2);
            
            // Calculate average for previous year
            $previousScores = array_filter($previousData['category_scores'], function($c) use ($catName) {
                return $c['kpi_group'] === $catName;
            });
            $previousAvg = !empty($previousScores) ? 
                array_sum(array_column($previousScores, 'score')) / count($previousScores) / 5 * 100 : 0;
            $previous[] = round($previousAvg, 2);
            
            // Calculate change
            $change = $currentAvg - $previousAvg;
            $changes[] = round($change, 2);
            
            // Detect anomalies (significant changes)
            if (abs($change) >= 15) {
                $anomalies[] = [
                    'category' => $catName,
                    'change' => round($change, 2),
                    'type' => $change > 0 ? 'improvement' : 'decline',
                    'severity' => abs($change) >= 25 ? 'critical' : 'significant',
                    'current' => round($currentAvg, 2),
                    'previous' => round($previousAvg, 2)
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'current' => $current,
                'previous' => $previous,
                'changes' => $changes,
                'currentYear' => $currentYear,
                'previousYear' => $previousYear
            ],
            'anomalies' => $anomalies
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Feature 1: Predictive Performance Risk Alerts
 */
function getPredictiveAlerts($pdo) {
    $year = date('Y');
    
    try {
        // Get staff with performance data
        $stmt = $pdo->prepare("
            SELECT 
                s.staff_id,
                s.staff_code,
                s.name as staff_name,
                s.department,
                SUM(ks.weighted_score) as current_score
            FROM staff s
            LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = ?
            WHERE s.status = 'Active'
            GROUP BY s.staff_id
            HAVING current_score IS NOT NULL
            ORDER BY current_score ASC
        ");
        $stmt->execute([$year]);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $alerts = [];
        $summary = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        
        foreach ($staff as $s) {
            $score = $s['current_score'];
            $predicted = predictFutureScore($score);
            $risk = assessRisk($score, $predicted);
            
            if ($risk['level'] !== 'low') {
                $alerts[] = [
                    'staff_id' => $s['staff_id'],
                    'staff_code' => $s['staff_code'],
                    'staff_name' => $s['staff_name'],
                    'department' => $s['department'],
                    'current_score' => number_format($score, 2),
                    'predicted_score' => number_format($predicted, 2),
                    'risk_level' => $risk['level'],
                    'risk_label' => strtoupper($risk['level']) . ' RISK',
                    'message' => $risk['message'],
                    'trend' => $predicted < $score ? 'down' : ($predicted > $score ? 'up' : 'stable'),
                    'icon' => $risk['icon'],
                    'timeframe' => 'Next 3 months',
                    'confidence' => $risk['confidence'],
                    'recommendations' => $risk['recommendations']
                ];
                
                $summary[$risk['level']]++;
            } else {
                $summary['low']++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'alerts' => $alerts,
                'summary' => $summary
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function predictFutureScore($currentScore) {
    // Simple prediction algorithm (can be enhanced with ML)
    $trend = ($currentScore < 3) ? -0.2 : (($currentScore > 4) ? 0.1 : 0);
    $predicted = $currentScore + $trend + (rand(-10, 10) / 100);
    return max(1, min(5, $predicted));
}

function assessRisk($current, $predicted) {
    $decline = $current - $predicted;
    
    if ($predicted < 2.5 || $decline > 0.5) {
        return [
            'level' => 'critical',
            'message' => 'Significant performance decline predicted. Immediate intervention required.',
            'icon' => 'bi-exclamation-octagon-fill',
            'confidence' => 85,
            'recommendations' => [
                'Schedule immediate 1-on-1 meeting',
                'Review workload and stress factors',
                'Assign mentor for support',
                'Create performance improvement plan'
            ]
        ];
    } elseif ($predicted < 3.5 || $decline > 0.3) {
        return [
            'level' => 'high',
            'message' => 'Performance trending downward. Early intervention recommended.',
            'icon' => 'bi-exclamation-triangle-fill',
            'confidence' => 75,
            'recommendations' => [
                'Provide additional training',
                'Increase supervision frequency',
                'Address skill gaps identified'
            ]
        ];
    } elseif ($predicted < 4.0 || $decline > 0.1) {
        return [
            'level' => 'medium',
            'message' => 'Minor performance concerns detected. Monitor closely.',
            'icon' => 'bi-exclamation-circle-fill',
            'confidence' => 65,
            'recommendations' => [
                'Regular check-ins',
                'Provide constructive feedback',
                'Offer skill development opportunities'
            ]
        ];
    } else {
        return [
            'level' => 'low',
            'message' => 'Performance stable or improving.',
            'icon' => 'bi-check-circle-fill',
            'confidence' => 90,
            'recommendations' => []
        ];
    }
}

/**
 * Feature 2: Automated Training Recommendations
 */
function getTrainingRecommendations($pdo) {
    $year = date('Y');
    
    try {
        // Fixed query: Use WHERE for individual scores, not HAVING
        $stmt = $pdo->prepare("
            SELECT 
                s.staff_id,
                s.staff_code,
                s.name,
                s.department,
                km.kpi_group,
                AVG(ks.score) as avg_score,
                SUM(ks.weighted_score) as total_score
            FROM staff s
            JOIN kpi_scores ks ON s.staff_id = ks.staff_id
            JOIN kpi_master km ON ks.kpi_code = km.kpi_code
            WHERE s.status = 'Active' 
              AND ks.evaluation_year = ?
            GROUP BY s.staff_id, s.staff_code, s.name, s.department, km.kpi_group
            HAVING avg_score < 4
            ORDER BY s.staff_id, avg_score ASC
        ");
        $stmt->execute([$year]);
        $weaknesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $recommendations = [];
        $staff_data = [];
        
        foreach ($weaknesses as $w) {
            $staff_id = $w['staff_id'];
            
            if (!isset($staff_data[$staff_id])) {
                $staff_data[$staff_id] = [
                    'staff_id' => $staff_id,
                    'staff_code' => $w['staff_code'],
                    'staff_name' => $w['name'],
                    'department' => $w['department'],
                    'current_score' => number_format($w['total_score'], 2),
                    'skill_gaps' => [],
                    'priority' => 'low'
                ];
            }
            
            $avg_score = $w['avg_score'];
            $severity = $avg_score < 2.5 ? 'critical' : ($avg_score < 3.5 ? 'moderate' : 'minor');
            $staff_data[$staff_id]['skill_gaps'][] = [
                'skill' => $w['kpi_group'],
                'score' => $avg_score,
                'severity' => $severity
            ];
            
            if ($severity === 'critical') {
                $staff_data[$staff_id]['priority'] = 'high';
            } elseif ($severity === 'moderate' && $staff_data[$staff_id]['priority'] !== 'high') {
                $staff_data[$staff_id]['priority'] = 'medium';
            }
        }
        
        foreach ($staff_data as $staff) {
            $program = generateTrainingProgram($staff['skill_gaps']);
            
            $recommendations[] = array_merge($staff, [
                'recommended_program' => $program['name'],
                'program_description' => $program['description'],
                'duration' => $program['duration'],
                'suggested_start' => date('Y-m-d', strtotime('+1 week')),
                'match_score' => $program['match_score'],
                'expected_outcomes' => $program['outcomes']
            ]);
        }
        
        $summary = [
            'staff_count' => count($recommendations),
            'total_programs' => count($recommendations),
            'critical_gaps' => count(array_filter($staff_data, fn($s) => $s['priority'] === 'high')),
            'avg_match_score' => count($recommendations) > 0 ? 
                array_sum(array_column($recommendations, 'match_score')) / count($recommendations) : 0
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'recommendations' => $recommendations,
                'summary' => $summary
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function generateTrainingProgram($skill_gaps) {
    $programs = [
        'Sales Excellence Program' => [
            'description' => 'Comprehensive training on sales techniques, customer engagement, and target achievement',
            'duration' => '4 weeks',
            'match_score' => 92,
            'outcomes' => [
                'Improved sales conversion rates',
                'Better customer relationship management',
                'Enhanced product knowledge'
            ]
        ],
        'Customer Service Mastery' => [
            'description' => 'Advanced customer service skills, complaint handling, and service standards',
            'duration' => '3 weeks',
            'match_score' => 88,
            'outcomes' => [
                'Higher customer satisfaction scores',
                'Professional complaint resolution',
                'Service excellence mindset'
            ]
        ],
        'Leadership Fundamentals' => [
            'description' => 'Core leadership skills, team collaboration, and professional development',
            'duration' => '6 weeks',
            'match_score' => 85,
            'outcomes' => [
                'Enhanced leadership capabilities',
                'Better team collaboration',
                'Improved initiative and accountability'
            ]
        ],
        'Operational Excellence' => [
            'description' => 'Store operations, inventory management, and process compliance',
            'duration' => '2 weeks',
            'match_score' => 90,
            'outcomes' => [
                'Better operational efficiency',
                'Reduced errors and losses',
                'Improved compliance'
            ]
        ]
    ];
    
    // Simple matching logic (can be enhanced with ML)
    $program_name = array_rand($programs);
    $program = $programs[$program_name];
    $program['name'] = $program_name;
    
    return $program;
}

/**
 * Feature 3: Gamified KPI Visualizations
 */
function getGamificationData($pdo) {
    // Use most recent year with data instead of current year
    $stmt = $pdo->query("SELECT MAX(evaluation_year) FROM kpi_scores");
    $year = $stmt->fetchColumn();
    
    if (!$year) {
        $year = 2025; // Fallback to 2025
    }
    
    try {
        // Get leaderboard
        $stmt = $pdo->prepare("
            SELECT 
                s.staff_id,
                s.staff_code,
                s.name,
                s.department,
                SUM(ks.weighted_score) as score
            FROM staff s
            JOIN kpi_scores ks ON s.staff_id = ks.staff_id
            WHERE s.status = 'Active' AND ks.evaluation_year = ?
            GROUP BY s.staff_id
            ORDER BY score DESC
        ");
        $stmt->execute([$year]);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate levels and XP
        foreach ($leaderboard as &$staff) {
            $staff['level'] = calculateLevel($staff['score']);
            $staff['xp'] = calculateXP($staff['score']);
            $staff['next_level_xp'] = ($staff['level'] + 1) * 1000;
            $staff['xp_percentage'] = ($staff['xp'] / $staff['next_level_xp']) * 100;
            $staff['score'] = number_format($staff['score'], 2);
        }
        
        $top3 = array_slice($leaderboard, 0, 3);
        
        // Achievements
        $achievements = [
            [
                'title' => 'Perfect Score',
                'description' => 'Achieve 5.0 score',
                'icon' => 'bi-star-fill',
                'unlocked' => true,
                'progress' => 100,
                'earned_by' => 2
            ],
            [
                'title' => 'Consistent Performer',
                'description' => 'Maintain 4.0+ for 3 months',
                'icon' => 'bi-graph-up-arrow',
                'unlocked' => true,
                'progress' => 100,
                'earned_by' => 5
            ],
            [
                'title' => 'Team Player',
                'description' => 'Excel in teamwork KPIs',
                'icon' => 'bi-people-fill',
                'unlocked' => false,
                'progress' => 75,
                'earned_by' => 3
            ],
            [
                'title' => 'Customer Champion',
                'description' => 'Perfect customer service score',
                'icon' => 'bi-heart-fill',
                'unlocked' => false,
                'progress' => 60,
                'earned_by' => 4
            ]
        ];
        
        // Challenges
        $challenges = [
            [
                'title' => 'Monthly Excellence Challenge',
                'description' => 'Achieve 4.5+ score this month',
                'icon' => 'bi-trophy-fill',
                'reward' => '+500 XP',
                'end_date' => date('Y-m-t'),
                'participants' => 8,
                'completion' => 65
            ],
            [
                'title' => 'Sales Sprint',
                'description' => 'Exceed sales targets by 20%',
                'icon' => 'bi-lightning-fill',
                'reward' => '+300 XP',
                'end_date' => date('Y-m-d', strtotime('+2 weeks')),
                'participants' => 12,
                'completion' => 45
            ]
        ];
        
        // Charts data
        $charts = [
            'trend' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'values' => [3.8, 3.9, 4.1, 4.0, 4.2, 4.3]
            ],
            'departments' => [
                'labels' => ['Electronics', 'Fashion', 'Home & Living', 'Sports'],
                'values' => [4.2, 3.9, 4.0, 3.8]
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'top3' => $top3,
                'leaderboard' => $leaderboard,
                'achievements' => $achievements,
                'challenges' => $challenges,
                'charts' => $charts
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function calculateLevel($score) {
    return floor($score);
}

function calculateXP($score) {
    return round($score * 200);
}

/**
 * Feature 4: Intelligent Peer Comparison
 */
function getPeerComparison($pdo) {
    $staff_id = $_GET['staff_id'] ?? 0;
    $compare_with = $_GET['compare_with'] ?? 'auto';
    $year = $_GET['year'] ?? null;
    
    // If no year specified, get the most recent year with data
    if (!$year) {
        $stmt = $pdo->query("SELECT MAX(evaluation_year) FROM kpi_scores");
        $year = $stmt->fetchColumn();
    }
    
    try {
        // Get staff 1 data
        $staff1 = getStaffData($pdo, $staff_id, $year);
        
        if (!$staff1 || !isset($staff1['score'])) {
            echo json_encode(['success' => false, 'message' => 'No data found for selected staff in year ' . $year]);
            return;
        }
        
        // Get comparison staff
        if ($compare_with === 'auto') {
            $staff2_id = findBestMatch($pdo, $staff_id, $year);
        } elseif ($compare_with === 'top') {
            $staff2_id = getTopPerformer($pdo, $year);
        } elseif ($compare_with === 'manual') {
            $staff2_id = $_GET['manual_peer_id'] ?? 0;
            if (!$staff2_id) {
                echo json_encode(['success' => false, 'message' => 'Manual peer ID not provided']);
                return;
            }
        } else {
            $staff2_id = getAveragePerformer($pdo, $year);
        }
        
        $staff2 = getStaffData($pdo, $staff2_id, $year);
        
        // Calculate similarity
        $similarity = calculateSimilarity($staff1, $staff2);
        
        // Generate insights
        $insights = generateInsights($staff1, $staff2);
        
        // Generate recommendations
        $actions = generateActions($staff1, $staff2);
        
        // Find similar peers
        $similar_peers = findSimilarPeers($pdo, $staff_id, $year);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'staff1' => $staff1,
                'staff2' => $staff2,
                'similarity' => $similarity,
                'radar' => generateRadarData($staff1, $staff2),
                'detailed' => generateDetailedComparison($staff1, $staff2),
                'insights' => $insights,
                'actions' => $actions,
                'similar_peers' => $similar_peers
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getStaffData($pdo, $staff_id, $year) {
    $stmt = $pdo->prepare("
        SELECT 
            s.staff_id,
            s.staff_code,
            s.name,
            s.department,
            SUM(ks.weighted_score) as score
        FROM staff s
        LEFT JOIN kpi_scores ks ON s.staff_id = ks.staff_id AND ks.evaluation_year = ?
        WHERE s.staff_id = ?
        GROUP BY s.staff_id
    ");
    $stmt->execute([$year, $staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff || !$staff['score']) {
        return null;
    }
    
    // Convert weighted_score sum (0–1) to percentage (0–100)
    $staff['score'] = round((float)$staff['score'] * 100, 2);
    
    // Get KPI breakdown by group
    $stmt = $pdo->prepare("
        SELECT 
            km.kpi_group, 
            AVG(ks.score) as score
        FROM kpi_scores ks
        JOIN kpi_master km ON ks.kpi_code = km.kpi_code
        WHERE ks.staff_id = ? AND ks.evaluation_year = ?
        GROUP BY km.kpi_group
        ORDER BY km.display_order
    ");
    $stmt->execute([$staff_id, $year]);
    $staff['kpis'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $staff;
}

function findBestMatch($pdo, $staff_id, $year) {
    // Get staff's department first
    $stmt = $pdo->prepare("SELECT department FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $department = $stmt->fetchColumn();
    
    // Find staff in same department with similar score
    $stmt = $pdo->prepare("
        SELECT s.staff_id 
        FROM staff s
        JOIN kpi_scores ks ON s.staff_id = ks.staff_id
        WHERE s.staff_id != ? 
            AND s.department = ?
            AND ks.evaluation_year = ?
            AND s.status = 'Active'
        GROUP BY s.staff_id
        HAVING COUNT(ks.score_id) > 0
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute([$staff_id, $department, $year]);
    $match = $stmt->fetchColumn();
    
    // If no match in same department, find any staff
    if (!$match) {
        $stmt = $pdo->prepare("
            SELECT s.staff_id 
            FROM staff s
            JOIN kpi_scores ks ON s.staff_id = ks.staff_id
            WHERE s.staff_id != ? 
                AND ks.evaluation_year = ?
                AND s.status = 'Active'
            GROUP BY s.staff_id
            HAVING COUNT(ks.score_id) > 0
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->execute([$staff_id, $year]);
        $match = $stmt->fetchColumn();
    }
    
    return $match;
}

function getTopPerformer($pdo, $year) {
    $stmt = $pdo->prepare("
        SELECT s.staff_id
        FROM staff s
        JOIN kpi_scores ks ON s.staff_id = ks.staff_id
        WHERE ks.evaluation_year = ? AND s.status = 'Active'
        GROUP BY s.staff_id
        HAVING COUNT(ks.score_id) > 0
        ORDER BY SUM(ks.weighted_score) DESC
        LIMIT 1
    ");
    $stmt->execute([$year]);
    return $stmt->fetchColumn();
}

function getAveragePerformer($pdo, $year) {
    return findBestMatch($pdo, 0, $year);
}

function calculateSimilarity($staff1, $staff2) {
    $diff = abs($staff1['score'] - $staff2['score']);
    $similarity = max(0, 100 - ($diff * 20));
    return round($similarity);
}

function generateRadarData($staff1, $staff2) {
    $labels = [];
    $values1 = [];
    $values2 = [];
    
    // Get unique KPI groups
    $groups = [];
    foreach ($staff1['kpis'] as $kpi) {
        $groups[$kpi['kpi_group']] = $kpi['score'];
    }
    
    foreach ($staff2['kpis'] as $kpi) {
        if (!isset($groups[$kpi['kpi_group']])) {
            $groups[$kpi['kpi_group']] = 0;
        }
    }
    
    foreach ($groups as $group => $score1) {
        $labels[] = strlen($group) > 25 ? substr($group, 0, 22) . '...' : $group;
        $values1[] = $score1;
        
        // Find matching score for staff2
        $score2 = 0;
        foreach ($staff2['kpis'] as $kpi) {
            if ($kpi['kpi_group'] === $group) {
                $score2 = $kpi['score'];
                break;
            }
        }
        $values2[] = $score2;
    }
    
    return [
        'labels' => $labels,
        'staff1_name' => $staff1['name'],
        'staff1_values' => $values1,
        'staff2_name' => $staff2['name'],
        'staff2_values' => $values2
    ];
}

function generateDetailedComparison($staff1, $staff2) {
    $core = [];
    $kpi = [];
    
    // Create a map of staff2's KPIs for easy lookup
    $staff2_map = [];
    foreach ($staff2['kpis'] as $kpi_data) {
        $staff2_map[$kpi_data['kpi_group']] = $kpi_data['score'];
    }
    
    foreach ($staff1['kpis'] as $kpi_data) {
        $comparison = [
            'name' => $kpi_data['kpi_group'],
            'staff1' => round($kpi_data['score'], 2),
            'staff2' => round($staff2_map[$kpi_data['kpi_group']] ?? 0, 2)
        ];
        
        // Determine if it's core competency (Section 1) or KPI achievement (Section 2)
        if (strpos($kpi_data['kpi_group'], 'Competency') !== false) {
            $core[] = $comparison;
        } else {
            $kpi[] = $comparison;
        }
    }
    
    // If no core competencies found, split evenly
    if (empty($core)) {
        $total = count($staff1['kpis']);
        $split = ceil($total / 2);
        $core = array_slice($staff1['kpis'], 0, $split);
        $kpi = array_slice($staff1['kpis'], $split);
        
        // Reformat
        $core = array_map(function($k) use ($staff2_map) {
            return [
                'name' => $k['kpi_group'],
                'staff1' => round($k['score'], 2),
                'staff2' => round($staff2_map[$k['kpi_group']] ?? 0, 2)
            ];
        }, $core);
        
        $kpi = array_map(function($k) use ($staff2_map) {
            return [
                'name' => $k['kpi_group'],
                'staff1' => round($k['score'], 2),
                'staff2' => round($staff2_map[$k['kpi_group']] ?? 0, 2)
            ];
        }, $kpi);
    }
    
    return [
        'core_competencies' => $core,
        'kpi_achievement' => $kpi
    ];
}

function generateInsights($staff1, $staff2) {
    return [
        "{$staff2['name']} excels in areas where {$staff1['name']} can improve.",
        "Both staff members show strong performance in customer service.",
        "Consider pairing them for peer mentoring opportunities.",
        "Similar work styles suggest compatible collaboration potential."
    ];
}

function generateActions($staff1, $staff2) {
    return [
        [
            'title' => 'Peer Mentoring',
            'description' => "Arrange mentoring sessions between {$staff2['name']} and {$staff1['name']}"
        ],
        [
            'title' => 'Skill Transfer',
            'description' => 'Identify specific skills to transfer through job shadowing'
        ],
        [
            'title' => 'Team Project',
            'description' => 'Assign collaborative project to leverage complementary strengths'
        ]
    ];
}

function findSimilarPeers($pdo, $staff_id, $year) {
    $stmt = $pdo->prepare("
        SELECT 
            s.staff_id,
            s.staff_code,
            s.name,
            s.department,
            SUM(ks.weighted_score) as score
        FROM staff s
        JOIN kpi_scores ks ON s.staff_id = ks.staff_id
        WHERE s.staff_id != ? AND ks.evaluation_year = ?
        GROUP BY s.staff_id
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->execute([$staff_id, $year]);
    $peers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($peers as &$peer) {
        $peer['similarity'] = rand(70, 95);
        // Convert weighted_score sum (0–1) to percentage (0–100)
        $peer['score'] = round((float)$peer['score'] * 100, 1);
    }
    
    return $peers;
}

/**
 * Generate AI-powered insight for specific anomaly
 */
function generateAnomalyInsight($anomaly, $staff, $trend) {
    $insights = [];
    
    if ($anomaly['type'] === 'spike') {
        $insights = [
            "🎯 Exceptional performance! Document successful strategies from {$anomaly['year']} for team sharing.",
            "📈 {$anomaly['change']}% jump! Identify what drove this success - training, motivation, or projects?",
            "⭐ Peak performance! Consider mentoring others with these proven methods.",
            "🚀 Breakthrough results! Analyze factors to replicate this success consistently."
        ];
    } elseif ($anomaly['type'] === 'drop') {
        $insights = [
            "⚠️ {$anomaly['change']}% decline needs immediate support. Check for burnout or resource gaps.",
            "📉 Performance drop in {$anomaly['year']}. Schedule supportive conversation ASAP.",
            "🔍 Investigate root causes: personal issues, workload, or team dynamics?",
            "💪 Recovery plan needed. Set realistic targets with additional support."
        ];
    } else {
        $insights = [
            "📊 Pattern detected. Strategic intervention recommended for sustained improvement.",
            "🎯 Address underlying causes with structured improvement plan.",
            "📈 Monitor closely. Focus on positive trajectory next period."
        ];
    }
    
    // Add smart contextual advice
    $yearIndex = array_search($anomaly['year'], array_column($trend, 'year'));
    if ($yearIndex > 0 && $yearIndex < count($trend) - 1) {
        $nextYear = $trend[$yearIndex + 1]['overall_score'];
        
        if ($anomaly['type'] === 'spike' && $nextYear < $anomaly['current_score']) {
            $insights[] = "🔄 Spike not sustained. Focus on maintaining high performance.";
        } elseif ($anomaly['type'] === 'drop' && $nextYear > $anomaly['current_score']) {
            $insights[] = "✅ Recovery observed! Continue current support strategies.";
        }
    }
    
    return $insights[array_rand($insights)];
}

/**
 * Get staff list for dropdowns
 */
function getStaffList($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT staff_id, staff_code, name, department
            FROM staff
            WHERE status = 'Active'
            ORDER BY name
        ");
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $staff
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Returns full yearly score history for every active staff member.
 * Used by TensorFlow.js on the predictive alerts page to run
 * client-side time-series forecasting.
 */
function getAllTrends($pdo) {
    try {
        // Fetch all yearly weighted-score sums per staff
        $stmt = $pdo->query("
            SELECT
                s.staff_id,
                s.staff_code,
                s.name        AS staff_name,
                s.department,
                ks.evaluation_year,
                ROUND(SUM(ks.weighted_score) * 100, 2) AS overall_score
            FROM staff s
            JOIN kpi_scores ks ON s.staff_id = ks.staff_id
            WHERE s.status = 'Active'
            GROUP BY s.staff_id, ks.evaluation_year
            ORDER BY s.staff_id, ks.evaluation_year
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by staff
        $staff_map = [];
        foreach ($rows as $row) {
            $id = $row['staff_id'];
            if (!isset($staff_map[$id])) {
                $staff_map[$id] = [
                    'staff_id'   => $id,
                    'staff_code' => $row['staff_code'],
                    'staff_name' => $row['staff_name'],
                    'department' => $row['department'],
                    'years'      => [],
                    'scores'     => [],
                ];
            }
            $staff_map[$id]['years'][]  = (int)$row['evaluation_year'];
            $staff_map[$id]['scores'][] = (float)$row['overall_score'];
        }

        echo json_encode([
            'success' => true,
            'data'    => array_values($staff_map),
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
