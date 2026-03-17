<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3 sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
                   href="dashboard.php">
                    <i class="bi bi-house-door"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kpi_dashboard.php' ? 'active' : '' ?>" 
                   href="kpi_dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    KPI Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kpi_entry.php' ? 'active' : '' ?>" 
                   href="kpi_entry.php">
                    <i class="bi bi-pencil-square"></i>
                    KPI Score Entry
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'staff_list.php' ? 'active' : '' ?>" 
                   href="staff_list.php">
                    <i class="bi bi-people"></i>
                    All Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>" 
                   href="analytics.php">
                    <i class="bi bi-bar-chart"></i>
                    Analytics
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
            <span>Reports</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' && ($_GET['type'] ?? '') == 'top_performers' ? 'active' : '' ?>" 
                   href="reports.php?type=top_performers">
                    <i class="bi bi-trophy"></i>
                    Top Performers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' && ($_GET['type'] ?? '') == 'at_risk' ? 'active' : '' ?>" 
                   href="reports.php?type=at_risk">
                    <i class="bi bi-exclamation-triangle"></i>
                    At-Risk Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' && ($_GET['type'] ?? '') == 'training' ? 'active' : '' ?>" 
                   href="reports.php?type=training">
                    <i class="bi bi-book"></i>
                    Training Needs
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
            <span>Advanced Analytics</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'predictive_alerts_new.php' ? 'active' : '' ?>" 
                   href="predictive_alerts_new.php">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Predictive Alerts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'training_recommendations.php' ? 'active' : '' ?>" 
                   href="training_recommendations.php">
                    <i class="bi bi-mortarboard-fill"></i>
                    Training Recommendations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'anomaly_detection.php' ? 'active' : '' ?>" 
                   href="anomaly_detection.php">
                    <i class="bi bi-graph-up-arrow"></i>
                    Anomaly Detection
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'peer_comparison_new.php' ? 'active' : '' ?>" 
                   href="peer_comparison_new.php">
                    <i class="bi bi-people-fill"></i>
                    Peer Comparison
                </a>
            </li>
        </ul>
    </div>
</nav>
