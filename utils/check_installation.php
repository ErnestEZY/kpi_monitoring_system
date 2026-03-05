<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Checker - KPI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #ccc;
        }
        .check-pass {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .check-fail {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .check-warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .icon-pass { color: #28a745; }
        .icon-fail { color: #dc3545; }
        .icon-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">🔧 KPI System Installation Checker</h1>
        <p class="lead">This page will verify your installation is set up correctly.</p>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Installation Status</h5>
            </div>
            <div class="card-body">
                <?php
                $checks = [];
                $all_passed = true;
                
                // Check 1: PHP Version
                $php_version = phpversion();
                $php_ok = version_compare($php_version, '7.4.0', '>=');
                $checks[] = [
                    'name' => 'PHP Version',
                    'status' => $php_ok,
                    'message' => $php_ok ? "PHP $php_version (OK)" : "PHP $php_version (Need 7.4+)",
                    'critical' => true
                ];
                if (!$php_ok) $all_passed = false;
                
                // Check 2: PDO Extension
                $pdo_ok = extension_loaded('pdo') && extension_loaded('pdo_mysql');
                $checks[] = [
                    'name' => 'PDO MySQL Extension',
                    'status' => $pdo_ok,
                    'message' => $pdo_ok ? 'PDO MySQL is enabled' : 'PDO MySQL is not enabled',
                    'critical' => true
                ];
                if (!$pdo_ok) $all_passed = false;
                
                // Check 3: Config file exists
                $config_exists = file_exists('config/database.php');
                $checks[] = [
                    'name' => 'Configuration File',
                    'status' => $config_exists,
                    'message' => $config_exists ? 'config/database.php found' : 'config/database.php missing',
                    'critical' => true
                ];
                if (!$config_exists) $all_passed = false;
                
                // Check 4: Database Connection
                $db_connected = false;
                $db_message = '';
                if ($config_exists) {
                    try {
                        require_once 'config/database.php';
                        $pdo = getDBConnection();
                        $db_connected = true;
                        $db_message = 'Successfully connected to database';
                    } catch (Exception $e) {
                        $db_message = 'Connection failed: ' . $e->getMessage();
                        $all_passed = false;
                    }
                } else {
                    $db_message = 'Cannot test - config file missing';
                    $all_passed = false;
                }
                $checks[] = [
                    'name' => 'Database Connection',
                    'status' => $db_connected,
                    'message' => $db_message,
                    'critical' => true
                ];
                
                // Check 5: Tables exist
                $tables_ok = false;
                $tables_message = '';
                if ($db_connected) {
                    try {
                        $required_tables = ['supervisors', 'staff', 'kpi_master', 'kpi_scores', 'staff_comments'];
                        $stmt = $pdo->query("SHOW TABLES");
                        $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $missing_tables = array_diff($required_tables, $existing_tables);
                        
                        if (empty($missing_tables)) {
                            $tables_ok = true;
                            $tables_message = 'All 5 required tables exist';
                        } else {
                            $tables_message = 'Missing tables: ' . implode(', ', $missing_tables);
                            $all_passed = false;
                        }
                    } catch (Exception $e) {
                        $tables_message = 'Error checking tables: ' . $e->getMessage();
                        $all_passed = false;
                    }
                } else {
                    $tables_message = 'Cannot check - no database connection';
                    $all_passed = false;
                }
                $checks[] = [
                    'name' => 'Database Tables',
                    'status' => $tables_ok,
                    'message' => $tables_message,
                    'critical' => true
                ];
                
                // Check 6: Sample data exists
                $data_ok = false;
                $data_message = '';
                if ($tables_ok) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM staff");
                        $staff_count = $stmt->fetchColumn();
                        
                        $stmt = $pdo->query("SELECT COUNT(*) FROM kpi_scores");
                        $scores_count = $stmt->fetchColumn();
                        
                        if ($staff_count > 0 && $scores_count > 0) {
                            $data_ok = true;
                            $data_message = "$staff_count staff members, $scores_count KPI scores";
                        } else {
                            $data_message = 'Tables are empty - import database/new_sample_data.sql';
                            $all_passed = false;
                        }
                    } catch (Exception $e) {
                        $data_message = 'Error checking data: ' . $e->getMessage();
                        $all_passed = false;
                    }
                } else {
                    $data_message = 'Cannot check - tables missing';
                    $all_passed = false;
                }
                $checks[] = [
                    'name' => 'Sample Data',
                    'status' => $data_ok,
                    'message' => $data_message,
                    'critical' => true
                ];
                
                // Check 7: KPICalculator class
                $calculator_ok = file_exists('includes/KPICalculator.php');
                $checks[] = [
                    'name' => 'KPI Calculator Class',
                    'status' => $calculator_ok,
                    'message' => $calculator_ok ? 'includes/KPICalculator.php found' : 'includes/KPICalculator.php missing',
                    'critical' => true
                ];
                if (!$calculator_ok) $all_passed = false;
                
                // Check 8: API endpoint
                $api_ok = file_exists('api/kpi_calculations.php');
                $checks[] = [
                    'name' => 'API Endpoint',
                    'status' => $api_ok,
                    'message' => $api_ok ? 'api/kpi_calculations.php found' : 'api/kpi_calculations.php missing',
                    'critical' => true
                ];
                if (!$api_ok) $all_passed = false;
                
                // Display all checks
                foreach ($checks as $check) {
                    $class = $check['status'] ? 'check-pass' : ($check['critical'] ? 'check-fail' : 'check-warning');
                    $icon = $check['status'] ? '✓' : '✗';
                    $icon_class = $check['status'] ? 'icon-pass' : 'icon-fail';
                    
                    echo "<div class='check-item $class'>";
                    echo "<strong class='$icon_class'>$icon {$check['name']}</strong><br>";
                    echo "<small>{$check['message']}</small>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        
        <?php if ($all_passed): ?>
        <div class="alert alert-success">
            <h4>✅ Installation Complete!</h4>
            <p>All checks passed. Your system is ready to use.</p>
            <a href="test_kpi_calculations.php" class="btn btn-success">Go to Test Page</a>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            <h4>❌ Installation Incomplete</h4>
            <p>Please fix the issues above before proceeding.</p>
            <h5 class="mt-3">Quick Fix Steps:</h5>
            <ol>
                <li>Make sure XAMPP Apache and MySQL are running</li>
                <li>Create database: <code>kpi_system</code></li>
                <li>Import <code>database/new_schema.sql</code> in phpMyAdmin</li>
                <li>Import <code>database/new_sample_data.sql</code> in phpMyAdmin</li>
                <li>Refresh this page</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">📋 Quick Reference</h5>
            </div>
            <div class="card-body">
                <h6>Important URLs:</h6>
                <ul>
                    <li><strong>phpMyAdmin:</strong> <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
                    <li><strong>This Checker:</strong> <code>http://localhost/kpi_system/check_installation.php</code></li>
                    <li><strong>Test Page:</strong> <code>http://localhost/kpi_system/test_kpi_calculations.php</code></li>
                </ul>
                
                <h6 class="mt-3">Database Credentials:</h6>
                <ul>
                    <li><strong>Host:</strong> localhost</li>
                    <li><strong>Database:</strong> kpi_system</li>
                    <li><strong>Username:</strong> root</li>
                    <li><strong>Password:</strong> (empty)</li>
                </ul>
                
                <h6 class="mt-3">File Locations:</h6>
                <ul>
                    <li><strong>Schema:</strong> database/new_schema.sql</li>
                    <li><strong>Sample Data:</strong> database/new_sample_data.sql</li>
                    <li><strong>Config:</strong> config/database.php</li>
                </ul>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <button onclick="location.reload()" class="btn btn-primary">🔄 Re-check Installation</button>
        </div>
    </div>
</body>
</html>
