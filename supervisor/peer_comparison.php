<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

// Get available years
$sql = "SELECT DISTINCT evaluation_year FROM kpi_scores ORDER BY evaluation_year DESC";
$stmt = $pdo->query($sql);
$available_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get unique departments from staff table
$sql = "SELECT DISTINCT department FROM staff WHERE department IS NOT NULL ORDER BY department";
$stmt = $pdo->query($sql);
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;
$selected_staff = $_GET['staff_id'] ?? 0;

$staff_info = null;
$comparison_data = null;
$peers = [];

if ($selected_staff > 0) {
    // Get staff information
    $stmt = $pdo->prepare("SELECT staff_id, name as full_name, staff_code as staff_number, department FROM staff WHERE staff_id = ?");
    $stmt->execute([$selected_staff]);
    $staff_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff_info) {
        // Get staff score
        $staff_score = $calculator->calculateOverallScore($selected_staff, $selected_year);
        
        // Get team comparison
        $comparison_data = $calculator->compareToTeamAverage($selected_staff, $selected_year);
        
        // Get peers in same department
        $sql = "SELECT staff_id, name as full_name, staff_code as staff_number,
                (SELECT SUM(weighted_score) FROM kpi_scores WHERE staff_id = s.staff_id AND evaluation_year = ?) as overall_score
                FROM staff s 
                WHERE s.department = ? AND s.staff_id != ? AND s.status = 'Active'
                ORDER BY overall_score DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selected_year, $staff_info['department'], $selected_staff]);
        $peers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peer Comparison - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-people"></i> Peer Comparison</h1>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> 
                    <strong>Innovative Feature #2:</strong> Compare individual performance against department peers and team averages for contextual evaluation.
                </div>
                
                <!-- Selection Form -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Select Staff Member to Compare</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Staff Member:</label>
                                <select name="staff_id" class="form-select" required>
                                    <option value="">Choose staff...</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT staff_id, staff_code, name FROM staff WHERE status = 'Active' ORDER BY name");
                                    $staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($staff_list as $staff):
                                    ?>
                                        <option value="<?= $staff['staff_id'] ?>" <?= $selected_staff == $staff['staff_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($staff['name']) ?> (<?= htmlspecialchars($staff['staff_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Evaluation Year:</label>
                                <select name="year" class="form-select">
                                    <?php foreach ($available_years as $year): ?>
                                        <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Compare
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($selected_staff && $staff_info && $comparison_data): ?>
                    <!-- Performance Overview -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="bi bi-speedometer2"></i> Performance Overview
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="card shadow h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-person-fill fs-1 text-primary"></i>
                                            <h3 class="mt-2"><?= $comparison_data['staff_score'] ?>%</h3>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($staff_info['full_name']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow h-100">
                                        <div class="card-body text-center">
                                            <i class="bi bi-people-fill fs-1 text-info"></i>
                                            <h3 class="mt-2"><?= $comparison_data['team_average'] ?>%</h3>
                                            <p class="text-muted mb-0">Team Average</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow h-100 border-<?= $comparison_data['difference'] >= 0 ? 'success' : 'danger' ?>">
                                        <div class="card-body text-center">
                                            <i class="bi bi-<?= $comparison_data['difference'] >= 0 ? 'arrow-up-circle' : 'arrow-down-circle' ?>-fill fs-1 text-<?= $comparison_data['difference'] >= 0 ? 'success' : 'danger' ?>"></i>
                                            <h3 class="mt-2 text-<?= $comparison_data['difference'] >= 0 ? 'success' : 'danger' ?>">
                                                <?= $comparison_data['difference'] > 0 ? '+' : '' ?><?= $comparison_data['difference'] ?>%
                                            </h3>
                                            <p class="text-muted mb-0"><?= $comparison_data['performance_vs_team'] ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Insights -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="bi bi-lightbulb-fill"></i> Performance Insights
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $staff_score_val = $comparison_data['staff_score'];
                            $team_avg = $comparison_data['team_average'];
                            $diff = $comparison_data['difference'];
                            
                            if ($diff > 10) {
                                echo "<div class='alert alert-success'>";
                                echo "<strong>Exceptional Performance!</strong> This staff member is performing significantly above the team average (+{$diff}%). ";
                                echo "Consider for leadership roles, mentoring opportunities, or special recognition.";
                                echo "</div>";
                            } elseif ($diff > 5) {
                                echo "<div class='alert alert-success'>";
                                echo "<strong>Above Average Performance!</strong> This staff member is performing well above the team average (+{$diff}%). ";
                                echo "Maintain current support and consider for advanced training.";
                                echo "</div>";
                            } elseif ($diff >= -5) {
                                echo "<div class='alert alert-info'>";
                                echo "<strong>Average Performance.</strong> This staff member is performing at or near the team average. ";
                                echo "Identify specific areas for improvement to reach the next level.";
                                echo "</div>";
                            } elseif ($diff >= -10) {
                                echo "<div class='alert alert-warning'>";
                                echo "<strong>Below Average Performance.</strong> This staff member is performing below the team average ({$diff}%). ";
                                echo "Provide additional support and training to help improve performance.";
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-danger'>";
                                echo "<strong>Critical Performance Gap.</strong> This staff member is significantly below the team average ({$diff}%). ";
                                echo "Immediate intervention required with performance improvement plan.";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Department Peer Ranking -->
                    <?php if (!empty($peers)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-bar-chart-fill"></i> Department Peer Ranking (<?= htmlspecialchars($staff_info['department']) ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Staff Member</th>
                                            <th>Overall Score</th>
                                            <th>Comparison</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $rank = 1;
                                        $staff_rank = 0;
                                        $staff_found = false;
                                        
                                        // Find selected staff's rank
                                        foreach ($peers as $index => $peer) {
                                            if ($peer['overall_score'] > $comparison_data['staff_score']) {
                                                $rank++;
                                            }
                                        }
                                        $staff_rank = $rank;
                                        ?>
                                        
                                        <!-- Selected Staff Row -->
                                        <tr class="table-primary">
                                            <td><strong><?= $staff_rank ?></strong></td>
                                            <td>
                                                <strong><?= htmlspecialchars($staff_info['full_name']) ?></strong>
                                                <span class="badge bg-primary ms-2">YOU</span>
                                            </td>
                                            <td><strong><?= $comparison_data['staff_score'] ?>%</strong></td>
                                            <td><span class="badge bg-primary">Selected</span></td>
                                            <td>
                                                <a href="staff_profile.php?id=<?= $selected_staff ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        
                                        <!-- Peer Rows -->
                                        <?php
                                        $rank = 1;
                                        foreach ($peers as $peer):
                                            $diff_from_selected = $peer['overall_score'] - $comparison_data['staff_score'];
                                        ?>
                                            <tr>
                                                <td><?= $rank ?></td>
                                                <td><?= htmlspecialchars($peer['full_name']) ?></td>
                                                <td><?= $peer['overall_score'] ?>%</td>
                                                <td>
                                                    <?php if ($diff_from_selected > 0): ?>
                                                        <span class="badge bg-success">+<?= round($diff_from_selected, 1) ?>% higher</span>
                                                    <?php elseif ($diff_from_selected < 0): ?>
                                                        <span class="badge bg-danger"><?= round($diff_from_selected, 1) ?>% lower</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Same</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="staff_profile.php?id=<?= $peer['staff_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php
                                            $rank++;
                                        endforeach;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <p class="mb-0">
                                    <strong>Department Ranking:</strong> 
                                    <?= $staff_rank ?> out of <?= count($peers) + 1 ?> staff members
                                    (Top <?= round(($staff_rank / (count($peers) + 1)) * 100, 1) ?>%)
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Category Comparison Chart -->
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-radar"></i> Category Performance vs Department Average
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryComparisonChart"></canvas>
                            </div>
                        </div>
                    </div>
                <?php elseif ($selected_staff): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No data available for the selected staff member in <?= $selected_year ?>.
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary text-center py-5">
                        <i class="bi bi-arrow-up-circle fs-1"></i>
                        <h4 class="mt-3">Select a Staff Member to Begin Comparison</h4>
                        <p class="text-muted">Choose a staff member and year from the form above to see detailed peer comparison analysis.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php if ($selected_staff && $staff_info && $comparison_data): ?>
    <script>
        // Get category data
        fetch('../api/kpi_calculations.php?action=overall_score&staff_id=<?= $selected_staff ?>&year=<?= $selected_year ?>')
            .then(response => response.json())
            .then(staffData => {
                // Get department average
                fetch('../api/kpi_calculations.php?action=category_averages&year=<?= $selected_year ?>')
                    .then(response => response.json())
                    .then(avgData => {
                        if (staffData.success && avgData.success) {
                            const categories = staffData.data.category_scores.map(c => c.category_name);
                            const staffScores = staffData.data.category_scores.map(c => c.score);
                            const avgScores = avgData.data.map(c => c.average);
                            
                            const ctx = document.getElementById('categoryComparisonChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'radar',
                                data: {
                                    labels: categories,
                                    datasets: [
                                        {
                                            label: '<?= htmlspecialchars($staff_info['full_name']) ?>',
                                            data: staffScores,
                                            backgroundColor: 'rgba(78, 115, 223, 0.2)',
                                            borderColor: '#4e73df',
                                            pointBackgroundColor: '#4e73df',
                                            pointBorderColor: '#fff',
                                            pointHoverBackgroundColor: '#fff',
                                            pointHoverBorderColor: '#4e73df'
                                        },
                                        {
                                            label: 'Department Average',
                                            data: avgScores,
                                            backgroundColor: 'rgba(28, 200, 138, 0.2)',
                                            borderColor: '#1cc88a',
                                            pointBackgroundColor: '#1cc88a',
                                            pointBorderColor: '#fff',
                                            pointHoverBackgroundColor: '#fff',
                                            pointHoverBorderColor: '#1cc88a'
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    scales: {
                                        r: {
                                            beginAtZero: true,
                                            max: 100,
                                            ticks: {
                                                stepSize: 20
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        }
                                    }
                                }
                            });
                        }
                    });
            });
    </script>
    <?php endif; ?>
</body>
</html>
