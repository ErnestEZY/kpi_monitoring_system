<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

// Get available years
// Fixed year range: 2026 down to 2021
$available_years = range(2026, 2021);

// Get unique departments from staff table
$sql = "SELECT DISTINCT department FROM staff WHERE department IS NOT NULL ORDER BY department";
$stmt = $pdo->query($sql);
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Analytics - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .filter-card .form-label {
            color: white;
            font-weight: 600;
        }
        .filter-card .form-control {
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.9);
        }
        .filter-card .form-select {
            border: 2px solid rgba(255,255,255,0.3);
            background-color: rgba(255,255,255,0.9);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.25rem;
        }
        .chart-container {
            position: relative;
            height: 280px;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
                    <h1 class="h2"><i class="bi bi-bar-chart-line"></i> Interactive Analytics Dashboard</h1>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filters & Controls</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <select class="form-select" id="filterYear">
                                <?php foreach ($available_years as $year): ?>
                                    <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <select class="form-select" id="filterDepartment">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept) ?>">
                                        <?= htmlspecialchars($dept) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Performance Level</label>
                            <select class="form-select" id="filterPerformance">
                                <option value="">All Levels</option>
                                <option value="top">Top Performer (≥85%)</option>
                                <option value="good">Good Performer (75-84%)</option>
                                <option value="satisfactory">Satisfactory (65-74%)</option>
                                <option value="needs_improvement">Needs Improvement (50-64%)</option>
                                <option value="critical">Critical (<50%)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-light w-100" onclick="applyFilters()">
                                <i class="bi bi-search"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row mb-4" id="keyMetrics">
                    <div class="col-md-3">
                        <div class="card stat-card shadow">
                            <div class="card-body text-center">
                                <i class="bi bi-people fs-1 text-primary"></i>
                                <h3 class="mt-2" id="totalStaff">-</h3>
                                <p class="text-muted mb-0">Total Staff</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card shadow">
                            <div class="card-body text-center">
                                <i class="bi bi-graph-up fs-1 text-success"></i>
                                <h3 class="mt-2" id="avgScore">-</h3>
                                <p class="text-muted mb-0">
                                    Average Score
                                    <i class="bi bi-info-circle text-primary" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       title="Calculated as: Sum of all staff overall scores ÷ Number of staff with data. Overall score = Weighted average of all 21 KPI scores (each KPI score 1-5, multiplied by its weight percentage)."></i>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card shadow">
                            <div class="card-body text-center">
                                <i class="bi bi-trophy fs-1 text-warning"></i>
                                <h3 class="mt-2" id="topPerformers">-</h3>
                                <p class="text-muted mb-0">Top Performers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card shadow">
                            <div class="card-body text-center">
                                <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                                <h3 class="mt-2" id="atRisk">-</h3>
                                <p class="text-muted mb-0">At-Risk Staff</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="row mb-4">
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-pie-chart"></i> Performance Distribution
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="distributionChart"></canvas>
                                </div>
                                <div class="mt-1 p-2 bg-light rounded">
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Insight:</strong> 
                                        <span id="distributionStory">This chart shows how your team is distributed across performance levels. A healthy team typically has most staff in "Good Performer" or above categories.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-bar-chart"></i> Department Comparison
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="departmentChart"></canvas>
                                </div>
                                <div class="mt-1 p-2 bg-light rounded">
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Insight:</strong> 
                                        <span id="departmentStory">Compare average performance across departments. Departments with lower scores may need additional training resources or process improvements.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row mb-4">
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-graph-up-arrow"></i> Multi-Year Trend Analysis
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="trendChart"></canvas>
                                </div>
                                <div class="mt-1 p-2 bg-light rounded">
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Insight:</strong> 
                                        <span id="trendStory">Track performance evolution over multiple years. Upward trends indicate successful training programs, while declining trends signal need for intervention.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-speedometer2"></i> KPI Category Averages
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                                <div class="mt-1 p-2 bg-light rounded">
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Insight:</strong> 
                                        <span id="categoryStory">Identify which KPI categories your team excels in and which need improvement. Use this to target training programs effectively.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 3 -->
                <div class="row mb-4">
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-bar-chart-fill"></i> Score Distribution Analysis
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="boxPlotChart"></canvas>
                                </div>
                                <div class="mt-1 p-2 bg-light rounded">
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Insight:</strong> 
                                        <span id="boxPlotStory">This distribution shows score concentration. A balanced distribution suggests consistent performance standards, while clustering at extremes may indicate training gaps.</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-table"></i> Detailed Staff Data
                                </h6>
                                <small class="text-muted">Click "View" button to see individual staff profile with complete KPI breakdown</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-hover" id="staffTable">
                                        <thead>
                                            <tr>
                                                <th>Staff ID</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Overall Score</th>
                                                <th>Classification</th>
                                                <th>Trend</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="staffTableBody">
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/analytics.js"></script>
</body>
</html>

