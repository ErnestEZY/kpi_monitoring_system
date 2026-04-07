<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$supervisor_name = $_SESSION['supervisor_name'] ?? 'Supervisor';
$login_time = $_SESSION['login_time'] ?? null;

// Format login time
$login_display = '';
if ($login_time) {
    $login_datetime = new DateTime($login_time);
    $login_display = $login_datetime->format('M d, Y g:i A');
}
?>
<nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">
        <i class="bi bi-graph-up-arrow"></i> KPI Monitor
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
            data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <!-- Login Time — centred in the remaining navbar space -->
    <?php if ($login_display): ?>
    <div class="flex-grow-1 text-center d-none d-md-block">
        <small class="text-white-50">
            <i class="bi bi-clock me-1"></i> Logged in: <span class="text-white"><?= $login_display ?></span>
        </small>
    </div>
    <?php else: ?>
    <div class="flex-grow-1"></div>
    <?php endif; ?>
    
    <div class="navbar-nav flex-row">
        <div class="nav-item dropdown">
            <a class="nav-link px-3 dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" 
               data-bs-toggle="dropdown" aria-expanded="false" style="white-space: nowrap;">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($supervisor_name) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                <?php if ($login_display): ?>
                <li><h6 class="dropdown-header">
                    <i class="bi bi-clock-history"></i> Login Session
                </h6></li>
                <li><span class="dropdown-item-text small text-muted">
                    <?= $login_display ?>
                </span></li>
                <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="../auth/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a></li>
            </ul>
        </div>
    </div>
</nav>
