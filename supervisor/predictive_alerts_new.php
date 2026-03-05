<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictive Performance Risk Alerts</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <style>
        .alert-card {
            border-left: 4px solid;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .alert-card:hover {
            transform: translateX(5px);
        }
        
        .alert-critical {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        
        .alert-high {
            border-left-color: #fd7e14;
            background-color: #ffe5d0;
        }
        
        .alert-medium {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        
        .alert-low {
            border-left-color: #0dcaf0;
            background-color: #cff4fc;
        }
        
        .risk-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .risk-critical { background-color: #dc3545; color: white; }
        .risk-high { background-color: #fd7e14; color: white; }
        .risk-medium { background-color: #ffc107; color: #000; }
        .risk-low { background-color: #0dcaf0; color: #000; }
        
        .trend-icon {
            font-size: 1.5rem;
        }
        
        .trend-down { color: #dc3545; }
        .trend-up { color: #28a745; }
        .trend-stable { color: #6c757d; }
        
        .prediction-chart {
            height: 200px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
                    <div>
                        <h2><i class="bi bi-exclamation-triangle-fill text-warning"></i> Predictive Performance Risk Alerts</h2>
                        <p class="text-muted">AI-powered early warning system for performance issues</p>
                    </div>
                    <button class="btn btn-primary" onclick="refreshAlerts()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Alerts
                    </button>
                </div>
                
                <!-- Risk Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size: 2rem;"></i>
                                <h3 class="mt-2 text-danger" id="criticalCount">0</h3>
                                <p class="text-muted mb-0">Critical Risk</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 2rem;"></i>
                                <h3 class="mt-2 text-warning" id="highCount">0</h3>
                                <p class="text-muted mb-0">High Risk</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-circle-fill text-info" style="font-size: 2rem;"></i>
                                <h3 class="mt-2 text-info" id="mediumCount">0</h3>
                                <p class="text-muted mb-0">Medium Risk</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                                <h3 class="mt-2 text-success" id="lowCount">0</h3>
                                <p class="text-muted mb-0">Low Risk</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alerts List -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bell-fill"></i> Active Risk Alerts</h5>
                    </div>
                    <div class="card-body" id="alertsContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Analyzing performance data...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            loadPredictiveAlerts();
        });
        
        function loadPredictiveAlerts() {
            $.ajax({
                url: '../api/innovative_features_api.php',
                method: 'GET',
                data: { action: 'get_predictive_alerts' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayAlerts(response.data);
                    }
                },
                error: function() {
                    $('#alertsContainer').html('<div class="alert alert-danger">Error loading alerts</div>');
                }
            });
        }
        
        function displayAlerts(data) {
            // Update counts
            $('#criticalCount').text(data.summary.critical);
            $('#highCount').text(data.summary.high);
            $('#mediumCount').text(data.summary.medium);
            $('#lowCount').text(data.summary.low);
            
            // Display alerts
            let html = '';
            
            if (data.alerts.length === 0) {
                html = '<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>No critical risk alerts at this time. All staff performing within acceptable ranges.</div>';
            } else {
                data.alerts.forEach(alert => {
                    html += `
                        <div class="card alert-card alert-${alert.risk_level}">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-1 text-center">
                                        <i class="bi ${alert.icon} trend-icon trend-${alert.trend}"></i>
                                    </div>
                                    <div class="col-md-7">
                                        <h6 class="mb-1">${alert.staff_name} (${alert.staff_code})</h6>
                                        <p class="mb-1">${alert.message}</p>
                                        <small class="text-muted">
                                            <i class="bi bi-graph-down"></i> Current: ${alert.current_score} | 
                                            <i class="bi bi-graph-up"></i> Predicted: ${alert.predicted_score} | 
                                            <i class="bi bi-calendar"></i> ${alert.timeframe}
                                        </small>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="risk-badge risk-${alert.risk_level}">${alert.risk_label}</span>
                                        <div class="mt-2">
                                            <small class="text-muted">Confidence: ${alert.confidence}%</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button class="btn btn-sm btn-primary" onclick="viewDetails(${alert.staff_id})">
                                            <i class="bi bi-eye"></i> Details
                                        </button>
                                        <button class="btn btn-sm btn-warning mt-1" onclick="takeAction(${alert.staff_id})">
                                            <i class="bi bi-lightning-fill"></i> Action
                                        </button>
                                    </div>
                                </div>
                                ${alert.recommendations ? `
                                    <div class="mt-3 pt-3 border-top">
                                        <strong><i class="bi bi-lightbulb-fill text-warning"></i> Recommended Actions:</strong>
                                        <ul class="mb-0 mt-2">
                                            ${alert.recommendations.map(r => `<li>${r}</li>`).join('')}
                                        </ul>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
            }
            
            $('#alertsContainer').html(html);
        }
        
        function refreshAlerts() {
            $('#alertsContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Refreshing alerts...</p></div>');
            loadPredictiveAlerts();
        }
        
        function viewDetails(staffId) {
            window.location.href = `staff_profile.php?id=${staffId}`;
        }
        
        function takeAction(staffId) {
            Swal.fire({
                title: 'Take Action',
                html: `
                    <div class="text-start">
                        <p>Select an action to address this performance risk:</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="action" id="action1" value="schedule_meeting" checked>
                            <label class="form-check-label" for="action1">Schedule 1-on-1 Meeting</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="action" id="action2" value="assign_training">
                            <label class="form-check-label" for="action2">Assign Training Program</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="action" id="action3" value="performance_plan">
                            <label class="form-check-label" for="action3">Create Performance Improvement Plan</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="action" id="action4" value="mentor">
                            <label class="form-check-label" for="action4">Assign Mentor</label>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Proceed',
                confirmButtonColor: '#667eea'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Action Scheduled',
                        text: 'The selected action has been scheduled and the staff member will be notified.',
                        timer: 2000
                    });
                }
            });
        }
    </script>
</body>
</html>
