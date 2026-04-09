<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;

// Get available years
// Fixed year range: 2026 down to 2021
$available_years = range(2026, 2021);

// Get all active staff with scores
$sql = "SELECT staff_id, staff_code as staff_number, name as full_name, department as department_name, status
        FROM staff
        WHERE status = 'Active'
        ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$all_staff = $stmt->fetchAll();

$leaderboard = [];
$achievements = [];

foreach ($all_staff as $staff) {
    $score_data = $calculator->calculateOverallScore($staff['staff_id'], $selected_year);
    $trend = $calculator->getPerformanceTrend($staff['staff_id']);
    
    if ($score_data['has_data']) {
        // Calculate points
        $points = 0;
        $badges = [];
        
        // Base points from score
        $points += $score_data['overall_score'] * 10;
        
        // Bonus for top performer
        if ($score_data['overall_score'] >= 85) {
            $points += 500;
            $badges[] = ['name' => 'Top Performer', 'icon' => 'trophy-fill', 'color' => 'warning'];
        }
        
        // Bonus for improvement
        if (count($trend) >= 2) {
            $latest = end($trend);
            if ($latest['trend'] == 'improving' && $latest['change'] > 5) {
                $points += 300;
                $badges[] = ['name' => 'Rising Star', 'icon' => 'star-fill', 'color' => 'info'];
            }
        }
        
        // Bonus for consistency (3+ years of good performance)
        $consistent_years = 0;
        foreach ($trend as $year_data) {
            if ($year_data['overall_score'] >= 75) {
                $consistent_years++;
            }
        }
        if ($consistent_years >= 3) {
            $points += 400;
            $badges[] = ['name' => 'Consistent Performer', 'icon' => 'check-circle-fill', 'color' => 'success'];
        }
        
        // Category excellence badges
        foreach ($score_data['category_scores'] as $cat) {
            if ($cat['score'] >= 90) {
                $badges[] = [
                    'name' => substr($cat['category_name'], 0, 20) . ' Expert',
                    'icon' => 'award-fill',
                    'color' => 'primary'
                ];
                $points += 100;
            }
        }
        
        // Perfect score
        if ($score_data['overall_score'] >= 95) {
            $badges[] = ['name' => 'Excellence Award', 'icon' => 'gem', 'color' => 'danger'];
            $points += 1000;
        }
        
        // Calculate level
        $level = floor($points / 1000) + 1;
        $next_level_points = $level * 1000;
        $progress = (($points % 1000) / 1000) * 100;
        
        $leaderboard[] = [
            'staff_id' => $staff['staff_id'],
            'staff_number' => $staff['staff_number'],
            'full_name' => $staff['full_name'],
            'department' => $staff['department_name'],
            'score' => $score_data['overall_score'],
            'points' => $points,
            'level' => $level,
            'progress' => $progress,
            'next_level_points' => $next_level_points,
            'badges' => $badges
        ];
    }
}

// Sort by points
usort($leaderboard, function($a, $b) {
    return $b['points'] <=> $a['points'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Gamification - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .leaderboard-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .leaderboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%); }
        .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); }
        .badge-icon {
            font-size: 1.5rem;
            margin: 5px;
        }
        .level-badge {
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-controller"></i> Performance Gamification Dashboard</h1>
                    <div class="btn-toolbar">
                        <select class="form-select" onchange="window.location.href='?year='+this.value">
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="alert alert-success">
                    <i class="bi bi-controller"></i> 
                    <strong>Innovative Feature #3:</strong> Gamified performance visualization with points, levels, badges, and leaderboards to motivate and recognize excellence.
                </div>

                <!-- Top 3 Podium -->
                <?php if (count($leaderboard) >= 3): ?>
                <div class="row mb-4">
                    <!-- 2nd Place -->
                    <div class="col-md-4 order-md-1">
                        <div class="card leaderboard-card shadow rank-2 text-white">
                            <div class="card-body text-center">
                                <div class="display-1">🥈</div>
                                <h3 class="mt-2"><?= htmlspecialchars($leaderboard[1]['full_name']) ?></h3>
                                <p class="mb-1"><?= $leaderboard[1]['staff_number'] ?></p>
                                <h4><?= number_format($leaderboard[1]['points']) ?> pts</h4>
                                <p class="mb-0">Level <?= $leaderboard[1]['level'] ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 1st Place -->
                    <div class="col-md-4 order-md-2">
                        <div class="card leaderboard-card shadow rank-1 text-white" style="margin-top: -20px;">
                            <div class="card-body text-center">
                                <div class="display-1">🏆</div>
                                <h2 class="mt-2"><?= htmlspecialchars($leaderboard[0]['full_name']) ?></h2>
                                <p class="mb-1"><?= $leaderboard[0]['staff_number'] ?></p>
                                <h3><?= number_format($leaderboard[0]['points']) ?> pts</h3>
                                <p class="mb-0">Level <?= $leaderboard[0]['level'] ?></p>
                                <div class="mt-2">
                                    <?php foreach (array_slice($leaderboard[0]['badges'], 0, 3) as $badge): ?>
                                        <i class="bi bi-<?= $badge['icon'] ?> badge-icon"></i>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3rd Place -->
                    <div class="col-md-4 order-md-3">
                        <div class="card leaderboard-card shadow rank-3 text-white">
                            <div class="card-body text-center">
                                <div class="display-1">🥉</div>
                                <h3 class="mt-2"><?= htmlspecialchars($leaderboard[2]['full_name']) ?></h3>
                                <p class="mb-1"><?= $leaderboard[2]['staff_number'] ?></p>
                                <h4><?= number_format($leaderboard[2]['points']) ?> pts</h4>
                                <p class="mb-0">Level <?= $leaderboard[2]['level'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Points System Explanation -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="bi bi-info-circle-fill"></i> How Points & Levels Work
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Earning Points:</h6>
                                <ul>
                                    <li><strong>Base Points:</strong> KPI Score × 10 (e.g., 85% = 850 pts)</li>
                                    <li><strong>Top Performer Bonus:</strong> +500 pts (Score ≥ 85%)</li>
                                    <li><strong>Rising Star Bonus:</strong> +300 pts (Improved 5+ points)</li>
                                    <li><strong>Consistency Bonus:</strong> +400 pts (3+ years ≥ 75%)</li>
                                    <li><strong>Category Excellence:</strong> +100 pts per category ≥ 90%</li>
                                    <li><strong>Excellence Award:</strong> +1000 pts (Score ≥ 95%)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Level System:</h6>
                                <ul>
                                    <li><strong>Level 1:</strong> 0 - 999 points</li>
                                    <li><strong>Level 2:</strong> 1,000 - 1,999 points</li>
                                    <li><strong>Level 3:</strong> 2,000 - 2,999 points</li>
                                    <li><strong>Level 4+:</strong> Every 1,000 points</li>
                                </ul>
                                <h6 class="mt-3">Achievement Badges:</h6>
                                <p>Earn badges for excellence, improvement, and consistency!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Full Leaderboard -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-list-ol"></i> Complete Leaderboard (<?= $selected_year ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Rank</th>
                                        <th scope="col">Staff Member</th>
                                        <th scope="col">Department</th>
                                        <th scope="col">Level</th>
                                        <th scope="col">Points</th>
                                        <th scope="col">KPI Score</th>
                                        <th scope="col">Badges</th>
                                        <th scope="col">Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard as $index => $entry): ?>
                                        <tr>
                                            <td>
                                                <?php if ($index == 0): ?>
                                                    <span class="badge bg-warning fs-5">🥇 1</span>
                                                <?php elseif ($index == 1): ?>
                                                    <span class="badge bg-secondary fs-6">🥈 2</span>
                                                <?php elseif ($index == 2): ?>
                                                    <span class="badge bg-danger fs-6">🥉 3</span>
                                                <?php else: ?>
                                                    <strong><?= $index + 1 ?></strong>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($entry['full_name']) ?></strong><br>
                                                <small class="text-muted"><?= $entry['staff_number'] ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($entry['department']) ?></td>
                                            <td>
                                                <span class="badge bg-primary level-badge">
                                                    Lv <?= $entry['level'] ?>
                                                </span>
                                            </td>
                                            <td><strong><?= number_format($entry['points']) ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?= $entry['score'] >= 85 ? 'success' : ($entry['score'] >= 75 ? 'info' : 'warning') ?>">
                                                    <?= $entry['score'] ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php foreach (array_slice($entry['badges'], 0, 3) as $badge): ?>
                                                    <i class="bi bi-<?= $badge['icon'] ?> text-<?= $badge['color'] ?>" 
                                                       title="<?= $badge['name'] ?>" 
                                                       data-bs-toggle="tooltip"></i>
                                                <?php endforeach; ?>
                                                <?php if (count($entry['badges']) > 3): ?>
                                                    <span class="badge bg-secondary">+<?= count($entry['badges']) - 3 ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="min-width: 150px;">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" 
                                                         style="width: <?= $entry['progress'] ?>%"
                                                         title="<?= round($entry['progress'], 1) ?>% to Level <?= $entry['level'] + 1 ?>">
                                                        <?= round($entry['progress']) ?>%
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    <?= number_format($entry['next_level_points'] - $entry['points']) ?> pts to next level
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Achievement Gallery -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning">
                        <h6 class="m-0 font-weight-bold text-dark">
                            <i class="bi bi-award-fill"></i> Achievement Badges Earned This Year
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $all_badges = [];
                            foreach ($leaderboard as $entry) {
                                foreach ($entry['badges'] as $badge) {
                                    $badge_name = $badge['name'];
                                    if (!isset($all_badges[$badge_name])) {
                                        $all_badges[$badge_name] = [
                                            'badge' => $badge,
                                            'count' => 0,
                                            'holders' => []
                                        ];
                                    }
                                    $all_badges[$badge_name]['count']++;
                                    $all_badges[$badge_name]['holders'][] = $entry['full_name'];
                                }
                            }
                            
                            foreach ($all_badges as $badge_name => $data):
                            ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card text-center h-100">
                                        <div class="card-body">
                                            <i class="bi bi-<?= $data['badge']['icon'] ?> text-<?= $data['badge']['color'] ?>" 
                                               style="font-size: 3rem;"></i>
                                            <h6 class="mt-2"><?= htmlspecialchars($badge_name) ?></h6>
                                            <p class="text-muted mb-0">
                                                <small>Earned by <?= $data['count'] ?> staff</small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>
