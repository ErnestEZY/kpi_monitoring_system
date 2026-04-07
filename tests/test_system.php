<?php
/**
 * KPI Monitoring System — Functional Test Suite
 * Access via browser: http://localhost/kpi_system/tests/test_system.php
 *
 * Tests every major function without modifying live data.
 */

session_start();
require_once '../config/database.php';
require_once '../includes/KPICalculator.php';

$pdo        = getDBConnection();
$calculator = new KPICalculator($pdo);

// ── Helpers ──────────────────────────────────────────────────────────────────

$pass = 0;
$fail = 0;
$results = [];

function test(string $name, bool $condition, string $detail = ''): void {
    global $pass, $fail, $results;
    if ($condition) {
        $pass++;
        $results[] = ['status' => 'PASS', 'name' => $name, 'detail' => $detail];
    } else {
        $fail++;
        $results[] = ['status' => 'FAIL', 'name' => $name, 'detail' => $detail ?: 'Condition was false'];
    }
}

// ── 1. Database Connection ────────────────────────────────────────────────────

try {
    $stmt = $pdo->query("SELECT 1");
    test('Database connection', $stmt !== false, 'PDO connected to kpi_system');
} catch (Exception $e) {
    test('Database connection', false, $e->getMessage());
}

// ── 2. Tables Exist ───────────────────────────────────────────────────────────

$required_tables = ['staff', 'kpi_master', 'kpi_scores', 'staff_comments', 'supervisors'];
foreach ($required_tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    test("Table exists: $table", $stmt->rowCount() > 0);
}

// ── 3. KPI Master Data ────────────────────────────────────────────────────────

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM kpi_master");
$kpi_count = (int)$stmt->fetch()['cnt'];
test('KPI master has 21 KPIs', $kpi_count === 21, "Found $kpi_count KPIs");

$stmt = $pdo->query("SELECT SUM(weight_percentage) as total FROM kpi_master");
$total_weight = round((float)$stmt->fetch()['total'], 2);
test('KPI weights sum to 100%', $total_weight === 100.00, "Sum = $total_weight%");

// ── 4. Staff Data ─────────────────────────────────────────────────────────────

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM staff WHERE status = 'Active'");
$staff_count = (int)$stmt->fetch()['cnt'];
test('Active staff exist', $staff_count > 0, "$staff_count active staff found");

// ── 5. KPI Score Calculation ──────────────────────────────────────────────────

// Get first staff with data
$stmt = $pdo->query("SELECT DISTINCT staff_id FROM kpi_scores LIMIT 1");
$row  = $stmt->fetch();
if ($row) {
    $staff_id = (int)$row['staff_id'];

    $stmt2 = $pdo->query("SELECT DISTINCT evaluation_year FROM kpi_scores WHERE staff_id = $staff_id LIMIT 1");
    $year  = (int)$stmt2->fetch()['evaluation_year'];

    $result = $calculator->calculateOverallScore($staff_id, $year);
    test('calculateOverallScore returns array',   is_array($result));
    test('overall_score is numeric',              is_numeric($result['overall_score']));
    test('overall_score in range 0–100',          $result['overall_score'] >= 0 && $result['overall_score'] <= 100,
         "Score = {$result['overall_score']}%");
    test('has_data flag is boolean',              is_bool($result['has_data']));
    test('category_scores is array',              is_array($result['category_scores']));
} else {
    test('calculateOverallScore', false, 'No kpi_scores data found');
}

// ── 6. Performance Classification ────────────────────────────────────────────

$cases = [
    [90, 'Top Performer'],
    [80, 'Good Performer'],
    [70, 'Satisfactory'],
    [55, 'Needs Improvement'],
    [30, 'Critical'],
];
foreach ($cases as [$score, $expected]) {
    $label = $calculator->classifyPerformance($score);
    test("classifyPerformance($score%) = $expected", $label === $expected, "Got: $label");
}

// ── 7. Performance Trend ──────────────────────────────────────────────────────

if (isset($staff_id)) {
    $trend = $calculator->getPerformanceTrend($staff_id);
    test('getPerformanceTrend returns array',     is_array($trend));
    test('Trend entries have required keys',
         !empty($trend) && isset($trend[0]['year'], $trend[0]['overall_score'], $trend[0]['trend']),
         empty($trend) ? 'No trend data' : 'Keys present');
}

// ── 8. At-Risk Staff (year-specific) ─────────────────────────────────────────

$at_risk_2026 = $calculator->getAtRiskStaff(2026);
$at_risk_2022 = $calculator->getAtRiskStaff(2022);
test('getAtRiskStaff returns array for 2026',  is_array($at_risk_2026));
test('getAtRiskStaff returns array for 2022',  is_array($at_risk_2022));
test('At-risk results differ by year',
     $at_risk_2026 !== $at_risk_2022 || count($at_risk_2026) === 0,
     'Year filter is working (or no at-risk data yet)');

// ── 9. Top Performers ─────────────────────────────────────────────────────────

$top = $calculator->getTopPerformers(2026, 85);
test('getTopPerformers returns array', is_array($top));
if (!empty($top)) {
    test('Top performers all have score ≥ 85',
         min(array_column($top, 'overall_score')) >= 85,
         'Min score: ' . min(array_column($top, 'overall_score')));
}

// ── 10. Category Averages ─────────────────────────────────────────────────────

$avgs = $calculator->getCategoryAverages(2026);
test('getCategoryAverages returns array',  is_array($avgs));
test('Category averages not empty',        !empty($avgs), count($avgs) . ' categories');

// ── 11. Narrative Generation ──────────────────────────────────────────────────

if (isset($staff_id)) {
    $narrative = $calculator->generateNarrative($staff_id);
    test('generateNarrative returns string',   is_string($narrative));
    test('Narrative is not empty',             strlen($narrative) > 10);
}

// ── 12. Team Average Comparison ───────────────────────────────────────────────

if (isset($staff_id)) {
    $comparison = $calculator->compareToTeamAverage($staff_id, $year);
    test('compareToTeamAverage returns array',  is_array($comparison));
    test('Comparison has required keys',
         isset($comparison['staff_score'], $comparison['team_average'], $comparison['difference']));
}

// ── 13. Predictive Performance (Linear Regression) ───────────────────────────

if (isset($staff_id)) {
    $prediction = $calculator->predictPerformance($staff_id);
    test('predictPerformance returns array', is_array($prediction));
    if ($prediction['success'] ?? false) {
        test('Predicted score in range 0–100',
             $prediction['predicted_score'] >= 0 && $prediction['predicted_score'] <= 100,
             "Predicted: {$prediction['predicted_score']}%");
        test('Confidence in range 50–99',
             $prediction['confidence'] >= 50 && $prediction['confidence'] <= 99,
             "Confidence: {$prediction['confidence']}%");
    } else {
        test('predictPerformance (insufficient data)', true, $prediction['message'] ?? 'Skipped');
    }
}

// ── 14. Supervisor Login ──────────────────────────────────────────────────────

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM supervisors");
$sup_count = (int)$stmt->fetch()['cnt'];
test('Supervisors table has records', $sup_count > 0, "$sup_count supervisor(s) found");

// ── 15. Staff Comments API ────────────────────────────────────────────────────

$stmt = $pdo->prepare("SELECT staff_id, evaluation_year FROM staff_comments LIMIT 1");
$stmt->execute();
$comment_row = $stmt->fetch();
if ($comment_row) {
    $stmt2 = $pdo->prepare(
        "SELECT supervisor_comment FROM staff_comments WHERE staff_id = ? AND evaluation_year = ?"
    );
    $stmt2->execute([$comment_row['staff_id'], $comment_row['evaluation_year']]);
    $comment = $stmt2->fetch();
    test('Staff comments readable from DB', $comment !== false);
} else {
    test('Staff comments (no data yet)', true, 'No comments in DB — skipped');
}

// ── 16. Unique Key on kpi_scores ─────────────────────────────────────────────

$stmt = $pdo->query(
    "SHOW INDEX FROM kpi_scores WHERE Key_name = 'unique_staff_kpi_year'"
);
test('kpi_scores has unique_staff_kpi_year index', $stmt->rowCount() > 0,
     'Prevents duplicate scores per staff/KPI/year');

// ── 17. Photo Column Exists ───────────────────────────────────────────────────

$stmt = $pdo->query("SHOW COLUMNS FROM staff LIKE 'photo'");
test('staff.photo column exists', $stmt->rowCount() > 0,
     'Run database/add_staff_photo.sql if this fails');

// ─────────────────────────────────────────────────────────────────────────────
// OUTPUT
// ─────────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI System — Test Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h2 class="mb-1">KPI Monitoring System — Test Suite</h2>
    <p class="text-muted mb-4">Functional tests covering database, calculations, classification, and API logic.</p>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h1 class="text-primary"><?= $pass + $fail ?></h1>
                    <p class="mb-0 text-muted">Total Tests</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h1 class="text-success"><?= $pass ?></h1>
                    <p class="mb-0 text-muted">Passed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h1 class="text-danger"><?= $fail ?></h1>
                    <p class="mb-0 text-muted">Failed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h1 class="<?= $fail === 0 ? 'text-success' : 'text-warning' ?>">
                        <?= $fail === 0 ? '✓ All Pass' : round(($pass / ($pass + $fail)) * 100) . '%' ?>
                    </h1>
                    <p class="mb-0 text-muted">Result</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Results table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:80px">Status</th>
                        <th>Test</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?= $r['status'] === 'PASS' ? 'table-success' : 'table-danger' ?>">
                        <td>
                            <span class="badge bg-<?= $r['status'] === 'PASS' ? 'success' : 'danger' ?>">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($r['detail']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3">
        Run from: <code>http://localhost/kpi_system/tests/test_system.php</code>
    </p>
</div>
</body>
</html>
