<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
$pdo = getDBConnection();

// Get staff list
$sql = "SELECT staff_id, staff_code, name, department FROM staff WHERE status = 'Active' ORDER BY name";
$stmt = $pdo->query($sql);
$staff_list = $stmt->fetchAll();

// Get KPI master list grouped by section
$sql = "SELECT * FROM kpi_master ORDER BY section_number, display_order";
$stmt = $pdo->query($sql);
$kpi_list = $stmt->fetchAll();

// Group KPIs by section
$kpis_by_section = [];
foreach ($kpi_list as $kpi) {
    $kpis_by_section[$kpi['section']][] = $kpi;
}

// Get selected staff and year
$selected_staff_id = $_GET['staff_id'] ?? '';
$selected_year = $_GET['year'] ?? date('Y');

// Get existing scores if staff is selected
$existing_scores = [];
if ($selected_staff_id) {
    $sql = "SELECT kpi_code, score FROM kpi_scores 
            WHERE staff_id = ? AND evaluation_year = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selected_staff_id, $selected_year]);
    $existing_scores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Get available years
$years = range(date('Y'), date('Y') - 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Score Entry - KPI Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .kpi-section {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .kpi-item {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .kpi-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .score-input {
            width: 80px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .score-buttons {
            display: flex;
            gap: 5px;
        }
        
        .score-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: bold;
        }
        
        .score-btn:hover {
            transform: scale(1.1);
        }
        
        .score-btn.active {
            border-width: 3px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        
        .score-btn[data-score="5"] { border-color: #28a745; color: #28a745; }
        .score-btn[data-score="5"].active { background: #28a745; color: white; }
        
        .score-btn[data-score="4"] { border-color: #17a2b8; color: #17a2b8; }
        .score-btn[data-score="4"].active { background: #17a2b8; color: white; }
        
        .score-btn[data-score="3"] { border-color: #ffc107; color: #ffc107; }
        .score-btn[data-score="3"].active { background: #ffc107; color: white; }
        
        .score-btn[data-score="2"] { border-color: #fd7e14; color: #fd7e14; }
        .score-btn[data-score="2"].active { background: #fd7e14; color: white; }
        
        .score-btn[data-score="1"] { border-color: #dc3545; color: #dc3545; }
        .score-btn[data-score="1"].active { background: #dc3545; color: white; }
        
        .score-legend {
            display: flex;
            justify-content: space-around;
            padding: 15px;
            background: white;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .legend-item {
            text-align: center;
        }
        
        .summary-card {
            position: sticky;
            top: 20px;
        }
        
        .progress-ring {
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        
        .weight-badge {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
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
                    <h1 class="h2"><i class="bi bi-pencil-square"></i> KPI Score Entry</h1>
                    <div class="btn-toolbar">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Selection Form -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3" id="selectionForm">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Select Staff Member *</label>
                                <select name="staff_id" class="form-select" required onchange="this.form.submit()">
                                    <option value="">-- Choose Staff --</option>
                                    <?php foreach ($staff_list as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>" 
                                                <?= $selected_staff_id == $staff['staff_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($staff['staff_code']) ?> - <?= htmlspecialchars($staff['name']) ?> 
                                            (<?= htmlspecialchars($staff['department']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Evaluation Year *</label>
                                <select name="year" class="form-select" required onchange="this.form.submit()">
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_staff_id): ?>
                    <?php
                    // Get selected staff info
                    $sql = "SELECT * FROM staff WHERE staff_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$selected_staff_id]);
                    $staff_info = $stmt->fetch();
                    ?>

                    <div class="row">
                        <!-- KPI Entry Form -->
                        <div class="col-lg-8">
                            <!-- Score Legend -->
                            <div class="score-legend shadow-sm">
                                <div class="legend-item">
                                    <div class="score-btn" data-score="5" style="pointer-events: none;">5</div>
                                    <small class="d-block mt-2 fw-bold text-success">Excellent</small>
                                    <small class="text-muted">Outstanding</small>
                                </div>
                                <div class="legend-item">
                                    <div class="score-btn" data-score="4" style="pointer-events: none;">4</div>
                                    <small class="d-block mt-2 fw-bold text-info">Good</small>
                                    <small class="text-muted">Above Average</small>
                                </div>
                                <div class="legend-item">
                                    <div class="score-btn" data-score="3" style="pointer-events: none;">3</div>
                                    <small class="d-block mt-2 fw-bold text-warning">Satisfactory</small>
                                    <small class="text-muted">Meets Expectations</small>
                                </div>
                                <div class="legend-item">
                                    <div class="score-btn" data-score="2" style="pointer-events: none;">2</div>
                                    <small class="d-block mt-2 fw-bold" style="color: #fd7e14;">Poor</small>
                                    <small class="text-muted">Below Average</small>
                                </div>
                                <div class="legend-item">
                                    <div class="score-btn" data-score="1" style="pointer-events: none;">1</div>
                                    <small class="d-block mt-2 fw-bold text-danger">Very Poor</small>
                                    <small class="text-muted">Unsatisfactory</small>
                                </div>
                            </div>

                            <form id="kpiScoreForm">
                                <input type="hidden" name="staff_id" value="<?= $selected_staff_id ?>">
                                <input type="hidden" name="evaluation_year" value="<?= $selected_year ?>">
                                <input type="hidden" name="evaluation_date" value="<?= date('Y-m-d') ?>">

                                <?php foreach ($kpis_by_section as $section => $kpis): ?>
                                    <div class="kpi-section">
                                        <h5 class="mb-3">
                                            <i class="bi bi-folder-fill text-primary"></i> 
                                            <?= htmlspecialchars($section) ?>
                                        </h5>

                                        <?php foreach ($kpis as $kpi): ?>
                                            <div class="kpi-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <strong><?= htmlspecialchars($kpi['kpi_code']) ?></strong>
                                                            <span class="weight-badge">
                                                                <?= number_format($kpi['weight_percentage'], 2) ?>%
                                                            </span>
                                                        </div>
                                                        <p class="mb-0 text-muted small">
                                                            <?= htmlspecialchars($kpi['kpi_description']) ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-end align-items-center gap-3">
                                                            <div class="score-buttons">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <button type="button" 
                                                                            class="score-btn <?= (isset($existing_scores[$kpi['kpi_code']]) && $existing_scores[$kpi['kpi_code']] == $i) ? 'active' : '' ?>" 
                                                                            data-score="<?= $i ?>"
                                                                            data-kpi="<?= $kpi['kpi_code'] ?>"
                                                                            data-weight="<?= $kpi['weight_percentage'] ?>"
                                                                            onclick="selectScore(this)">
                                                                        <?= $i ?>
                                                                    </button>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <input type="hidden" 
                                                                   name="scores[<?= $kpi['kpi_code'] ?>]" 
                                                                   id="score_<?= $kpi['kpi_code'] ?>"
                                                                   value="<?= $existing_scores[$kpi['kpi_code']] ?? '' ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-save"></i> Save All Scores
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Summary Sidebar -->
                        <div class="col-lg-4">
                            <div class="card shadow summary-card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-calculator"></i> Score Summary</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Staff Info -->
                                    <div class="text-center mb-4">
                                        <div class="profile-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                            <?php
                                            $names = explode(' ', $staff_info['name']);
                                            $initials = '';
                                            foreach ($names as $name) {
                                                $initials .= strtoupper(substr($name, 0, 1));
                                            }
                                            echo $initials;
                                            ?>
                                        </div>
                                        <h5 class="mb-1"><?= htmlspecialchars($staff_info['name']) ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars($staff_info['staff_code']) ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($staff_info['department']) ?></small>
                                    </div>

                                    <hr>

                                    <!-- Score Progress -->
                                    <div class="text-center mb-4">
                                        <canvas id="scoreChart" width="150" height="150"></canvas>
                                        <h2 class="mt-3 mb-0" id="totalScore">0.0</h2>
                                        <small class="text-muted">Overall Score</small>
                                    </div>

                                    <hr>

                                    <!-- Score Breakdown -->
                                    <div id="scoreBreakdown">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Scores Entered:</span>
                                            <strong><span id="scoresEntered">0</span> / <?= count($kpi_list) ?></strong>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                                        </div>

                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Weighted Score:</span>
                                            <strong id="weightedScore">0.00</strong>
                                        </div>

                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Classification:</span>
                                            <span class="badge bg-secondary" id="classification">Not Rated</span>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Quick Actions -->
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllScores()">
                                            <i class="bi bi-x-circle"></i> Clear All
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="viewHistory()">
                                            <i class="bi bi-clock-history"></i> View History
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Please select a staff member and evaluation year to begin entering KPI scores.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let scoreChart;

        // Initialize chart
        function initChart() {
            const ctx = document.getElementById('scoreChart');
            if (!ctx) return;

            scoreChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: ['#667eea', '#e9ecef'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }

        // Select score
        function selectScore(button) {
            const score = button.dataset.score;
            const kpiCode = button.dataset.kpi;
            
            // Remove active class from siblings
            const siblings = button.parentElement.querySelectorAll('.score-btn');
            siblings.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Update hidden input
            document.getElementById('score_' + kpiCode).value = score;
            
            // Update summary
            updateSummary();
        }

        // Update summary
        function updateSummary() {
            const form = document.getElementById('kpiScoreForm');
            const inputs = form.querySelectorAll('input[name^="scores"]');
            
            let totalScore = 0;
            let totalWeight = 0;
            let scoresEntered = 0;
            
            inputs.forEach(input => {
                if (input.value) {
                    scoresEntered++;
                    const score = parseFloat(input.value);
                    const kpiCode = input.name.match(/\[(.*?)\]/)[1];
                    const button = document.querySelector(`[data-kpi="${kpiCode}"][data-score="${score}"]`);
                    const weight = parseFloat(button.dataset.weight);
                    
                    totalScore += (score / 5) * weight;
                    totalWeight += weight;
                }
            });
            
            const overallScore = totalWeight > 0 ? (totalScore / totalWeight) * 100 : 0;
            const progress = (scoresEntered / inputs.length) * 100;
            
            // Update UI
            document.getElementById('scoresEntered').textContent = scoresEntered;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('totalScore').textContent = overallScore.toFixed(1);
            document.getElementById('weightedScore').textContent = totalScore.toFixed(2);
            
            // Update classification
            const classificationBadge = document.getElementById('classification');
            if (overallScore >= 85) {
                classificationBadge.textContent = 'Top Performer';
                classificationBadge.className = 'badge bg-success';
            } else if (overallScore >= 70) {
                classificationBadge.textContent = 'Good Performer';
                classificationBadge.className = 'badge bg-info';
            } else if (overallScore >= 50) {
                classificationBadge.textContent = 'Satisfactory';
                classificationBadge.className = 'badge bg-warning';
            } else if (overallScore >= 30) {
                classificationBadge.textContent = 'Needs Improvement';
                classificationBadge.className = 'badge bg-orange';
            } else if (overallScore > 0) {
                classificationBadge.textContent = 'Critical';
                classificationBadge.className = 'badge bg-danger';
            } else {
                classificationBadge.textContent = 'Not Rated';
                classificationBadge.className = 'badge bg-secondary';
            }
            
            // Update chart
            if (scoreChart) {
                scoreChart.data.datasets[0].data = [overallScore, 100 - overallScore];
                scoreChart.data.datasets[0].backgroundColor = [
                    overallScore >= 85 ? '#28a745' : 
                    overallScore >= 70 ? '#17a2b8' : 
                    overallScore >= 50 ? '#ffc107' : 
                    overallScore >= 30 ? '#fd7e14' : '#dc3545',
                    '#e9ecef'
                ];
                scoreChart.update();
            }
        }

        // Clear all scores
        function clearAllScores() {
            Swal.fire({
                title: 'Clear All Scores?',
                text: 'This will remove all entered scores. Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, clear all'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelectorAll('.score-btn').forEach(btn => btn.classList.remove('active'));
                    document.querySelectorAll('input[name^="scores"]').forEach(input => input.value = '');
                    updateSummary();
                    Swal.fire('Cleared!', 'All scores have been cleared.', 'success');
                }
            });
        }

        // View history
        function viewHistory() {
            const staffId = document.querySelector('input[name="staff_id"]').value;
            window.location.href = 'staff_profile.php?id=' + staffId;
        }

        // Form submission
        document.getElementById('kpiScoreForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const scores = {};
            
            // Collect scores
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('scores[') && value) {
                    const kpiCode = key.match(/\[(.*?)\]/)[1];
                    scores[kpiCode] = value;
                }
            }
            
            // Check if any scores entered
            if (Object.keys(scores).length === 0) {
                Swal.fire('No Scores', 'Please enter at least one score before saving.', 'warning');
                return;
            }
            
            // Prepare data
            const data = {
                staff_id: formData.get('staff_id'),
                evaluation_year: formData.get('evaluation_year'),
                evaluation_date: formData.get('evaluation_date'),
                scores: scores
            };
            
            // Show loading
            Swal.fire({
                title: 'Saving Scores...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit via AJAX
            fetch('../api/kpi_api.php?action=save_scores', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Scores Saved!',
                        text: `Successfully saved ${Object.keys(scores).length} KPI scores.`,
                        confirmButtonText: 'View Profile'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'staff_profile.php?id=' + data.staff_id;
                        }
                    });
                } else {
                    Swal.fire('Error', result.message || 'Failed to save scores', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while saving scores', 'error');
            });
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initChart();
            updateSummary();
        });
    </script>
</body>
</html>
