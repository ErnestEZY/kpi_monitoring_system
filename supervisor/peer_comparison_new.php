<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
$pdo = getDBConnection();

// Get staff list for dropdown
$stmt = $pdo->query("SELECT staff_id, staff_code, name, department FROM staff WHERE status = 'Active' ORDER BY name");
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available years
// Fixed year range: 2026 down to 2021
$years = range(2026, 2021);

$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligent Peer Comparison</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <style>
        .comparison-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s;
            border: 3px solid transparent;
        }
        
        .comparison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .comparison-card.staff-selected {
            border-color: #667eea;
            background: linear-gradient(to bottom, #f8f9ff 0%, #ffffff 100%);
        }
        
        .comparison-card.peer-selected {
            border-color: #28a745;
            background: linear-gradient(to bottom, #f0fff4 0%, #ffffff 100%);
        }
        
        .staff-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 15px;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .staff-avatar.peer-avatar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .vs-container {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 20px 0;
        }
        
        .vs-badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(240, 147, 251, 0.4);
            animation: pulse 2s infinite;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .vs-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, #667eea 0%, #f093fb 50%, #28a745 100%);
            top: 50%;
            z-index: -1;
        }
        
        .score-display {
            font-size: 3rem;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .score-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .staff-info {
            padding: 20px;
        }
        
        .staff-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .staff-dept {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .radar-container {
            height: 450px;
            position: relative;
            padding: 20px;
        }
        
        .peer-suggestion {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 12px;
            transition: all 0.3s;
            background: white;
        }
        
        .peer-suggestion:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
            transform: translateX(5px);
        }
        
        .peer-suggestion .similarity-badge {
            font-size: 1.1rem;
            padding: 8px 16px;
            border-radius: 20px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .insight-item {
            padding: 12px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
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
                    <h1 class="h2"><i class="bi bi-people"></i> Intelligent Peer Comparison</h1>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> 
                    <strong>Advanced Analytics:</strong> AI-powered performance comparison with similar peers
                </div>
                
                <!-- Staff Selection -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Evaluation Year:</label>
                                <select class="form-select" id="yearSelect">
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Select Staff Member:</label>
                                <select class="form-select" id="staffSelect">
                                    <option value="">Choose staff...</option>
                                    <?php foreach ($staff_list as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>"><?= htmlspecialchars($staff['name']) ?> (<?= htmlspecialchars($staff['staff_code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Compare With:</label>
                                <select class="form-select" id="compareWithSelect" onchange="toggleSpecificStaffSelect()">
                                    <option value="auto">Auto-select Best Match</option>
                                    <option value="top">Top Performer</option>
                                    <option value="avg">Team Average</option>
                                    <option value="specific">Specific Staff Member</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="specificStaffContainer" style="display: none;">
                                <label class="form-label">Select Peer:</label>
                                <select class="form-select" id="specificStaffSelect">
                                    <option value="">Choose peer...</option>
                                    <?php foreach ($staff_list as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>"><?= htmlspecialchars($staff['name']) ?> (<?= htmlspecialchars($staff['staff_code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="loadComparison()">
                                    <i class="bi bi-arrow-left-right"></i> Compare
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Comparison Results -->
                <div id="comparisonResults">
                    <div class="alert alert-secondary text-center py-5">
                        <i class="bi bi-arrow-up-circle fs-1"></i>
                        <h4 class="mt-3">Select Staff Members to Compare</h4>
                        <p class="text-muted">Choose staff members above to see detailed performance comparison.</p>
                    </div>
                </div>
                
                <!-- Similar Peers Suggestions -->
                <div class="card mt-4" id="similarPeersCard" style="display: none;">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0"><i class="bi bi-people-fill"></i> Other Similar Peers - Quick Compare</h5>
                        <small>Click "Compare Now" to see detailed comparison with any peer</small>
                    </div>
                    <div class="card-body" id="similarPeers">
                        <!-- Loaded via AJAX -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5>Analyzing Performance Data</h5>
            <p class="text-muted">Please wait while we process the comparison...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let radarChart = null;
        
        // Toggle specific staff select
        function toggleSpecificStaffSelect() {
            const compareWith = $('#compareWithSelect').val();
            if (compareWith === 'specific') {
                $('#specificStaffContainer').show();
            } else {
                $('#specificStaffContainer').hide();
            }
        }
        
        // Simple loading functions
        function showLoading() {
            $('#loadingOverlay').show();
        }
        
        function hideLoading() {
            $('#loadingOverlay').hide();
        }
        
        // Load comparison data
        function loadComparison() {
            const staffId = $('#staffSelect').val();
            const compareWith = $('#compareWithSelect').val();
            const year = $('#yearSelect').val();
            
            if (!staffId || !year) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select staff member and year'
                });
                return;
            }
            
            if (compareWith === 'specific') {
                const peerId = $('#specificStaffSelect').val();
                if (!peerId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select a peer to compare with'
                    });
                    return;
                }
            }
            
            showLoading();
            
            const requestData = {
                action: 'get_peer_comparison',
                staff_id: staffId,
                year: year
            };
            
            if (compareWith === 'specific') {
                requestData.compare_with = 'manual';
                requestData.manual_peer_id = $('#specificStaffSelect').val();
            } else {
                requestData.compare_with = compareWith;
            }
            
            // Simple AJAX call with timeout
            $.ajax({
                url: '../api/innovative_features_api.php',
                method: 'GET',
                timeout: 15000, // 15 second timeout
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        displayComparisonData(response.data);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to load comparison data'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    console.error('AJAX Error:', error);
                    let errorMessage = 'Failed to connect to server';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'API endpoint not found.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: errorMessage
                    });
                }
            });
        }
        
        // Display comparison data
        function displayComparisonData(data) {
            let html = `
                <div class="row mb-4">
                    <!-- Staff 1 Card -->
                    <div class="col-md-5">
                        <div class="comparison-card staff-selected">
                            <div class="card-body text-center staff-info">
                                <div class="staff-avatar">${data.staff1.name.charAt(0)}</div>
                                <div class="staff-name">${data.staff1.name}</div>
                                <div class="staff-dept">${data.staff1.department}</div>
                                <div class="score-display text-primary">${data.staff1.score}%</div>
                                <div class="score-label">Overall Performance</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- VS Badge -->
                    <div class="col-md-2">
                        <div class="vs-container">
                            <div class="vs-line"></div>
                            <div class="vs-badge">VS</div>
                        </div>
                    </div>
                    
                    <!-- Staff 2 Card -->
                    <div class="col-md-5">
                        <div class="comparison-card peer-selected">
                            <div class="card-body text-center staff-info">
                                <div class="staff-avatar peer-avatar">${data.staff2.name.charAt(0)}</div>
                                <div class="staff-name">${data.staff2.name}</div>
                                <div class="staff-dept">${data.staff2.department}</div>
                                <div class="score-display text-success">${data.staff2.score}%</div>
                                <div class="score-label">Overall Performance</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add insights with better styling
            if (data.insights && data.insights.length > 0) {
                html += `
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-lightbulb-fill"></i> AI-Powered Performance Insights</h5>
                        </div>
                        <div class="card-body">
                `;
                
                data.insights.forEach(insight => {
                    html += `<div class="insight-item"><i class="bi bi-check-circle-fill text-success me-2"></i> ${insight}</div>`;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Add radar chart with larger size
            if (data.radar_data) {
                html += `
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-radar"></i> Detailed Performance Comparison by Category</h5>
                        </div>
                        <div class="card-body">
                            <div class="radar-container">
                                <canvas id="radarChart"></canvas>
                            </div>
                            <div class="text-center mt-3">
                                <span class="badge bg-primary me-2"><i class="bi bi-circle-fill"></i> ${data.staff1.name}</span>
                                <span class="badge bg-success"><i class="bi bi-circle-fill"></i> ${data.staff2.name}</span>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            $('#comparisonResults').html(html);
            
            // Create radar chart if data available
            if (data.radar_data) {
                createRadarChart(data.radar_data);
            }
            
            // Show similar peers
            if (data.similar_peers && data.similar_peers.length > 0) {
                displaySimilarPeers(data.similar_peers);
                $('#similarPeersCard').show();
            }
        }
        
        // Create radar chart
        function createRadarChart(radarData) {
            const ctx = document.getElementById('radarChart');
            
            if (radarChart) {
                radarChart.destroy();
            }
            
            radarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: radarData.labels,
                    datasets: [
                        {
                            label: radarData.staff1_name,
                            data: radarData.staff1_values,
                            backgroundColor: 'rgba(102, 126, 234, 0.3)',
                            borderColor: '#667eea',
                            borderWidth: 3,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#667eea',
                            pointHoverRadius: 7
                        },
                        {
                            label: radarData.staff2_name,
                            data: radarData.staff2_values,
                            backgroundColor: 'rgba(40, 167, 69, 0.3)',
                            borderColor: '#28a745',
                            borderWidth: 3,
                            pointBackgroundColor: '#28a745',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#28a745',
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 5,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 12
                                }
                            },
                            pointLabels: {
                                font: {
                                    size: 13,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    }
                }
            });
        }
        
        // Display similar peers WITH COMPARE BUTTONS
        function displaySimilarPeers(peers) {
            let html = '';
            peers.forEach(peer => {
                html += `
                    <div class="peer-suggestion">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <div class="d-flex align-items-center">
                                    <div class="staff-avatar me-3" style="width: 50px; height: 50px; font-size: 1.2rem; margin: 0; flex-shrink: 0;">
                                        ${peer.name.charAt(0)}
                                    </div>
                                    <div style="text-align: left;">
                                        <strong style="font-size: 1.1rem;">${peer.name}</strong>
                                        <span class="text-muted ms-2">(${peer.staff_code})</span>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-building"></i> ${peer.department} | 
                                            <i class="bi bi-graph-up"></i> Score: ${peer.score}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <span class="badge bg-primary similarity-badge">
                                    <i class="bi bi-percent"></i> ${peer.similarity}% Match
                                </span>
                            </div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-outline-primary btn-sm w-100" onclick="compareWithPeer(${peer.staff_id})" style="font-weight: 600;">
                                    <i class="bi bi-arrow-left-right"></i> Compare Now
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#similarPeers').html(html);
        }
        
        // Compare with peer function
        function compareWithPeer(peerId) {
            // Get current staff and year
            const currentStaffId = $('#staffSelect').val();
            const year = $('#yearSelect').val();
            
            console.log('Comparing with peer:', { currentStaffId, peerId, year });
            
            // Load comparison with selected peer
            showLoading();
            
            $.ajax({
                url: '../api/innovative_features_api.php',
                method: 'GET',
                timeout: 15000,
                data: {
                    action: 'get_peer_comparison',
                    staff_id: currentStaffId,
                    compare_with: 'manual',
                    manual_peer_id: peerId,
                    year: year
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    console.log('Manual comparison response:', response);
                    
                    if (response.success) {
                        displayComparisonData(response.data);
                        
                        // Scroll to top of comparison
                        $('html, body').animate({
                            scrollTop: $('#comparisonResults').offset().top - 100
                        }, 500);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Comparison Updated',
                            text: 'Now comparing with selected peer',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to load comparison data'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    console.error('Manual comparison error:', { xhr, status, error });
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load comparison. Please try again.'
                    });
                }
            });
        }
        
        // Initialize page
        $(document).ready(function() {
            // Page is ready - no initial loading needed
            console.log('Peer Comparison page loaded successfully');
        });
    </script>
</body>
</html>
