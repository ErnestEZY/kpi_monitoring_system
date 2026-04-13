<?php
/**
 * KPI Calculator Class
 * Handles all KPI calculations including weighted scores, trends, and classifications
 */
class KPICalculator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate weighted overall KPI score for a staff member in a specific year
     * @param int $staff_id
     * @param int $year
     * @return array ['overall_score' => float, 'category_scores' => array]
     */
    public function calculateOverallScore($staff_id, $year) {
        // Always recalculate from raw score + weight to avoid stale weighted_score values
        $sql = "SELECT 
                    km.kpi_code,
                    km.kpi_group,
                    km.weight_percentage,
                    COALESCE(ks.score, 0) as score
                FROM kpi_master km
                LEFT JOIN kpi_scores ks ON km.kpi_code = ks.kpi_code 
                    AND ks.staff_id = ? AND ks.evaluation_year = ?
                ORDER BY km.display_order";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staff_id, $year]);
        $kpis = $stmt->fetchAll();
        
        if (empty($kpis)) {
            return [
                'overall_score' => 0,
                'category_scores' => [],
                'has_data' => false
            ];
        }
        
        $total_weighted_score = 0;
        $has_any_data = false;
        $category_scores = [];
        
        foreach ($kpis as $kpi) {
            $score  = (float)$kpi['score'];
            $weight = (float)$kpi['weight_percentage'];
            
            // Correct formula: (score / 5) * (weight / 100)
            // score is 1–5 scale; weight sums to 100 across all 21 KPIs
            // Result per KPI is in range 0–0.2 (for a 10% weight KPI)
            // Sum of all 21 = 0.0 – 1.0 → multiply by 100 for percentage
            $weighted_score = ($score / 5) * ($weight / 100);
            
            if ($score > 0) {
                $has_any_data = true;
                $total_weighted_score += $weighted_score;
            }
            
            $category_scores[] = [
                'kpi_code'      => $kpi['kpi_code'],
                'kpi_group'     => $kpi['kpi_group'],
                'category_name' => $kpi['kpi_group'],
                'score'         => $score,
                'weight'        => $weight,
                'weighted_score'=> $weighted_score,
            ];
        }
        
        // overall_score % = SUM(weighted_scores) * 100
        $overall_score = $total_weighted_score * 100;
        
        return [
            'overall_score'   => round($overall_score, 2),
            'category_scores' => $category_scores,
            'has_data'        => $has_any_data,
        ];
    }
    
    /**
     * Get performance trend for a staff member across all recorded years.
     *
     * @param  int   $staff_id
     * @return array Each element: ['year', 'overall_score', 'change', 'change_percentage', 'trend']
     */
    public function getPerformanceTrend(int $staff_id): array {
        $sql = "SELECT DISTINCT evaluation_year
                FROM kpi_scores
                WHERE staff_id = ?
                ORDER BY evaluation_year";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staff_id]);
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $trend_data     = [];
        $previous_score = null;

        foreach ($years as $year) {
            $result        = $this->calculateOverallScore($staff_id, $year);
            $current_score = $result['overall_score'];

            $change            = null;
            $change_percentage = null;

            if ($previous_score !== null && $previous_score > 0) {
                $change            = $current_score - $previous_score;
                $change_percentage = round(($change / $previous_score) * 100, 2);
            }

            $trend_data[] = [
                'year'              => $year,
                'overall_score'     => $current_score,
                'change'            => $change,
                'change_percentage' => $change_percentage,
                'trend'             => $this->determineTrend($change),
            ];

            $previous_score = $current_score;
        }

        return $trend_data;
    }
    
    /**
     * Determine trend direction
     */
    private function determineTrend($change) {
        if ($change === null) return 'new';
        if ($change > 2) return 'improving';
        if ($change < -2) return 'declining';
        return 'stable';
    }
    
    /**
     * Classify performance level
     * @param float $score
     * @return string
     */
    public function classifyPerformance($score) {
        if ($score >= 85) return 'Top Performer';
        if ($score >= 75) return 'Good Performer';
        if ($score >= 65) return 'Satisfactory';
        if ($score >= 50) return 'Needs Improvement';
        return 'Critical';
    }
    
    /**
     * Get performance classification with color coding
     */
    public function getPerformanceClassification($score) {
        $classifications = [
            'Top Performer' => ['min' => 85, 'color' => '#28a745', 'badge' => 'success'],
            'Good Performer' => ['min' => 75, 'color' => '#17a2b8', 'badge' => 'info'],
            'Satisfactory' => ['min' => 65, 'color' => '#ffc107', 'badge' => 'warning'],
            'Needs Improvement' => ['min' => 50, 'color' => '#fd7e14', 'badge' => 'warning'],
            'Critical' => ['min' => 0, 'color' => '#dc3545', 'badge' => 'danger']
        ];
        
        foreach ($classifications as $label => $config) {
            if ($score >= $config['min']) {
                return [
                    'label' => $label,
                    'color' => $config['color'],
                    'badge' => $config['badge'],
                    'score' => $score
                ];
            }
        }
        
        return [
            'label' => 'No Data',
            'color' => '#6c757d',
            'badge' => 'secondary',
            'score' => 0
        ];
    }
    
    /**
     * Identify at-risk staff members for a specific year.
     * Criteria:
     *   - Score < 60 in the selected year → High risk
     *   - Score < 70 in both the selected year AND the year before → Medium risk
     *
     * @param  int|null $current_year  The year to evaluate (defaults to current year)
     * @return array
     */
    public function getAtRiskStaff(?int $current_year = null): array {
        $current_year = $current_year ?? (int) date('Y');
        $prev_year    = $current_year - 1;

        $sql = "SELECT DISTINCT s.staff_id, s.name as full_name, s.staff_code as staff_number
                FROM staff s
                WHERE s.status = 'Active'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $staff_list = $stmt->fetchAll();

        $at_risk = [];

        foreach ($staff_list as $staff) {
            // Score for the selected year only
            $current_data = $this->calculateOverallScore($staff['staff_id'], $current_year);

            if (!$current_data['has_data']) continue;

            $current_score = $current_data['overall_score'];

            // Critically low in the selected year
            if ($current_score < 60) {
                $at_risk[] = [
                    'staff_id'      => $staff['staff_id'],
                    'staff_number'  => $staff['staff_number'],
                    'full_name'     => $staff['full_name'],
                    'current_score' => $current_score,
                    'risk_level'    => 'High',
                    'reason'        => 'Critical performance level (below 60%)',
                ];
                continue;
            }

            // Below 70 in both selected year and the year before
            if ($current_score < 70) {
                $prev_data = $this->calculateOverallScore($staff['staff_id'], $prev_year);

                if ($prev_data['has_data'] && $prev_data['overall_score'] < 70) {
                    $at_risk[] = [
                        'staff_id'      => $staff['staff_id'],
                        'staff_number'  => $staff['staff_number'],
                        'full_name'     => $staff['full_name'],
                        'current_score' => $current_score,
                        'risk_level'    => 'Medium',
                        'reason'        => "Below 70% for two consecutive years ($prev_year & $current_year)",
                    ];
                }
            }
        }

        return $at_risk;
    }

    /**
     * Get top performers
     */
    public function getTopPerformers($year, $threshold = 85) {
        $sql = "SELECT DISTINCT s.staff_id, s.name as full_name, s.staff_code as staff_number, s.department as department_name
                FROM staff s
                WHERE s.status = 'Active'";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $staff_list = $stmt->fetchAll();
        
        $top_performers = [];
        
        foreach ($staff_list as $staff) {
            $result = $this->calculateOverallScore($staff['staff_id'], $year);
            
            if ($result['overall_score'] >= $threshold) {
                $top_performers[] = [
                    'staff_id' => $staff['staff_id'],
                    'staff_number' => $staff['staff_number'],
                    'full_name' => $staff['full_name'],
                    'department' => $staff['department_name'],
                    'overall_score' => $result['overall_score']
                ];
            }
        }
        
        // Sort by score descending
        usort($top_performers, function($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        return $top_performers;
    }
    
    /**
     * Calculate category-wise average for comparison
     */
    public function getCategoryAverages($year) {
        $sql = "SELECT 
                    km.kpi_group as category_name,
                    AVG(ks.score) as avg_score,
                    MIN(ks.score) as min_score,
                    MAX(ks.score) as max_score,
                    COUNT(ks.score) as count
                FROM kpi_master km
                LEFT JOIN kpi_scores ks ON km.kpi_code = ks.kpi_code 
                    AND ks.evaluation_year = ?
                GROUP BY km.kpi_group
                ORDER BY km.display_order";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$year]);
        
        $averages = [];
        foreach ($stmt->fetchAll() as $row) {
            $averages[] = [
                'category' => $row['category_name'],
                'average' => round($row['avg_score'] * 20, 2), // Convert 1-5 scale to 0-100
                'min' => round($row['min_score'] * 20, 2),
                'max' => round($row['max_score'] * 20, 2),
                'count' => $row['count']
            ];
        }
        
        return $averages;
    }
    
    /**
     * Generate narrative insights for a staff member
     */
    public function generateNarrative($staff_id) {
        $trend = $this->getPerformanceTrend($staff_id);
        
        if (empty($trend)) {
            return "No performance data available for analysis.";
        }
        
        $narrative = [];
        $latest = end($trend);
        $current_year = $latest['year'];
        $current_score = $latest['overall_score'];
        
        // Overall performance assessment
        $classification = $this->classifyPerformance($current_score);
        $narrative[] = "Current Performance ({$current_year}): {$classification} with an overall score of {$current_score}%.";
        
        // Trend analysis (safe index access — no prev() pointer issues)
        if (count($trend) > 1) {
            $previous = $trend[count($trend) - 2];
            $change   = $current_score - $previous['overall_score'];
            
            if ($change > 5) {
                $narrative[] = "Shows significant improvement of " . round($change, 1) . " points compared to {$previous['year']}.";
            } elseif ($change < -5) {
                $narrative[] = "Performance declined by " . round(abs($change), 1) . " points compared to {$previous['year']}, requiring attention.";
            } else {
                $narrative[] = "Maintains consistent performance with minimal variation from previous year.";
            }
        }
        
        // Multi-year trend
        if (count($trend) >= 3) {
            $first_score = $trend[0]['overall_score'];
            $overall_change = $current_score - $first_score;
            
            if ($overall_change > 10) {
                $narrative[] = "Demonstrates strong long-term growth trajectory with " . round($overall_change, 1) . " points improvement since {$trend[0]['year']}.";
            } elseif ($overall_change < -10) {
                $narrative[] = "Long-term performance trend shows concerning decline of " . round(abs($overall_change), 1) . " points since {$trend[0]['year']}.";
            }
        }
        
        // Category analysis
        $latest_calc = $this->calculateOverallScore($staff_id, $current_year);
        $strengths = [];
        $weaknesses = [];
        
        foreach ($latest_calc['category_scores'] as $cat) {
            if ($cat['score'] >= 85) {
                $strengths[] = $cat['category_name'];
            } elseif ($cat['score'] < 70 && $cat['score'] > 0) {
                $weaknesses[] = $cat['category_name'];
            }
        }
        
        if (!empty($strengths)) {
            $narrative[] = "Key strengths: " . implode(", ", $strengths) . ".";
        }
        
        if (!empty($weaknesses)) {
            $narrative[] = "Areas requiring development: " . implode(", ", $weaknesses) . ".";
        }
        
        // Recommendation
        if ($current_score < 65) {
            $narrative[] = "Immediate intervention recommended through targeted training and close supervision.";
        } elseif ($current_score >= 85) {
            $narrative[] = "Excellent candidate for leadership development and mentoring opportunities.";
        }
        
        return implode(" ", $narrative);
    }
    
    /**
     * Compare staff performance against team average
     */
    public function compareToTeamAverage(int $staff_id, int $year): array {
        $staff_result = $this->calculateOverallScore($staff_id, $year);
        $staff_score  = $staff_result['overall_score'];

        // Recalculate team average from raw scores (not stored weighted_score)
        $sql = "SELECT
                    ks.staff_id,
                    SUM((ks.score / 5) * (km.weight_percentage / 100)) AS correct_weighted
                FROM kpi_scores ks
                JOIN kpi_master km ON ks.kpi_code = km.kpi_code
                WHERE ks.evaluation_year = ?
                GROUP BY ks.staff_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$year]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [
                'staff_score'         => $staff_score,
                'team_average'        => 0,
                'difference'          => 0,
                'performance_vs_team' => 'No Data',
            ];
        }

        $team_avg = round(
            (array_sum(array_column($rows, 'correct_weighted')) / count($rows)) * 100,
            2
        );

        $difference = round($staff_score - $team_avg, 2);

        return [
            'staff_score'         => $staff_score,
            'team_average'        => $team_avg,
            'difference'          => $difference,
            'performance_vs_team' => $difference > 0 ? 'Above Average'
                                   : ($difference < 0 ? 'Below Average' : 'Average'),
        ];
    }

    /**
     * Predict future performance using linear regression
     * @param int $staff_id Staff member ID
     * @return array Prediction data with confidence score
     */
    public function predictPerformance($staff_id) {
        $trend = $this->getPerformanceTrend($staff_id);

        if (count($trend) < 3) {
            return [
                'success' => false,
                'message' => 'Insufficient data for prediction (minimum 3 years required)'
            ];
        }

        // Prepare data for linear regression
        $n = count($trend);
        $x = []; // Years as numeric values (0, 1, 2, ...)
        $y = []; // Scores

        foreach ($trend as $index => $data) {
            $x[] = $index;
            $y[] = $data['overall_score'];
        }

        // Calculate linear regression: y = mx + b
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        // Calculate slope (m) and intercept (b)
        $m = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $b = ($sumY - $m * $sumX) / $n;

        // Predict next year (n)
        $predictedScore = $m * $n + $b;
        $predictedScore = max(0, min(100, $predictedScore)); // Clamp between 0-100

        // Calculate confidence based on R-squared
        $meanY = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;

        for ($i = 0; $i < $n; $i++) {
            $predicted = $m * $x[$i] + $b;
            $ssTotal += pow($y[$i] - $meanY, 2);
            $ssResidual += pow($y[$i] - $predicted, 2);
        }

        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;
        $confidence = max(65, min(95, $rSquared * 100)); // Scale to 65-95%

        // Determine risk level
        $currentScore = $trend[$n - 1]['overall_score'];
        $change = $predictedScore - $currentScore;

        $riskLevel = 'Low';
        $riskColor = 'info';

        if ($predictedScore < 50 || $change < -10) {
            $riskLevel = 'Critical';
            $riskColor = 'danger';
        } elseif ($predictedScore < 70 || $change < -5) {
            $riskLevel = 'High';
            $riskColor = 'warning';
        } elseif ($predictedScore < 85 || $change < -2) {
            $riskLevel = 'Medium';
            $riskColor = 'warning';
        }

        // Determine trend direction
        $trendDirection = $m > 0.5 ? 'improving' : ($m < -0.5 ? 'declining' : 'stable');

        return [
            'success' => true,
            'current_score' => round($currentScore, 2),
            'predicted_score' => round($predictedScore, 2),
            'change' => round($change, 2),
            'confidence' => round($confidence, 2),
            'risk_level' => $riskLevel,
            'risk_color' => $riskColor,
            'trend_direction' => $trendDirection,
            'slope' => round($m, 4),
            'data_points' => $n,
            'r_squared' => round($rSquared, 4)
        ];
    }
    
    /**
     * Smart training recommendation with compatibility scoring
     * @param int $staff_id Staff member ID
     * @param int $year Evaluation year
     * @return array Training recommendations with match scores
     */
    public function getSmartTrainingRecommendations($staff_id, $year) {
        // Get staff performance data
        $data = $this->calculateOverallScore($staff_id, $year);
        
        if (!$data['has_data']) {
            return [
                'success' => false,
                'message' => 'No performance data available'
            ];
        }
        
        // Define training programs with their focus areas
        $trainingPrograms = [
            [
                'id' => 1,
                'name' => 'Sales Excellence Program',
                'duration' => '4 weeks',
                'focus_areas' => ['Daily Sales Operations', 'Sales Target Contribution'],
                'description' => 'Comprehensive sales training covering target achievement, transaction value optimization, and product attachment strategies.',
                'outcomes' => ['Increase sales by 20%', 'Improve closing rate', 'Master upselling techniques'],
                'prerequisites' => 'None',
                'difficulty' => 'Intermediate'
            ],
            [
                'id' => 2,
                'name' => 'Customer Service Mastery',
                'duration' => '3 weeks',
                'focus_areas' => ['Customer Service Quality'],
                'description' => 'Advanced customer service training focusing on satisfaction, complaint resolution, and service recovery.',
                'outcomes' => ['Improve CSAT scores', 'Reduce complaint resolution time', 'Master service recovery'],
                'prerequisites' => 'None',
                'difficulty' => 'Beginner'
            ],
            [
                'id' => 3,
                'name' => 'Leadership Fundamentals',
                'duration' => '6 weeks',
                'focus_areas' => ['Training & Team Contribution', 'Competency'],
                'description' => 'Leadership and team collaboration training for high performers ready for advancement.',
                'outcomes' => ['Develop leadership skills', 'Improve team collaboration', 'Mentor junior staff'],
                'prerequisites' => 'Overall score ≥ 85%',
                'difficulty' => 'Advanced'
            ],
            [
                'id' => 4,
                'name' => 'Operational Excellence',
                'duration' => '2 weeks',
                'focus_areas' => ['Store Operations Support', 'Inventory & Cost Control'],
                'description' => 'Operations and inventory management training for efficiency improvement.',
                'outcomes' => ['Reduce operational errors', 'Improve inventory accuracy', 'Optimize cost control'],
                'prerequisites' => 'None',
                'difficulty' => 'Beginner'
            ],
            [
                'id' => 5,
                'name' => 'Time Management & Productivity',
                'duration' => '2 weeks',
                'focus_areas' => ['Daily Sales Operations', 'Store Operations Support'],
                'description' => 'Productivity and time management training for consistency improvement.',
                'outcomes' => ['Improve punctuality', 'Increase productivity', 'Better task prioritization'],
                'prerequisites' => 'None',
                'difficulty' => 'Beginner'
            ],
            [
                'id' => 6,
                'name' => 'Advanced Product Knowledge',
                'duration' => '3 weeks',
                'focus_areas' => ['Competency', 'Daily Sales Operations'],
                'description' => 'Deep-dive product training for technical knowledge enhancement.',
                'outcomes' => ['Master product features', 'Improve product recommendations', 'Handle technical queries'],
                'prerequisites' => 'None',
                'difficulty' => 'Intermediate'
            ]
        ];
        
        // Identify skill gaps
        $skillGaps = [];
        $categoryScores = [];
        
        foreach ($data['category_scores'] as $cat) {
            $catScore = ($cat['score'] / 5) * 100;
            $categoryScores[$cat['kpi_group']] = $catScore;
            
            if ($catScore < 50) {
                $skillGaps[] = [
                    'category' => $cat['kpi_group'],
                    'score' => $catScore,
                    'severity' => 'Critical',
                    'priority' => 3
                ];
            } elseif ($catScore < 70) {
                $skillGaps[] = [
                    'category' => $cat['kpi_group'],
                    'score' => $catScore,
                    'severity' => 'Moderate',
                    'priority' => 2
                ];
            } elseif ($catScore < 85) {
                $skillGaps[] = [
                    'category' => $cat['kpi_group'],
                    'score' => $catScore,
                    'severity' => 'Minor',
                    'priority' => 1
                ];
            }
        }
        
        // Match training programs to skill gaps
        $recommendations = [];
        
        foreach ($trainingPrograms as $program) {
            $matchScore = 0;
            $matchedGaps = [];
            $relevanceScore = 0;
            
            // Calculate match score based on focus areas
            foreach ($program['focus_areas'] as $focusArea) {
                if (isset($categoryScores[$focusArea])) {
                    $score = $categoryScores[$focusArea];
                    
                    // Higher match for lower scores (more need)
                    if ($score < 50) {
                        $matchScore += 40;
                        $relevanceScore += 3;
                    } elseif ($score < 70) {
                        $matchScore += 30;
                        $relevanceScore += 2;
                    } elseif ($score < 85) {
                        $matchScore += 20;
                        $relevanceScore += 1;
                    } else {
                        $matchScore += 5; // Still relevant for excellence
                    }
                    
                    // Track which gaps this program addresses
                    foreach ($skillGaps as $gap) {
                        if ($gap['category'] === $focusArea) {
                            $matchedGaps[] = $gap;
                        }
                    }
                }
            }
            
            // Adjust for prerequisites
            if (strpos($program['prerequisites'], '≥ 85%') !== false && $data['overall_score'] < 85) {
                $matchScore *= 0.5; // Reduce match if prerequisites not met
            }
            
            // Normalize match score to 0-100
            $matchScore = min(100, $matchScore);
            
            // Only recommend if there's a reasonable match
            if ($matchScore >= 20 || !empty($matchedGaps)) {
                $recommendations[] = [
                    'program' => $program,
                    'match_score' => round($matchScore, 2),
                    'matched_gaps' => $matchedGaps,
                    'relevance' => $relevanceScore,
                    'priority' => !empty($matchedGaps) ? max(array_column($matchedGaps, 'priority')) : 0
                ];
            }
        }
        
        // Sort by match score (descending)
        usort($recommendations, function($a, $b) {
            if ($b['match_score'] == $a['match_score']) {
                return $b['relevance'] - $a['relevance'];
            }
            return $b['match_score'] - $a['match_score'];
        });
        
        return [
            'success' => true,
            'staff_id' => $staff_id,
            'overall_score' => $data['overall_score'],
            'skill_gaps' => $skillGaps,
            'recommendations' => array_slice($recommendations, 0, 5), // Top 5
            'total_gaps' => count($skillGaps)
        ];
    }
}

?>
