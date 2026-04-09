<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

$report_type = $_GET['type'] ?? 'top_performers';
$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;

// Get available years
// Fixed year range: 2026 down to 2021
$available_years = range(2026, 2021);

$report_data = [];
$report_title = '';
$report_description = '';

switch ($report_type) {
    case 'top_performers':
        $report_title = 'Top Performers Report';
        $report_description = 'Staff members with outstanding performance (KPI score ≥ 85%)';
        $report_data = $calculator->getTopPerformers($selected_year, 85);
        break;
        
    case 'at_risk':
        $report_title = 'At-Risk Staff Report';
        $report_description = 'Staff members requiring immediate attention and intervention';
        $report_data = $calculator->getAtRiskStaff($selected_year);
        break;
        
    case 'training':
        $report_title = 'Training Needs Summary';
        $report_description = 'Consolidated training recommendations for staff development';
        
        // Get all staff with comments
        $sql = "SELECT s.staff_id, s.name as full_name, s.staff_code as staff_number, s.department as department_name,
                sc.supervisor_comment, sc.training_recommendation, sc.evaluation_year
                FROM staff s
                LEFT JOIN staff_comments sc ON s.staff_id = sc.staff_id
                WHERE sc.evaluation_year = ?
                ORDER BY s.name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selected_year]);
        $report_data = $stmt->fetchAll();
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $report_title ?> - KPI System</title>
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
                    <h1 class="h2"><?= $report_title ?></h1>
                    <div class="btn-toolbar">
                        <div class="btn-group me-2">
                            <select class="form-select" onchange="window.location.href='?type=<?= $report_type ?>&year='+this.value">
                                <?php foreach ($available_years as $year): ?>
                                    <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                        <button class="btn btn-sm btn-success ms-2" onclick="exportToCSV()">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                        </button>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <?= $report_description ?>
                </div>

                <?php if ($report_type == 'top_performers'): ?>
                    <!-- Top Performers Report -->
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="bi bi-trophy-fill"></i> Top Performers (<?= $selected_year ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($report_data)): ?>
                                <p class="text-muted">No top performers found for this period.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="reportTable">
                                        <thead>
                                            <tr>
                                                <th scope="col">Rank</th>
                                                <th scope="col">Staff ID</th>
                                                <th scope="col">Name</th>
                                                <th scope="col">Department</th>
                                                <th scope="col">Overall Score</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $index => $staff): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($index == 0): ?>
                                                            <i class="bi bi-trophy-fill text-warning fs-4"></i>
                                                        <?php elseif ($index == 1): ?>
                                                            <i class="bi bi-trophy-fill text-secondary fs-5"></i>
                                                        <?php elseif ($index == 2): ?>
                                                            <i class="bi bi-trophy-fill text-danger fs-6"></i>
                                                        <?php else: ?>
                                                            <?= $index + 1 ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($staff['staff_number']) ?></td>
                                                    <td><strong><?= htmlspecialchars($staff['full_name']) ?></strong></td>
                                                    <td><?= htmlspecialchars($staff['department']) ?></td>
                                                    <td>
                                                        <span class="badge bg-success fs-6"><?= $staff['overall_score'] ?>%</span>
                                                    </td>
                                                    <td>
                                                        <a href="staff_profile.php?id=<?= $staff['staff_id'] ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="bi bi-eye"></i> View Profile
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-4">
                                    <h6>Summary Statistics</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h3><?= count($report_data) ?></h3>
                                                    <p class="mb-0">Total Top Performers</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h3><?= round(array_sum(array_column($report_data, 'overall_score')) / count($report_data), 1) ?>%</h3>
                                                    <p class="mb-0">Average Score</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h3><?= max(array_column($report_data, 'overall_score')) ?>%</h3>
                                                    <p class="mb-0">Highest Score</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($report_type == 'at_risk'): ?>
                    <!-- At-Risk Staff Report -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-warning">
                            <h6 class="m-0 font-weight-bold text-dark">
                                <i class="bi bi-exclamation-triangle-fill"></i> At-Risk Staff Requiring Intervention
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($report_data)): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle"></i> Excellent! No staff members are currently at risk.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="reportTable">
                                        <thead>
                                            <tr>
                                                <th scope="col">Priority</th>
                                                <th scope="col">Staff ID</th>
                                                <th scope="col">Name</th>
                                                <th scope="col">Current Score</th>
                                                <th scope="col">Risk Level</th>
                                                <th scope="col">Reason</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $index => $staff): ?>
                                                <tr class="<?= $staff['risk_level'] == 'High' ? 'table-danger' : 'table-warning' ?>">
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($staff['staff_number']) ?></td>
                                                    <td><strong><?= htmlspecialchars($staff['full_name']) ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-danger"><?= $staff['current_score'] ?>%</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $staff['risk_level'] == 'High' ? 'danger' : 'warning' ?>">
                                                            <?= $staff['risk_level'] ?> Risk
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($staff['reason']) ?></td>
                                                    <td>
                                                        <a href="staff_profile.php?id=<?= $staff['staff_id'] ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="bi bi-eye"></i> Review
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="alert alert-danger mt-4">
                                    <h6 class="alert-heading">Recommended Actions:</h6>
                                    <ul class="mb-0">
                                        <li>Schedule one-on-one meetings with at-risk staff</li>
                                        <li>Develop personalized performance improvement plans</li>
                                        <li>Assign mentors or coaches for support</li>
                                        <li>Provide targeted training based on weak KPI categories</li>
                                        <li>Monitor progress weekly for high-risk staff</li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($report_type == 'training'): ?>
                    <!-- Training Needs Report -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="bi bi-book-fill"></i> Training & Development Recommendations
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($report_data)): ?>
                                <p class="text-muted">No training recommendations available for this period.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="reportTable">
                                        <thead>
                                            <tr>
                                                <th scope="col">Staff ID</th>
                                                <th scope="col">Name</th>
                                                <th scope="col">Department</th>
                                                <th scope="col">Training Recommendation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $staff): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($staff['staff_number']) ?></td>
                                                    <td><strong><?= htmlspecialchars($staff['full_name']) ?></strong></td>
                                                    <td><?= htmlspecialchars($staff['department_name']) ?></td>
                                                    <td>
                                                        <?php
                                                        $rec = $staff['training_recommendation'] ?: $staff['supervisor_comment'];
                                                        echo $rec
                                                            ? htmlspecialchars($rec)
                                                            : '<span class="text-muted fst-italic">No recommendation recorded</span>';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php
                                // Extract program keywords from both fields to find most common training needs
                                $program_keywords = [
                                    'Sales Excellence'          => 'Sales Excellence Program',
                                    'Customer Service Mastery'  => 'Customer Service Mastery',
                                    'Leadership Fundamentals'   => 'Leadership Fundamentals',
                                    'Operational Excellence'    => 'Operational Excellence',
                                    'Time Management'           => 'Time Management & Productivity',
                                    'Product Knowledge'         => 'Advanced Product Knowledge',
                                    'Inventory'                 => 'Inventory Management',
                                    'Mentoring'                 => 'Mentoring / Peer Support',
                                ];

                                $training_counts = [];
                                foreach ($report_data as $staff) {
                                    // Use training_recommendation first, fall back to supervisor_comment
                                    $text = $staff['training_recommendation'] ?: $staff['supervisor_comment'] ?? '';
                                    if (empty($text)) continue;

                                    $matched = false;
                                    foreach ($program_keywords as $keyword => $label) {
                                        if (stripos($text, $keyword) !== false) {
                                            $training_counts[$label] = ($training_counts[$label] ?? 0) + 1;
                                            $matched = true;
                                        }
                                    }
                                    // If no keyword matched, bucket as "General Development"
                                    if (!$matched) {
                                        $training_counts['General Development'] = ($training_counts['General Development'] ?? 0) + 1;
                                    }
                                }
                                arsort($training_counts);
                                ?>

                                <?php if (!empty($training_counts)): ?>
                                <div class="mt-4">
                                    <h6 class="fw-bold mb-3">
                                        <i class="bi bi-bar-chart-fill text-primary me-1"></i>
                                        Most Common Training Needs:
                                    </h6>
                                    <div class="list-group">
                                        <?php
                                        $total_staff = count($report_data);
                                        foreach (array_slice($training_counts, 0, 6) as $label => $count):
                                            $pct = $total_staff > 0 ? round(($count / $total_staff) * 100) : 0;
                                        ?>
                                            <div class="list-group-item px-3 py-2">
                                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                                    <span class="fw-semibold small"><?= htmlspecialchars($label) ?></span>
                                                    <span class="badge bg-primary ms-2"><?= $count ?> staff</span>
                                                </div>
                                                <div class="progress" style="height:6px;">
                                                    <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            const table = document.getElementById('reportTable');
            let csv = [];
            
            // Get headers
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            csv.push(headers.join(','));
            
            // Get rows
            table.querySelectorAll('tbody tr').forEach(tr => {
                const row = [];
                tr.querySelectorAll('td').forEach((td, index) => {
                    // Skip action column
                    if (index < headers.length - 1) {
                        row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
                    }
                });
                csv.push(row.join(','));
            });
            
            // Download
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = '<?= $report_type ?>_report_<?= $selected_year ?>.csv';
            a.click();
        }
    </script>
</body>
</html>
