<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo = getDBConnection();
$calculator = new KPICalculator($pdo);

$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;
$selected_dept = $_GET['dept'] ?? '';

// Get unique departments from staff table
$sql = "SELECT DISTINCT department FROM staff WHERE department IS NOT NULL ORDER BY department";
$stmt = $pdo->query($sql);
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get available years
// Fixed year range: 2026 down to 2021
$available_years = range(2026, 2021);

// Get all staff
$sql = "SELECT s.*, s.department as department_name, s.name as full_name, s.staff_code as staff_number
        FROM staff s";

if ($selected_dept) {
    $sql .= " WHERE s.department = ?";
}

$sql .= " ORDER BY s.name";

$stmt = $pdo->prepare($sql);
if ($selected_dept) {
    $stmt->execute([$selected_dept]);
} else {
    $stmt->execute();
}
$all_staff = $stmt->fetchAll();

// Calculate scores
$staff_data = [];
foreach ($all_staff as $staff) {
    $score_data = $calculator->calculateOverallScore($staff['staff_id'], $selected_year);
    $classification = $calculator->getPerformanceClassification($score_data['overall_score']);
    $trend = $calculator->getPerformanceTrend($staff['staff_id']);
    
    $latest_trend = 'new';
    if (count($trend) > 1) {
        $latest_trend = end($trend)['trend'];
    }
    
    $staff_data[] = [
        'staff' => $staff,
        'score' => $score_data['overall_score'],
        'classification' => $classification,
        'has_data' => $score_data['has_data'],
        'trend' => $latest_trend
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Staff - KPI System</title>
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
                    <h1 class="h2"><i class="bi bi-people-fill"></i> All Staff Members</h1>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Evaluation Year</label>
                                <select name="year" class="form-select" onchange="this.form.submit()">
                                    <?php foreach ($available_years as $year): ?>
                                        <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department</label>
                                <select name="dept" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept) ?>" <?= $dept == $selected_dept ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Performance Level</label>
                                <select id="performanceFilter" class="form-select">
                                    <option value="">All Levels</option>
                                    <option value="Top Performer">Top Performer (≥85%)</option>
                                    <option value="Good Performer">Good Performer (70-84%)</option>
                                    <option value="Satisfactory">Satisfactory (50-69%)</option>
                                    <option value="Needs Improvement">Needs Improvement (30-49%)</option>
                                    <option value="Critical">Critical (<30%)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Staff Member</label>
                                <select id="staffFilter" class="form-select">
                                    <option value="">All Staff</option>
                                    <?php foreach ($all_staff as $staff): ?>
                                        <option value="<?= strtolower($staff['name']) ?>">
                                            <?= htmlspecialchars($staff['name']) ?> (<?= htmlspecialchars($staff['staff_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Staff Grid -->
                <div class="row" id="staffGrid">
                    <?php foreach ($staff_data as $data): ?>
                        <?php
                        $staff = $data['staff'];
                        $names = explode(' ', $staff['full_name']);
                        $initials = '';
                        foreach ($names as $name) {
                            $initials .= strtoupper(substr($name, 0, 1));
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4 staff-card" 
                             data-name="<?= strtolower($staff['full_name']) ?>"
                             data-classification="<?= $data['classification']['label'] ?>">
                            <div class="card shadow h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="profile-avatar me-3" style="width:60px;height:60px;font-size:1.5rem;flex-shrink:0;">
                                            <?= $initials ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-0"><?= htmlspecialchars($staff['full_name']) ?></h5>
                                            <small class="text-muted"><?= htmlspecialchars($staff['staff_number']) ?></small>
                                        </div>
                                        <?php if (!empty($staff['photo'])): ?>
                                        <img src="../assets/photos/<?= htmlspecialchars($staff['photo']) ?>"
                                             alt=""
                                             style="width:90px;height:90px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1px solid #dee2e6;">
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <i class="bi bi-building text-muted"></i>
                                        <small><?= htmlspecialchars($staff['department_name']) ?></small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <i class="bi bi-calendar text-muted"></i>
                                        <small>Hired: <?= date('M Y', strtotime($staff['hire_date'])) ?></small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <i class="bi bi-circle-fill text-<?= $staff['status'] == 'Active' ? 'success' : 'secondary' ?>"></i>
                                        <small><?= $staff['status'] ?></small>
                                    </div>
                                    
                                    <hr>
                                    
                                    <?php if ($data['has_data']): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">Performance (<?= $selected_year ?>)</span>
                                            <h4 class="mb-0"><?= $data['score'] ?>%</h4>
                                        </div>
                                        
                                        <div class="progress mb-2" style="height: 8px;">
                                            <div class="progress-bar bg-<?= $data['classification']['badge'] ?>" 
                                                 style="width: <?= $data['score'] ?>%"></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-<?= $data['classification']['badge'] ?>">
                                                <?= $data['classification']['label'] ?>
                                            </span>
                                            <span class="badge bg-<?= $data['trend'] == 'improving' ? 'success' : ($data['trend'] == 'declining' ? 'danger' : 'secondary') ?>">
                                                <?php if ($data['trend'] == 'improving'): ?>
                                                    <i class="bi bi-arrow-up"></i> Improving
                                                <?php elseif ($data['trend'] == 'declining'): ?>
                                                    <i class="bi bi-arrow-down"></i> Declining
                                                <?php elseif ($data['trend'] == 'stable'): ?>
                                                    <i class="bi bi-arrow-right"></i> Stable
                                                <?php else: ?>
                                                    <i class="bi bi-star"></i> New
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-secondary mb-3">
                                            <small>No data for <?= $selected_year ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="staff_profile.php?id=<?= $staff['staff_id'] ?>" 
                                       class="btn btn-primary w-100">
                                        <i class="bi bi-eye"></i> View Full Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($staff_data)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No staff members found matching your criteria.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.getElementById('staffFilter').addEventListener('change', filterStaff);
        document.getElementById('performanceFilter').addEventListener('change', filterStaff);
        
        function filterStaff() {
            const staffValue = document.getElementById('staffFilter').value.toLowerCase();
            const performanceValue = document.getElementById('performanceFilter').value;
            const cards = document.querySelectorAll('.staff-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const classification = card.getAttribute('data-classification');
                
                const matchesStaff = !staffValue || name.includes(staffValue);
                const matchesPerformance = !performanceValue || classification === performanceValue;
                
                if (matchesStaff && matchesPerformance) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
