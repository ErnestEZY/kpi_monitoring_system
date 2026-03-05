<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
$pdo = getDBConnection();

// Get current year
$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Dashboard - Sales Assistant Performance</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .score-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .score-excellent { background-color: #d4edda; color: #155724; }
        .score-good { background-color: #d1ecf1; color: #0c5460; }
        .score-satisfactory { background-color: #fff3cd; color: #856404; }
        .score-poor { background-color: #f8d7da; color: #721c24; }
        .score-very-poor { background-color: #f5c6cb; color: #721c24; }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        .btn-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
        }
        
        .btn-custom:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-speedometer2"></i> KPI Dashboard</h1>
                    <div>
                        <select id="yearFilter" class="form-select" style="width: 150px;">
                            <option value="2025" <?= $selected_year == 2025 ? 'selected' : '' ?>>2025</option>
                            <option value="2024" <?= $selected_year == 2024 ? 'selected' : '' ?>>2024</option>
                            <option value="2023" <?= $selected_year == 2023 ? 'selected' : '' ?>>2023</option>
                            <option value="2022" <?= $selected_year == 2022 ? 'selected' : '' ?>>2022</option>
                        </select>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row" id="statsCards">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                            <div class="stat-number text-primary" id="totalStaff">0</div>
                            <div class="stat-label">Total Staff</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="bi bi-trophy-fill text-success" style="font-size: 2rem;"></i>
                            <div class="stat-number text-success" id="topPerformers">0</div>
                            <div class="stat-label">Top Performers</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2rem;"></i>
                            <div class="stat-number text-warning" id="atRisk">0</div>
                            <div class="stat-label">At Risk</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="bi bi-graph-up-arrow text-info" style="font-size: 2rem;"></i>
                            <div class="stat-number text-info" id="avgScore">0.0</div>
                            <div class="stat-label">Average Score</div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-bar-chart-fill"></i> Performance Distribution
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="performanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-pie-chart-fill"></i> Score Categories
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Staff Performance Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table"></i> Staff Performance Overview
                    </div>
                    <div class="card-body">
                        <table id="staffTable" class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Staff Code</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Core Competencies</th>
                                    <th>KPI Achievement</th>
                                    <th>Final Score</th>
                                    <th>Rating</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Day.js -->
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.11.10/dayjs.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/kpi_dashboard.js"></script>
</body>
</html>
