<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

$staff_id = $_GET['id'] ?? null;

if (!$staff_id) {
    header('Location: dashboard.php');
    exit;
}

// Get staff information
$sql = "SELECT s.*, s.department as department_name, s.name as full_name, s.staff_code as staff_number
        FROM staff s
        WHERE s.staff_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) {
    header('Location: dashboard.php');
    exit;
}

// Get performance trend
$trend = $calculator->getPerformanceTrend($staff_id);

// Get current year data
$current_year = date('Y');
$current_data = $calculator->calculateOverallScore($staff_id, $current_year);
$classification = $calculator->getPerformanceClassification($current_data['overall_score']);

// Get narrative
$narrative = $calculator->generateNarrative($staff_id);

// Get team comparison
$team_comparison = $calculator->compareToTeamAverage($staff_id, $current_year);

// Get supervisor comments
$sql = "SELECT * FROM staff_comments 
        WHERE staff_id = ? 
        ORDER BY evaluation_year DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$staff_id]);
$comments = $stmt->fetchAll();

// Get initials for avatar
$names = explode(' ', $staff['full_name']);
$initials = '';
foreach ($names as $name) {
    $initials .= strtoupper(substr($name, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($staff['full_name']) ?> - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // Simple Alpine.js test
        document.addEventListener('alpine:init', () => {
            console.log('Alpine.js is working!');
        });
    </script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2">
                        <a href="dashboard.php" class="text-decoration-none text-muted">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        Staff Profile
                    </h1>
                    <div class="btn-toolbar">
                        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Profile
                        </button>
                    </div>
                </div>

                <!-- Profile Header -->
                <div class="card shadow mb-4">
                    <div class="profile-header">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <!-- Initials avatar — unchanged -->
                                <div class="profile-avatar">
                                    <?= $initials ?>
                                </div>
                            </div>
                            <div class="col">
                                <h3 class="mb-1"><?= htmlspecialchars($staff['full_name']) ?></h3>
                                <p class="text-muted mb-1"><?= htmlspecialchars($staff['staff_number']) ?> • <?= htmlspecialchars($staff['department_name']) ?></p>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-<?= $classification['color'] ?> fs-6"><?= $classification['label'] ?></span>
                                    <span class="text-muted">Overall Score: <strong><?= number_format($current_data['overall_score'], 1) ?>%</strong></span>
                                </div>
                            </div>
                            <?php if (!empty($staff['photo'])): ?>
                            <div class="col-auto d-none d-md-block">
                                <img src="../assets/photos/<?= htmlspecialchars($staff['photo']) ?>"
                                     alt=""
                                     style="width:110px;height:110px;border-radius:12px;object-fit:cover;border:3px solid rgba(255,255,255,.5);box-shadow:0 4px 12px rgba(0,0,0,.2);">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- AI-Generated Narrative -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-robot"></i> Performance Story
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="narrative-content">
                            <?= $narrative ?>
                        </div>
                    </div>
                </div>

                <!-- Performance Visuals & Charts -->
                <div class="row mb-4">
                    <!-- Performance Trend Chart -->
                    <div class="col-lg-8 d-flex">
                        <div class="card shadow mb-4 w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-graph-up"></i> Performance Trend Over Time
                                </h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="trendChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <p class="text-muted mb-2"><strong>Trend Analysis:</strong></p>
                                    <?php
                                    if (count($trend) > 1) {
                                        $latest = end($trend);
                                        $previous = prev($trend);
                                        $change = $latest['overall_score'] - $previous['overall_score'];
                                        
                                        if ($change > 5) {
                                            echo '<p class="text-success"><i class="bi bi-arrow-up-circle-fill"></i> <strong>Improving:</strong> Performance has increased by ' . number_format($change, 1) . '% from last year. Keep up the excellent work!</p>';
                                        } elseif ($change < -5) {
                                            echo '<p class="text-danger"><i class="bi bi-arrow-down-circle-fill"></i> <strong>Declining:</strong> Performance has decreased by ' . number_format(abs($change), 1) . '% from last year. Immediate attention and support needed.</p>';
                                        } else {
                                            echo '<p class="text-info"><i class="bi bi-dash-circle-fill"></i> <strong>Stable:</strong> Performance is consistent with previous year. Consider setting new growth targets.</p>';
                                        }
                                    } else {
                                        echo '<p class="text-muted">Insufficient historical data for trend analysis.</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Score Gauge -->
                    <div class="col-lg-4 d-flex">
                        <div class="card shadow mb-4 w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-speedometer2"></i> Current Score
                                </h6>
                            </div>
                            <div class="card-body text-center d-flex flex-column">
                                <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                                    <div style="height: 200px; position: relative; display: flex; align-items: center; justify-content: center;">
                                        <div style="position: relative; width: 200px; height: 200px;">
                                            <canvas id="gaugeChart"></canvas>
                                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;">
                                                <h2 class="mb-0"><?= number_format($current_data['overall_score'], 1) ?>%</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge bg-<?= $classification['color'] ?> fs-6 mt-2">
                                        <?= $classification['label'] ?>
                                    </span>
                                </div>
                                <div class="mt-auto text-start">
                                    <hr>
                                    <p class="mb-2"><strong>Performance Level:</strong></p>
                                    <?php if ($current_data['overall_score'] >= 85): ?>
                                        <p class="text-success small mb-0">
                                            <i class="bi bi-trophy-fill"></i> Exceeds expectations consistently. Candidate for leadership roles.
                                        </p>
                                    <?php elseif ($current_data['overall_score'] >= 70): ?>
                                        <p class="text-info small mb-0">
                                            <i class="bi bi-star-fill"></i> Meets expectations well. Shows potential for advancement.
                                        </p>
                                    <?php elseif ($current_data['overall_score'] >= 50): ?>
                                        <p class="text-warning small mb-0">
                                            <i class="bi bi-exclamation-circle-fill"></i> Meets basic expectations. Needs improvement in key areas.
                                        </p>
                                    <?php else: ?>
                                        <p class="text-danger small mb-0">
                                            <i class="bi bi-x-circle-fill"></i> Below expectations. Requires immediate intervention and support.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Category Breakdown -->
                <div class="row mb-4">
                    <div class="col-lg-6 d-flex">
                        <div class="card shadow mb-4 w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-pie-chart-fill"></i> KPI Category Breakdown
                                </h6>
                            </div>
                            <div class="card-body">
                                <div style="height: 320px;">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                                <?php
                                $sql_cat = "SELECT km.section, AVG(ks.score) as avg_score
                                            FROM kpi_scores ks
                                            JOIN kpi_master km ON ks.kpi_code = km.kpi_code
                                            WHERE ks.staff_id = ? AND ks.evaluation_year = ?
                                            GROUP BY km.section
                                            ORDER BY avg_score DESC";
                                $stmt_cat = $pdo->prepare($sql_cat);
                                $stmt_cat->execute([$staff_id, $current_year]);
                                $cat_insight = $stmt_cat->fetchAll();

                                if (!empty($cat_insight)):
                                    $best = $cat_insight[0];
                                    $worst = end($cat_insight);
                                    $avg_all = array_sum(array_column($cat_insight, 'avg_score')) / count($cat_insight);
                                    $below = array_filter($cat_insight, fn($c) => $c['avg_score'] < 3);
                                ?>
                                <hr class="mt-3 mb-2">
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-lightbulb text-warning"></i> <strong>Insight:</strong>
                                    Strongest in <strong><?= htmlspecialchars($best['section']) ?></strong>
                                    (<?= number_format($best['avg_score'], 1) ?>/5.0).
                                    <?php if ($worst['section'] !== $best['section']): ?>
                                        Lowest in <strong><?= htmlspecialchars($worst['section']) ?></strong>
                                        (<?= number_format($worst['avg_score'], 1) ?>/5.0).
                                    <?php endif; ?>
                                    Overall average: <strong><?= number_format($avg_all, 1) ?>/5.0</strong>.
                                    <?php if (count($below) > 0): ?>
                                        <span class="text-danger"><?= count($below) ?> categor<?= count($below) > 1 ? 'ies need' : 'y needs' ?> attention (score &lt; 3.0).</span>
                                    <?php else: ?>
                                        <span class="text-success">All categories are performing satisfactorily.</span>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card shadow mb-4 w-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-bar-chart-fill"></i> Strengths & Weaknesses
                                </h6>
                            </div>
                            <div class="card-body d-flex flex-column" style="min-height: 0;">
                                <div style="flex: 1; overflow-y: auto;">
                                <?php
                                // Get category scores for current year
                                $sql = "SELECT km.section, AVG(ks.score) as avg_score
                                        FROM kpi_scores ks
                                        JOIN kpi_master km ON ks.kpi_code = km.kpi_code
                                        WHERE ks.staff_id = ? AND ks.evaluation_year = ?
                                        GROUP BY km.section
                                        ORDER BY avg_score DESC";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$staff_id, $current_year]);
                                $category_scores = $stmt->fetchAll();
                                
                                if (!empty($category_scores)):
                                    $top_categories = array_slice($category_scores, 0, 3);
                                    $bottom_categories = array_slice($category_scores, -3);
                                ?>
                                    <div class="mb-4">
                                        <h6 class="text-success"><i class="bi bi-check-circle-fill"></i> Top Strengths</h6>
                                        <?php foreach ($top_categories as $cat): ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small><?= htmlspecialchars($cat['section']) ?></small>
                                                    <small class="text-success fw-bold"><?= number_format($cat['avg_score'], 1) ?>/5.0</small>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: <?= ($cat['avg_score']/5)*100 ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div>
                                        <h6 class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Areas for Improvement</h6>
                                        <?php foreach ($bottom_categories as $cat): ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small><?= htmlspecialchars($cat['section']) ?></small>
                                                    <small class="text-warning fw-bold"><?= number_format($cat['avg_score'], 1) ?>/5.0</small>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: <?= ($cat['avg_score']/5)*100 ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No category data available for <?= $current_year ?>.</p>
                                <?php endif; ?>
                                </div>
                                <?php if (!empty($category_scores)): ?>
                                <hr class="mt-3 mb-2">
                                <p class="small text-muted mb-0">
                                    <i class="bi bi-lightbulb text-warning"></i> <strong>Recommendation:</strong>
                                    Focus training efforts on <?= htmlspecialchars($bottom_categories[0]['section']) ?>
                                    to achieve balanced performance across all categories.
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supervisor Comments Section -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0">
                            <i class="bi bi-chat-left-text"></i> Supervisor Comments & Training Recommendations
                        </h6>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#commentModal">
                            <i class="bi bi-pencil-square"></i> Add/Edit Comment
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get latest comment
                        $sql = "SELECT * FROM staff_comments 
                                WHERE staff_id = ? 
                                ORDER BY evaluation_year DESC 
                                LIMIT 1";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$staff_id]);
                        $latest_comment = $stmt->fetch();
                        
                        if ($latest_comment && ($latest_comment['supervisor_comment'] || $latest_comment['training_recommendation'])):
                        ?>
                            <div class="mb-3">
                                <strong class="text-primary">Latest Comment (<?= $latest_comment['evaluation_year'] ?>):</strong>
                                <p class="mt-2"><?= nl2br(htmlspecialchars($latest_comment['supervisor_comment'] ?: 'No comment provided')) ?></p>
                            </div>
                            
                            <?php if ($latest_comment['training_recommendation']): ?>
                                <div class="alert alert-success">
                                    <strong><i class="bi bi-mortarboard-fill"></i> Training Recommendation:</strong>
                                    <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($latest_comment['training_recommendation'])) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">
                                <i class="bi bi-info-circle"></i> No comments added yet. Click "Add/Edit Comment" to provide feedback.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Comment Modal -->
    <div class="modal fade" id="commentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" x-data="commentAssistant(<?= $staff_id ?>, <?= $_SESSION['supervisor_id'] ?>)">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-magic"></i> Smart Comment Assistant
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Year Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Evaluation Year:</label>
                        <select x-model="selectedYear" class="form-select">
                            <?php for ($y = 2026; $y >= 2021; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <!-- Quick Templates -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-collection"></i> Quick Templates:
                        </label>
                        <div class="row g-2">
                            <template x-for="template in getTemplates()" :key="template.name">
                                <div class="col-md-6">
                                    <button 
                                        type="button" 
                                        class="btn btn-outline-primary w-100 text-start btn-sm"
                                        @click="applyTemplate(template)">
                                        <i class="bi bi-file-text"></i>
                                        <span x-text="template.name"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Comment Text Area -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Performance Comment:</label>
                        <textarea 
                            x-model="comment" 
                            class="form-control" 
                            rows="5"
                            placeholder="Type your comment or select a template above..."
                            maxlength="1000">
                        </textarea>
                        <small class="text-muted">
                            <span x-text="comment.length"></span> / 1000 characters
                        </small>
                    </div>
                    
                    <!-- Training Recommendation -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Training Recommendation:</label>
                        <textarea 
                            x-model="training" 
                            class="form-control" 
                            rows="3"
                            placeholder="Recommend specific training programs..."
                            maxlength="500">
                        </textarea>
                        <small class="text-muted">
                            <span x-text="training.length"></span> / 500 characters
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button 
                        type="button" 
                        @click="saveCommentAndClose()" 
                        class="btn btn-primary"
                        :disabled="comment.length === 0 || isSaving">
                        <i class="bi bi-save"></i> Save Comment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Auto-open comment modal if coming from training needs
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openComment') === 'true') {
                const commentModal = new bootstrap.Modal(document.getElementById('commentModal'));
                commentModal.show();
            }
        });
    </script>
    
    <!-- Alpine.js Comment Assistant Component -->
    <script>
        function commentAssistant(staffId, supervisorId) {
            return {
                staffId: staffId,
                supervisorId: supervisorId,
                comment: '',
                training: '',
                selectedYear: new Date().getFullYear(),
                isSaving: false,
                
                init() {
                    this.loadExistingCommentSilent();
                },
                
                async loadExistingCommentSilent() {
                    try {
                        const year = this.selectedYear || new Date().getFullYear();
                        const response = await fetch(`../api/kpi_api.php?action=get_staff_comment&staff_id=${this.staffId}&year=${year}`);
                        const data = await response.json();
                        
                        if (data.success && data.data) {
                            this.comment = data.data.supervisor_comment || '';
                            this.training = data.data.training_recommendation || '';
                        } else {
                            this.comment = '';
                            this.training = '';
                        }
                    } catch (error) {
                        console.error('Failed to load comment:', error);
                    }
                },
                
                async saveCommentAndClose() {
                    if (this.comment.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Comment',
                            text: 'Please enter a comment before saving',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    this.isSaving = true;
                    
                    try {
                        const year = this.selectedYear || new Date().getFullYear();
                        const response = await fetch('../api/kpi_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'save_comment',
                                staff_id: this.staffId,
                                year: year,
                                supervisor_comment: this.comment,
                                training_recommendation: this.training
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Comment Saved!',
                                text: 'Reloading page to show updated comment...',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Save Failed',
                                text: data.message || 'Failed to save comment',
                                confirmButtonText: 'OK'
                            });
                        }
                    } catch (error) {
                        console.error('Save error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Save Failed',
                            text: 'Failed to save comment. Please try again.',
                            confirmButtonText: 'OK'
                        });
                    } finally {
                        this.isSaving = false;
                    }
                },
                
                getTemplates() {
                    return [
                        {
                            name: 'Excellent Performance',
                            comment: 'Outstanding performance across all metrics. Exceeds expectations consistently and demonstrates exceptional leadership qualities.',
                            training: 'Consider for leadership development program and mentorship opportunities.'
                        },
                        {
                            name: 'Good Performance',
                            comment: 'Strong performance with room for growth. Meets expectations and shows potential for advancement.',
                            training: 'Focus on skill development in areas showing below-average performance.'
                        },
                        {
                            name: 'Needs Improvement',
                            comment: 'Performance below expected standards. Requires immediate attention and support.',
                            training: 'Mandatory training program required with close monitoring and regular feedback sessions.'
                        },
                        {
                            name: 'Customer Service Focus',
                            comment: 'Good customer interaction skills but needs improvement in handling difficult situations.',
                            training: 'Customer service excellence training with focus on conflict resolution.'
                        }
                    ];
                },
                
                applyTemplate(template) {
                    this.comment = template.comment;
                    this.training = template.training;
                }
            }
        }
    </script>
</body>
</html>

    <!-- Chart Rendering Scripts -->
    <script>
        // Performance Trend Chart
        <?php if (!empty($trend)): ?>
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($trend, 'year')) ?>,
                    datasets: [{
                        label: 'Overall Score (%)',
                        data: <?= json_encode(array_column($trend, 'overall_score')) ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Score: ' + context.parsed.y.toFixed(1) + '%';
                                }
                            }
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
                    }
                }
            });
        }
        <?php endif; ?>

        // Gauge Chart (Doughnut)
        const gaugeCtx = document.getElementById('gaugeChart');
        if (gaugeCtx) {
            const score = <?= $current_data['overall_score'] ?>;
            const remaining = 100 - score;
            
            new Chart(gaugeCtx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [score, remaining],
                        backgroundColor: [
                            score >= 85 ? '#28a745' : 
                            score >= 70 ? '#17a2b8' : 
                            score >= 50 ? '#ffc107' : '#dc3545',
                            '#e9ecef'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }

        // Category Breakdown Chart
        <?php
        $sql = "SELECT km.section, AVG(ks.score) as avg_score
                FROM kpi_scores ks
                JOIN kpi_master km ON ks.kpi_code = km.kpi_code
                WHERE ks.staff_id = ? AND ks.evaluation_year = ?
                GROUP BY km.section
                ORDER BY km.section";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$staff_id, $current_year]);
        $category_data = $stmt->fetchAll();
        
        if (!empty($category_data)):
        ?>
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            const categoryLabels = <?= json_encode(array_column($category_data, 'section')) ?>;
            const categoryScores = <?= json_encode(array_column($category_data, 'avg_score')) ?>;

            const barColors = categoryScores.map(score => {
                if (score >= 4)   return 'rgba(28, 200, 138, 0.85)';
                if (score >= 3)   return 'rgba(54, 185, 204, 0.85)';
                if (score >= 2)   return 'rgba(255, 193, 7, 0.85)';
                return 'rgba(231, 74, 59, 0.85)';
            });

            new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Avg Score',
                        data: categoryScores,
                        backgroundColor: barColors,
                        borderColor: barColors.map(c => c.replace('0.85', '1')),
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 5,
                            ticks: { stepSize: 1 },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            title: { display: true, text: 'Score (out of 5)', font: { size: 12 } }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { font: { size: 12, weight: '600' } }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` Score: ${ctx.parsed.x.toFixed(2)} / 5`
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
