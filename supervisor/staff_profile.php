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
                        </div>
                    </div>
                </div>

                <!-- AI-Generated Narrative -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-robot"></i> AI-Generated Performance Narrative
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="narrative-content">
                            <?= $narrative ?>
                        </div>
                    </div>
                </div>

                <!-- Smart Comment Assistant with Alpine.js -->
                <div x-data="commentAssistant(<?= $staff_id ?>, <?= $_SESSION['supervisor_id'] ?>)" class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="m-0">
                            <i class="bi bi-magic"></i> Smart Comment Assistant
                            <span class="badge bg-light text-dark ms-2">
                                <i class="bi bi-lightning-charge-fill"></i> Powered by Alpine.js
                            </span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Year Selection -->
                        <div class="mb-3">
                            <label class="form-label">Evaluation Year:</label>
                            <select x-model="selectedYear" class="form-select">
                                <?php for ($y = date('Y'); $y >= 2022; $y--): ?>
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
                                            class="btn btn-outline-primary w-100 text-start"
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
                                @input="debouncedSave()"
                                class="form-control" 
                                rows="5"
                                placeholder="Type your comment or select a template above..."
                                maxlength="1000">
                            </textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    <span x-text="comment.length"></span> / 1000 characters
                                </small>
                                <div>
                                    <span x-show="isSaving" class="text-primary">
                                        <i class="bi bi-cloud-upload"></i> Saving...
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Training Recommendation -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Training Recommendation:</label>
                            <textarea 
                                x-model="training" 
                                @input="debouncedSave()"
                                class="form-control" 
                                rows="3"
                                placeholder="Recommend specific training programs..."
                                maxlength="500">
                            </textarea>
                            <small class="text-muted">
                                <span x-text="training.length"></span> / 500 characters
                            </small>
                        </div>
                        
                        <!-- Preview -->
                        <div x-show="comment.length > 0 || training.length > 0" 
                             x-transition
                             class="alert alert-light border">
                            <strong><i class="bi bi-eye"></i> Preview:</strong>
                            <div x-show="comment.length > 0" class="mt-2">
                                <strong class="text-primary">Comment:</strong>
                                <p x-text="comment" class="mb-2"></p>
                            </div>
                            <div x-show="training.length > 0" class="mt-2">
                                <strong class="text-success">Training:</strong>
                                <p x-text="training" class="mb-0"></p>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button 
                                type="button" 
                                @click="saveComment()" 
                                class="btn btn-primary"
                                :disabled="comment.length === 0 || isSaving">
                                <i class="bi bi-save"></i> Save Now
                            </button>
                            <button 
                                type="button" 
                                @click="loadExistingComment()" 
                                class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Load Existing
                            </button>
                            <button 
                                type="button" 
                                @click="clearAll()" 
                                class="btn btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
                    console.log('Alpine component initialized!');
                    console.log('Staff ID:', this.staffId);
                    console.log('Supervisor ID:', this.supervisorId);
                    
                    // Load existing comment silently (no notification)
                    this.loadExistingCommentSilent();
                },
                
                // Watch for year changes
                watch: {
                    selectedYear() {
                        this.loadExistingCommentSilent();
                    }
                },
                
                async loadExistingCommentSilent() {
                    try {
                        const year = this.selectedYear || new Date().getFullYear();
                        console.log('Silently loading comment for year:', year);
                        
                        const response = await fetch(`../api/kpi_api.php?action=get_staff_comment&staff_id=${this.staffId}&year=${year}`);
                        const data = await response.json();
                        
                        if (data.success && data.data) {
                            // Check if comment actually has content
                            if (data.data.supervisor_comment || data.data.training_recommendation) {
                                this.comment = data.data.supervisor_comment || '';
                                this.training = data.data.training_recommendation || '';
                                console.log('Comment loaded silently');
                            } else {
                                // Comment exists but is empty
                                this.comment = '';
                                this.training = '';
                                console.log('No comment content found');
                            }
                        } else {
                            // No comment found for this year
                            this.comment = '';
                            this.training = '';
                            console.log('No comment found for year:', year);
                        }
                    } catch (error) {
                        console.error('Failed to load comment silently:', error);
                        this.comment = '';
                        this.training = '';
                    }
                },
                
                async loadExistingComment() {
                    try {
                        const year = this.selectedYear || new Date().getFullYear();
                        
                        // Show loading notification
                        const loadingToast = Swal.fire({
                            title: 'Loading...',
                            text: `Fetching comment for ${year}`,
                            icon: 'info',
                            showConfirmButton: false,
                            timer: 500,
                            timerProgressBar: true
                        });
                        
                        const response = await fetch(`../api/kpi_api.php?action=get_staff_comment&staff_id=${this.staffId}&year=${year}`);
                        const data = await response.json();
                        
                        if (data.success && data.data) {
                            // Check if comment actually has content
                            if (data.data.supervisor_comment || data.data.training_recommendation) {
                                this.comment = data.data.supervisor_comment || '';
                                this.training = data.data.training_recommendation || '';
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Comment Loaded!',
                                    text: `Successfully loaded comment for ${year}`,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                // Comment exists but is empty
                                this.comment = '';
                                this.training = '';
                                
                                Swal.fire({
                                    icon: 'info',
                                    title: 'No Comment Found',
                                    text: `No comment exists for ${year}`,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        } else {
                            // No comment found for this year
                            this.comment = '';
                            this.training = '';
                            
                            Swal.fire({
                                icon: 'info',
                                title: 'No Comment Found',
                                text: `No comment exists for ${year}`,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    } catch (error) {
                        console.error('Failed to load comment:', error);
                        this.comment = '';
                        this.training = '';
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Loading Comment',
                            text: 'Failed to fetch comment from server',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                
                debouncedSave() {
                    // Debounce save to avoid too many API calls
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = setTimeout(() => {
                        this.saveComment();
                    }, 1000);
                },
                
                async saveComment() {
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
                                text: `Comment for ${year} has been saved successfully`,
                                timer: 2000,
                                showConfirmButton: false
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
                        },
                        {
                            name: 'Sales Performance',
                            comment: 'Sales targets met but could improve in upselling and customer retention.',
                            training: 'Advanced sales techniques and relationship building workshop.'
                        }
                    ];
                },
                
                applyTemplate(template) {
                    this.comment = template.comment;
                    this.training = template.training;
                    this.debouncedSave();
                },
                
                clearAll() {
                    this.comment = '';
                    this.training = '';
                }
            }
        }
    </script>
</body>
</html>
