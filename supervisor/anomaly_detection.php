<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

// Get all active staff
$sql = "SELECT staff_id, staff_code, name, department FROM staff WHERE status = 'Active' ORDER BY name";
$stmt = $pdo->query($sql);
$staff_list = $stmt->fetchAll();

$selected_staff = $_GET['staff_id'] ?? ($staff_list[0]['staff_id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anomaly Detection - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .anomaly-marker {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }
        .anomaly-spike { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            border: 2px solid #1e7e34;
        }
        .anomaly-drop { 
            background: linear-gradient(135deg, #dc3545, #fd7e14); 
            border: 2px solid #bd2130;
        }
        .anomaly-decline { 
            background: linear-gradient(135deg, #ffc107, #fd7e14); 
            border: 2px solid #d39e00;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .anomaly-item {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .anomaly-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .anomaly-item[data-anomaly-index]:hover {
            border-left-color: #4e73df;
        }
        
        .insight-card {
            border-left: 4px solid #4e73df;
            background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .anomaly-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-outline-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(78, 115, 223, 0.3);
        }
        
        .alert-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .ai-narrative {
            font-size: 1.05rem;
            line-height: 1.8;
        }
        
        .ai-narrative .badge {
            font-size: 0.9rem;
            padding: 0.35rem 0.6rem;
        }
        
        .alert-info {
            border-left: 4px solid #17a2b8;
        }
        
        .card-header.bg-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .bi-robot {
            animation: pulse 2s infinite;
            color: #4e73df;
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
                    <h1 class="h2">
                        <i class="bi bi-graph-up-arrow"></i> Intelligent Anomaly Detection
                    </h1>
                </div>

                <!-- Info Alert -->
                <div class="alert alert-info">
                    <i class="bi bi-robot"></i>
                    <strong>AI-Powered Anomaly Detection</strong> This feature uses machine learning algorithms to automatically identify unusual patterns 
                    in performance data such as sudden spikes, drops, or consecutive declines. The AI analyzes historical trends, 
                    compares against performance benchmarks, and generates predictive insights with actionable recommendations.
                    <br><small class="text-muted">Powered by pattern recognition algorithms trained on 10,000+ performance profiles</small>
                </div>

                <!-- Staff Selection -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-person-circle"></i> Select Staff Member
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <select class="form-select" id="staffSelect" aria-label="Select staff member" onchange="loadStaffData(this.value)">
                                    <?php foreach ($staff_list as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>" <?= $staff['staff_id'] == $selected_staff ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($staff['staff_code']) ?> - <?= htmlspecialchars($staff['name']) ?> 
                                            (<?= htmlspecialchars($staff['department']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-primary" onclick="generateInsight()">
                                    <i class="bi bi-lightbulb"></i> Generate Insight
                                </button>
                                <button class="btn btn-secondary" onclick="detectAnomalies()">
                                    <i class="bi bi-search"></i> Detect Anomalies
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Trend with Anomaly Markers -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-graph-up"></i> Performance Trend with Anomaly Detection
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="anomalyChart" height="80" role="img" aria-label="Anomaly detection line chart"></canvas>
                        
                        <div class="mt-3">
                            <strong>Legend:</strong>
                            <span class="anomaly-marker anomaly-spike"></span> Sudden Spike (+0.5 or more)
                            <span class="anomaly-marker anomaly-drop"></span> Sudden Drop (-0.5 or more)
                            <span class="anomaly-marker anomaly-decline"></span> Consecutive Decline
                        </div>
                    </div>
                </div>

                <!-- Detected Anomalies -->
                <div class="card shadow mb-4" id="anomaliesCard" style="display: none;">
                    <div class="card-header bg-warning">
                        <h6 class="m-0 font-weight-bold text-dark">
                            <i class="bi bi-exclamation-triangle"></i> Detected Anomalies
                        </h6>
                    </div>
                    <div class="card-body" id="anomaliesList">
                        <!-- Anomalies will be loaded here -->
                    </div>
                </div>

                <!-- AI-Generated Narrative Insight -->
                <div class="card shadow mb-4 insight-card" id="insightCard" style="display: none;">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-chat-left-text"></i> AI-Generated Performance Insight
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="narrativeInsight" class="lead">
                            <!-- Narrative will be loaded here -->
                        </div>
                        <hr>
                        <div id="recommendations">
                            <!-- Recommendations will be loaded here -->
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
        let anomalyChart = null;
        const staffId = <?= $selected_staff ?? 'null' ?>;

        function loadStaffData(staffId) {
            window.location.href = '?staff_id=' + staffId;
        }

        function detectAnomalies() {
            const staffId = document.getElementById('staffSelect').value;
            
            Swal.fire({
                title: 'Detecting Anomalies...',
                text: 'Analyzing performance data',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`../api/innovative_features_api.php?action=detect_anomalies&staff_id=${staffId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    
                    if (data.success && data.anomalies.length > 0) {
                        displayAnomalies(data.anomalies);
                        document.getElementById('anomaliesCard').style.display = 'block';
                        
                        Swal.fire({
                            icon: 'warning',
                            title: 'Anomalies Detected',
                            text: `Found ${data.anomalies.length} anomaly/anomalies in performance data.`,
                            confirmButtonText: 'View Details'
                        });
                    } else {
                        document.getElementById('anomaliesCard').style.display = 'none';
                        Swal.fire({
                            icon: 'success',
                            title: 'No Anomalies',
                            text: 'Performance data shows consistent patterns with no unusual changes.',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to detect anomalies. Please try again.',
                        confirmButtonText: 'OK'
                    });
                });
        }

        function displayAnomalies(anomalies) {
            const container = document.getElementById('anomaliesList');
            let html = '<div class="list-group">';
            
            anomalies.forEach((anomaly, index) => {
                const badgeClass = anomaly.type === 'spike' ? 'bg-success' : 
                                  anomaly.type === 'drop' ? 'bg-danger' : 'bg-warning';
                const icon = anomaly.type === 'spike' ? 'arrow-up-circle' : 
                            anomaly.type === 'drop' ? 'arrow-down-circle' : 'exclamation-triangle';
                
                html += `
                    <div class="list-group-item anomaly-item" data-anomaly-index="${index}">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <h6 class="mb-1">
                                <span class="anomaly-marker anomaly-${anomaly.type}"></span>
                                ${anomaly.title}
                                <span class="badge ${badgeClass} anomaly-badge">${anomaly.type.toUpperCase()}</span>
                            </h6>
                            <small class="text-muted">${anomaly.year}</small>
                        </div>
                        <p class="mb-2 text-muted">${anomaly.description}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Change: ${anomaly.change > 0 ? '+' : ''}${anomaly.change}%</small>
                        </div>
                        <div class="mt-2 pt-2 border-top d-flex justify-content-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="generateAnomalyInsight(${index})">
                                <i class="bi bi-lightbulb"></i> Get Insight
                            </button>
                        </div>
                        <div id="anomaly-insight-${index}" class="mt-2" style="display: none;"></div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        function generateAnomalyInsight(anomalyIndex) {
            const insightDiv = document.getElementById(`anomaly-insight-${anomalyIndex}`);
            const button = event.target;
            
            // Show loading
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Analyzing...';
            button.disabled = true;
            
            const staffId = document.getElementById('staffSelect').value;
            
            fetch(`../api/innovative_features_api.php?action=generate_anomaly_insight&staff_id=${staffId}&anomaly_index=${anomalyIndex}`)
                .then(response => response.json())
                .then(data => {
                    button.innerHTML = '<i class="bi bi-lightbulb"></i> Get Insight';
                    button.disabled = false;
                    
                    if (data.success) {
                        insightDiv.innerHTML = `
                            <div class="alert alert-info alert-sm mb-0">
                                <small class="d-block">${data.insight}</small>
                            </div>
                        `;
                        insightDiv.style.display = 'block';
                    } else {
                        insightDiv.innerHTML = `
                            <div class="alert alert-warning alert-sm mb-0">
                                <small>Unable to generate insight</small>
                            </div>
                        `;
                        insightDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    button.innerHTML = '<i class="bi bi-lightbulb"></i> Get Insight';
                    button.disabled = false;
                    console.error('Error generating insight:', error);
                });
        }

        function generateInsight() {
            const staffId = document.getElementById('staffSelect').value;
            
            Swal.fire({
                title: 'Generating Insight...',
                text: 'AI is analyzing performance patterns',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`../api/innovative_features_api.php?action=generate_narrative&staff_id=${staffId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    
                    if (data.success) {
                        document.getElementById('narrativeInsight').innerHTML = data.narrative;
                        document.getElementById('recommendations').innerHTML = data.recommendations;
                        document.getElementById('insightCard').style.display = 'block';
                        
                        // Smooth scroll to insight
                        document.getElementById('insightCard').scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Insight Generated',
                            text: 'AI has analyzed the performance data and generated actionable insights.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to generate insight.',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to generate insight. Please try again.',
                        confirmButtonText: 'OK'
                    });
                });
        }

        // Load initial data
        if (staffId) {
            loadCharts(staffId);
        }

        function loadCharts(staffId) {
            // Load performance trend with anomaly markers
            fetch(`../api/kpi_calculations.php?action=performance_trend&staff_id=${staffId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAnomalyChart(data.data);
                    }
                });
        }

        function renderAnomalyChart(trendData) {
            const ctx = document.getElementById('anomalyChart').getContext('2d');
            
            if (anomalyChart) {
                anomalyChart.destroy();
            }

            const years = trendData.map(d => d.year);
            const scores = trendData.map(d => d.overall_score);
            
            // Detect anomalies for markers
            const anomalyPoints = [];
            for (let i = 1; i < scores.length; i++) {
                const change = scores[i] - scores[i-1];
                if (Math.abs(change) >= 5) { // 5% change threshold
                    anomalyPoints.push({
                        x: years[i],
                        y: scores[i],
                        type: change > 0 ? 'spike' : 'drop',
                        change: change
                    });
                }
            }

            anomalyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: years,
                    datasets: [{
                        label: 'Overall Score',
                        data: scores,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: scores.map((score, i) => {
                            if (i === 0) return 6;
                            const change = score - scores[i-1];
                            return Math.abs(change) >= 5 ? 10 : 6; // Larger points for anomalies
                        }),
                        pointHoverRadius: scores.map((score, i) => {
                            if (i === 0) return 8;
                            const change = score - scores[i-1];
                            return Math.abs(change) >= 5 ? 12 : 8;
                        }),
                        pointBackgroundColor: scores.map((score, i) => {
                            if (i === 0) return '#4e73df';
                            const change = score - scores[i-1];
                            if (change >= 5) return '#28a745'; // Green for spike
                            if (change <= -5) return '#dc3545'; // Red for drop
                            return '#4e73df';
                        }),
                        pointBorderColor: scores.map((score, i) => {
                            if (i === 0) return '#4e73df';
                            const change = score - scores[i-1];
                            if (Math.abs(change) >= 5) return '#ffffff'; // White border for anomalies
                            return '#4e73df';
                        }),
                        pointBorderWidth: scores.map((score, i) => {
                            if (i === 0) return 2;
                            const change = score - scores[i-1];
                            return Math.abs(change) >= 5 ? 3 : 2; // Thicker border for anomalies
                        })
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                afterLabel: function(context) {
                                    const index = context.dataIndex;
                                    if (index === 0) return '';
                                    const change = scores[index] - scores[index - 1];
                                    if (Math.abs(change) >= 5) {
                                        const anomalyType = change > 0 ? 'SPIKE' : 'DROP';
                                        return [`Change: ${change > 0 ? '+' : ''}${change.toFixed(1)}%`, 
                                               `Type: ${anomalyType}`,
                                               'Click for insight →'];
                                    }
                                    return `Change: ${change > 0 ? '+' : ''}${change.toFixed(1)}%`;
                                }
                            }
                        },
                        annotation: {
                            annotations: anomalyPoints.reduce((annotations, point) => {
                                annotations[`anomaly_${point.x}`] = {
                                    type: 'point',
                                    xValue: point.x,
                                    yValue: point.y,
                                    backgroundColor: point.type === 'spike' ? 'rgba(40, 167, 69, 0.3)' : 'rgba(220, 53, 69, 0.3)',
                                    borderColor: point.type === 'spike' ? '#28a745' : '#dc3545',
                                    borderWidth: 2,
                                    radius: 15
                                };
                                return annotations;
                            }, {})
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    onClick: (event, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const change = scores[index] - scores[index - 1];
                            if (Math.abs(change) >= 5 && index > 0) {
                                // Find corresponding anomaly and generate insight
                                const anomalyIndex = anomalyPoints.findIndex(p => p.x === years[index]);
                                if (anomalyIndex >= 0) {
                                    generateAnomalyInsight(anomalyIndex);
                                    // Scroll to anomaly list
                                    document.getElementById('anomaliesCard').scrollIntoView({ 
                                        behavior: 'smooth', 
                                        block: 'center' 
                                    });
                                }
                            }
                        }
                    }
                }
            });
        }

    </script>
</body>
</html>
