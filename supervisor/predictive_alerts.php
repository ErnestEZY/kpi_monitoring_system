<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

// Get all active staff
$sql = "SELECT staff_id, staff_code, name as full_name, department as department_name, status
        FROM staff
        WHERE status = 'Active'
        ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$all_staff = $stmt->fetchAll();

$predictions = [];

foreach ($all_staff as $staff) {
    $trend = $calculator->getPerformanceTrend($staff['staff_id']);
    
    if (count($trend) >= 2) {
        $latest = end($trend);
        $previous = $trend[count($trend) - 2];
        
        $prediction = [
            'staff_id' => $staff['staff_id'],
            'staff_number' => $staff['staff_code'],
            'full_name' => $staff['full_name'],
            'department' => $staff['department_name'],
            'current_score' => $latest['overall_score'],
            'trend' => $latest['trend'],
            'change' => $latest['change'],
            'alert_level' => 'none',
            'prediction' => '',
            'recommendation' => ''
        ];
        
        // Predictive logic
        if ($latest['trend'] == 'declining' && $latest['overall_score'] < 75) {
            $prediction['alert_level'] = 'high';
            $prediction['prediction'] = 'High risk of falling below satisfactory performance';
            $prediction['recommendation'] = 'Immediate intervention required - schedule performance review';
        } elseif ($latest['trend'] == 'declining' && $latest['overall_score'] >= 75) {
            $prediction['alert_level'] = 'medium';
            $prediction['prediction'] = 'Performance declining - may need support';
            $prediction['recommendation'] = 'Monitor closely and provide coaching';
        } elseif ($latest['overall_score'] < 70 && $latest['trend'] == 'stable') {
            $prediction['alert_level'] = 'medium';
            $prediction['prediction'] = 'Consistently low performance - no improvement';
            $prediction['recommendation'] = 'Develop performance improvement plan';
        } elseif ($latest['trend'] == 'improving' && $latest['overall_score'] >= 80) {
            $prediction['alert_level'] = 'positive';
            $prediction['prediction'] = 'On track to become top performer';
            $prediction['recommendation'] = 'Consider for leadership development';
        }
        
        if ($prediction['alert_level'] != 'none') {
            $predictions[] = $prediction;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictive Performance Alerts - KPI System</title>
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
                    <h1 class="h2"><i class="bi bi-lightning-charge-fill"></i> Predictive Performance Alerts</h1>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> 
                    <strong>AI-Powered Insights:</strong> This system analyzes performance trends to predict future risks and opportunities.
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <?php
                    $high_alerts = array_filter($predictions, fn($p) => $p['alert_level'] == 'high');
                    $medium_alerts = array_filter($predictions, fn($p) => $p['alert_level'] == 'medium');
                    $positive_alerts = array_filter($predictions, fn($p) => $p['alert_level'] == 'positive');
                    ?>
                    <div class="col-md-4">
                        <div class="card border-left-danger shadow h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">High Risk Alerts</div>
                                        <div class="h5 mb-0 font-weight-bold"><?= count($high_alerts) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-left-warning shadow h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Medium Risk Alerts</div>
                                        <div class="h5 mb-0 font-weight-bold"><?= count($medium_alerts) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-exclamation-circle-fill fs-2 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-left-success shadow h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Positive Trends</div>
                                        <div class="h5 mb-0 font-weight-bold"><?= count($positive_alerts) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-graph-up-arrow fs-2 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- High Risk Alerts -->
                <?php if (!empty($high_alerts)): ?>
                <div class="card shadow mb-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="bi bi-exclamation-triangle-fill"></i> High Risk - Immediate Action Required
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($high_alerts as $alert): ?>
                            <div class="alert alert-danger">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <h5 class="mb-0"><?= htmlspecialchars($alert['full_name']) ?></h5>
                                        <small class="text-muted"><?= $alert['staff_number'] ?> | <?= $alert['department'] ?></small>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="h4 mb-0"><?= $alert['current_score'] ?>%</div>
                                        <small>Current Score</small>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Prediction:</strong> <?= $alert['prediction'] ?><br>
                                        <strong>Recommendation:</strong> <?= $alert['recommendation'] ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="staff_profile.php?id=<?= $alert['staff_id'] ?>" class="btn btn-danger">
                                            <i class="bi bi-eye"></i> Review Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Medium Risk Alerts -->
                <?php if (!empty($medium_alerts)): ?>
                <div class="card shadow mb-4 border-warning">
                    <div class="card-header bg-warning">
                        <h6 class="m-0 font-weight-bold">
                            <i class="bi bi-exclamation-circle-fill"></i> Medium Risk - Monitor Closely
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Staff</th>
                                        <th>Department</th>
                                        <th>Current Score</th>
                                        <th>Trend</th>
                                        <th>Prediction</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medium_alerts as $alert): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($alert['full_name']) ?></strong><br>
                                                <small class="text-muted"><?= $alert['staff_number'] ?></small>
                                            </td>
                                            <td><?= $alert['department'] ?></td>
                                            <td><span class="badge bg-warning"><?= $alert['current_score'] ?>%</span></td>
                                            <td>
                                                <span class="badge bg-<?= $alert['trend'] == 'declining' ? 'danger' : 'secondary' ?>">
                                                    <?= ucfirst($alert['trend']) ?>
                                                </span>
                                            </td>
                                            <td><?= $alert['prediction'] ?></td>
                                            <td>
                                                <a href="staff_profile.php?id=<?= $alert['staff_id'] ?>" class="btn btn-sm btn-primary">
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
                <?php endif; ?>

                <!-- Positive Trends -->
                <?php if (!empty($positive_alerts)): ?>
                <div class="card shadow mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="bi bi-graph-up-arrow"></i> Positive Trends - Recognition Opportunities
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($positive_alerts as $alert): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5><?= htmlspecialchars($alert['full_name']) ?></h5>
                                            <p class="mb-2">
                                                <span class="badge bg-success"><?= $alert['current_score'] ?>%</span>
                                                <span class="badge bg-info"><?= $alert['department'] ?></span>
                                            </p>
                                            <p class="mb-2"><strong>Prediction:</strong> <?= $alert['prediction'] ?></p>
                                            <p class="mb-2"><strong>Recommendation:</strong> <?= $alert['recommendation'] ?></p>
                                            <a href="staff_profile.php?id=<?= $alert['staff_id'] ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-eye"></i> View Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($predictions)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> 
                    <strong>All Clear!</strong> No performance alerts detected at this time.
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
