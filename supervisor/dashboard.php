<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

// Get current year
$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;

// Get all staff with scores
$sql = "SELECT DISTINCT s.staff_id, s.staff_code as staff_number, s.name as full_name, 
        s.department as department_name, s.status
        FROM staff s
        ORDER BY s.name";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$all_staff = $stmt->fetchAll();

// Calculate scores for all staff
$staff_data = [];
foreach ($all_staff as $staff) {
    $score_data = $calculator->calculateOverallScore($staff['staff_id'], $selected_year);
    $classification = $calculator->getPerformanceClassification($score_data['overall_score']);
    
    $staff_data[] = [
        'staff_id' => $staff['staff_id'],
        'staff_number' => $staff['staff_number'],
        'full_name' => $staff['full_name'],
        'department' => $staff['department_name'],
        'status' => $staff['status'],
        'overall_score' => $score_data['overall_score'],
        'classification' => $classification['label'],
        'badge_class' => $classification['badge'],
        'has_data' => $score_data['has_data']
    ];
}

// Get top performers and at-risk staff
$top_performers = $calculator->getTopPerformers($selected_year, 85);
$at_risk_staff = $calculator->getAtRiskStaff($selected_year);

// Get performance distribution
$sql = "SELECT DISTINCT s.staff_id FROM staff s WHERE s.status = 'Active'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$active_staff_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$distribution = [
    'Top Performer' => 0,
    'Good Performer' => 0,
    'Satisfactory' => 0,
    'Needs Improvement' => 0,
    'Critical' => 0
];

foreach ($active_staff_ids as $staff_id) {
    $result = $calculator->calculateOverallScore($staff_id, $selected_year);
    if ($result['has_data']) {
        $class = $calculator->classifyPerformance($result['overall_score']);
        $distribution[$class]++;
    }
}

// Get available years
// Fixed year range: 2026 down to 2021
$available_years = range(2026, 2021);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard - KPI Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Supervisor Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <select class="form-select" id="yearFilter" onchange="filterByYear(this.value)">
                                <?php foreach ($available_years as $year): ?>
                                    <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Export Report
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 clickable-card" 
                             onclick="window.location.href='staff_list.php'"
                             data-bs-toggle="tooltip" 
                             data-bs-placement="top" 
                             title="View all staff members and their performance details">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Staff</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= count($staff_data) ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 clickable-card" 
                             onclick="window.location.href='reports.php?type=top_performers'"
                             data-bs-toggle="tooltip" 
                             data-bs-placement="top" 
                             title="View staff with scores ≥85% - Click to see detailed report">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Top Performers</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= count($top_performers) ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-trophy fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 clickable-card" 
                             onclick="window.location.href='reports.php?type=at_risk'"
                             data-bs-toggle="tooltip" 
                             data-bs-placement="top" 
                             title="Staff requiring immediate attention - Click to see who needs support">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #e74a3b;">
                                            At-Risk Staff</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= count($at_risk_staff) ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-exclamation-triangle fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 clickable-card" 
                             onclick="window.location.href='analytics.php'"
                             data-bs-toggle="tooltip" 
                             data-bs-placement="top" 
                             title="Team average performance score - Click for detailed analytics">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Average Score</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php
                                            $total_score = 0;
                                            $count = 0;
                                            foreach ($staff_data as $staff) {
                                                if ($staff['has_data']) {
                                                    $total_score += $staff['overall_score'];
                                                    $count++;
                                                }
                                            }
                                            echo $count > 0 ? round($total_score / $count, 1) . '%' : 'N/A';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-graph-up fs-2 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attention Required Section -->
                <?php if (!empty($at_risk_staff)): ?>
                <div class="alert alert-warning alert-dismissible fade show shadow" role="alert">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Attention Required</h5>
                    <p><strong><?= count($at_risk_staff) ?></strong> staff member(s) require immediate attention:</p>
                    <ul class="mb-0">
                        <?php foreach (array_slice($at_risk_staff, 0, 5) as $staff): ?>
                            <li>
                                <strong><?= htmlspecialchars($staff['full_name']) ?></strong> 
                                (<?= $staff['current_score'] ?>%) - <?= $staff['reason'] ?>
                                <a href="staff_profile.php?id=<?= $staff['staff_id'] ?>" class="alert-link">View Profile</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($at_risk_staff) > 5): ?>
                        <hr>
                        <a href="reports.php?type=at_risk" class="alert-link">View all <?= count($at_risk_staff) ?> at-risk staff →</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Performance Level Distribution (Gamified Visualization) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-award"></i> Team Performance Level Distribution
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    // Define performance levels with colors and icons
                                    $levels = [
                                        'Platinum' => ['min' => 95, 'color' => '#e5e4e2', 'icon' => 'gem', 'count' => 0],
                                        'Gold' => ['min' => 85, 'color' => '#ffd700', 'icon' => 'trophy-fill', 'count' => 0],
                                        'Silver' => ['min' => 70, 'color' => '#c0c0c0', 'icon' => 'award-fill', 'count' => 0],
                                        'Bronze' => ['min' => 50, 'color' => '#cd7f32', 'icon' => 'star-fill', 'count' => 0],
                                        'Developing' => ['min' => 0, 'color' => '#6c757d', 'icon' => 'arrow-up-circle', 'count' => 0]
                                    ];
                                    
                                    // Count staff in each level
                                    foreach ($staff_data as $staff) {
                                        if ($staff['has_data']) {
                                            $score = $staff['overall_score'];
                                            if ($score >= 95) $levels['Platinum']['count']++;
                                            elseif ($score >= 85) $levels['Gold']['count']++;
                                            elseif ($score >= 70) $levels['Silver']['count']++;
                                            elseif ($score >= 50) $levels['Bronze']['count']++;
                                            else $levels['Developing']['count']++;
                                        }
                                    }
                                    
                                    foreach ($levels as $levelName => $levelData):
                                    ?>
                                        <div class="col-md-2 mb-3">
                                            <div class="card h-100 text-center level-card" 
                                                 style="border: 3px solid <?= $levelData['color'] ?>; cursor: pointer; transition: transform 0.3s;"
                                                 onmouseover="this.style.transform='scale(1.05)'"
                                                 onmouseout="this.style.transform='scale(1)'"
                                                 data-level="<?= $levelName ?>"
                                                 data-count="<?= $levelData['count'] ?>">
                                                <div class="card-body">
                                                    <i class="bi bi-<?= $levelData['icon'] ?> fs-1 mb-2" 
                                                       style="color: <?= $levelData['color'] ?>;"></i>
                                                    <h5 class="card-title mb-1"><?= $levelName ?></h5>
                                                    <p class="text-muted small mb-2">
                                                        <?php
                                                        if ($levelName === 'Platinum') echo '≥95%';
                                                        elseif ($levelName === 'Gold') echo '85-94%';
                                                        elseif ($levelName === 'Silver') echo '70-84%';
                                                        elseif ($levelName === 'Bronze') echo '50-69%';
                                                        else echo '<50%';
                                                        ?>
                                                    </p>
                                                    <h2 class="mb-0 level-count" data-target="<?= $levelData['count'] ?>">0</h2>
                                                    <small class="text-muted">staff members</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Distribution Chart -->
                <div class="row mb-4">
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow mb-4 w-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Performance Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div style="position: relative; height: 280px;">
                                    <canvas id="distributionChart"></canvas>
                                </div>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Quick Insight:</strong> 
                                        This visualization shows your team's performance spread. A healthy distribution has most staff in "Good Performer" or above categories, indicating effective training and support systems.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card shadow mb-4 w-100">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Top Performers & At-Risk Staff</h6>
                                <small class="text-muted"><?= $selected_year ?></small>
                            </div>
                            <div class="card-body">
                                <!-- Top Performers -->
                                <h6 class="text-success mb-3"><i class="bi bi-trophy-fill"></i> Top Performers (≥85%)</h6>
                                <?php if (empty($top_performers)): ?>
                                    <p class="text-muted small">No top performers for this period.</p>
                                <?php else: ?>
                                    <div class="list-group mb-4">
                                        <?php foreach (array_slice($top_performers, 0, 3) as $performer): ?>
                                            <a href="staff_profile.php?id=<?= $performer['staff_id'] ?>" 
                                               class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($performer['full_name']) ?></h6>
                                                    <span class="badge bg-success"><?= $performer['overall_score'] ?>%</span>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($performer['department']) ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- At-Risk Staff -->
                                <h6 class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i> At-Risk Staff (<70%)</h6>
                                <?php if (empty($at_risk_staff)): ?>
                                    <p class="text-muted small">No at-risk staff for this period.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach (array_slice($at_risk_staff, 0, 3) as $staff): ?>
                                            <a href="staff_profile.php?id=<?= $staff['staff_id'] ?>" 
                                               class="list-group-item list-group-item-action list-group-item-warning">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($staff['full_name']) ?></h6>
                                                    <span class="badge bg-danger"><?= $staff['current_score'] ?>%</span>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($staff['reason']) ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Performance Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">All Staff Performance</h6>
                        <input type="text" id="searchInput" class="form-control form-control-sm" 
                               style="max-width: 300px;" placeholder="Search by name or department...">
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="staffTable">
                                <thead>
                                    <tr>
                                        <th onclick="sortTable(0)">Staff ID <i class="bi bi-arrow-down-up"></i></th>
                                        <th onclick="sortTable(1)">Name <i class="bi bi-arrow-down-up"></i></th>
                                        <th onclick="sortTable(2)">Department <i class="bi bi-arrow-down-up"></i></th>
                                        <th onclick="sortTable(3)">Score <i class="bi bi-arrow-down-up"></i></th>
                                        <th onclick="sortTable(4)">Classification <i class="bi bi-arrow-down-up"></i></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_data as $staff): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($staff['staff_number']) ?></td>
                                            <td><?= htmlspecialchars($staff['full_name']) ?></td>
                                            <td><?= htmlspecialchars($staff['department']) ?></td>
                                            <td>
                                                <?php if ($staff['has_data']): ?>
                                                    <strong><?= $staff['overall_score'] ?>%</strong>
                                                <?php else: ?>
                                                    <span class="text-muted">No data</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $staff['badge_class'] ?>">
                                                    <?= $staff['classification'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="staff_profile.php?id=<?= $staff['staff_id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Animate level counters on page load
        window.addEventListener('load', function() {
            const levelCounts = document.querySelectorAll('.level-count');
            
            levelCounts.forEach((counter, index) => {
                const target = parseInt(counter.getAttribute('data-target'));
                let current = 0;
                const increment = target / 50; // 50 steps
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                        
                        // Show level-up notification for levels with staff
                        if (target > 0 && index < 2) { // Only for top 2 levels
                            const levelCard = counter.closest('.level-card');
                            const levelName = levelCard.getAttribute('data-level');
                            
                            setTimeout(() => {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: `${target} staff in ${levelName} level!`,
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                            }, index * 500);
                        }
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 20);
            });
        });
        
        // Performance Distribution Chart
        const ctx = document.getElementById('distributionChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($distribution)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($distribution)) ?>,
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8',
                        '#ffc107',
                        '#fd7e14',
                        '#dc3545'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${context.parsed} staff (${pct}%)`;
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                beforeDraw(chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;

                    // Centre of the actual doughnut drawing area (excludes legend)
                    const cx = (chartArea.left + chartArea.right)  / 2;
                    const cy = (chartArea.top  + chartArea.bottom) / 2;

                    const yearText  = 'Year';
                    const yearValue = '<?= $selected_year ?>';

                    // Sizes relative to the doughnut radius
                    const radius    = (chartArea.bottom - chartArea.top) / 2;
                    const labelSize = Math.max(10, Math.round(radius / 5));
                    const valueSize = Math.max(15, Math.round(radius / 3));
                    const gap       = labelSize * 0.3;

                    ctx.save();
                    ctx.textAlign    = 'center';
                    ctx.textBaseline = 'middle';

                    // "Year" — small grey label above centre
                    ctx.font      = `${labelSize}px sans-serif`;
                    ctx.fillStyle = '#aaa';
                    ctx.fillText(yearText, cx, cy - valueSize * 0.5 - gap);

                    // Year number — bold, below label
                    ctx.font      = `bold ${valueSize}px sans-serif`;
                    ctx.fillStyle = '#444';
                    ctx.fillText(yearValue, cx, cy + labelSize * 0.5 + gap);

                    ctx.restore();
                }
            }]
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('staffTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            }
        });

        // Sort table
        function sortTable(columnIndex) {
            const table = document.getElementById('staffTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = table.dataset.sortOrder !== 'asc';
            
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();
                
                if (!isNaN(aValue) && !isNaN(bValue)) {
                    return isAscending ? aValue - bValue : bValue - aValue;
                }
                
                return isAscending ? 
                    aValue.localeCompare(bValue) : 
                    bValue.localeCompare(aValue);
            });
            
            rows.forEach(row => table.querySelector('tbody').appendChild(row));
            table.dataset.sortOrder = isAscending ? 'asc' : 'desc';
        }

        // Year filter
        function filterByYear(year) {
            window.location.href = '?year=' + year;
        }
    </script>
</body>
</html>
