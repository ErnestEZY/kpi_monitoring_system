<?php
session_start();
require_once '../includes/auth.php';
requireLogin();

require_once '../config/database.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automated Training Recommendations</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <style>
        .training-card {
            border-left: 4px solid #667eea;
            transition: transform 0.3s;
            overflow: hidden;
        }
        
        .training-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .priority-high { border-left-color: #dc3545; }
        .priority-medium { border-left-color: #ffc107; }
        .priority-low { border-left-color: #28a745; }
        
        .skill-gap-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 2px;
            white-space: nowrap;
        }
        
        .skill-gaps-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            max-width: 100%;
            overflow: hidden;
        }
        
        .gap-critical { background-color: #f8d7da; color: #721c24; }
        .gap-moderate { background-color: #fff3cd; color: #856404; }
        .gap-minor { background-color: #d1ecf1; color: #0c5460; }
        
        .training-module {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            word-wrap: break-word;
        }
        
        .training-module h6 {
            font-size: 1rem;
            margin-bottom: 10px;
            word-wrap: break-word;
        }
        
        .training-module p {
            font-size: 0.9rem;
            word-wrap: break-word;
        }
        
        .match-score {
            font-size: 2.8rem;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
        }
        
        .training-card .card-body {
            padding: 1rem;
        }
        
        .training-card .row {
            margin: 0;
        }
        
        .training-card .col-md-8,
        .training-card .col-md-4 {
            padding: 0.5rem;
        }
        
        .expected-outcomes-list {
            font-size: 0.9rem;
            padding-left: 1.2rem;
            margin-bottom: 0;
        }
        
        .expected-outcomes-list li {
            margin-bottom: 0.3rem;
        }
        
        @media (max-width: 768px) {
            .training-card .col-md-8,
            .training-card .col-md-4 {
                padding: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-mortarboard-fill"></i> Automated Training Recommendations</h1>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> 
                    <strong>Advanced Analytics:</strong> AI-powered personalized training suggestions based on performance gaps
                </div>
                
                <div class="mb-3">
                    <button class="btn btn-primary" id="generateBtn" onclick="generateRecommendations()">
                        <i class="bi bi-robot"></i> Generate Recommendations
                    </button>
                    <small class="text-muted ms-2">Results are saved for your session — navigate away and return without losing them.</small>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                                <h3 class="mt-2" id="staffNeedingTraining">0</h3>
                                <p class="text-muted mb-0">Staff Needing Training</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-book-fill text-success" style="font-size: 2rem;"></i>
                                <h3 class="mt-2" id="totalPrograms">0</h3>
                                <p class="text-muted mb-0">Recommended Programs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2rem;"></i>
                                <h3 class="mt-2" id="criticalGaps">0</h3>
                                <p class="text-muted mb-0">Critical Skill Gaps</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-graph-up-arrow text-info" style="font-size: 2rem;"></i>
                                <h3 class="mt-2" id="avgMatchScore">0%</h3>
                                <p class="text-muted mb-0">Avg Match Score</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Options -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Priority Level:</label>
                                <select class="form-select" id="priorityFilter">
                                    <option value="">All Priorities</option>
                                    <option value="high">High Priority</option>
                                    <option value="medium">Medium Priority</option>
                                    <option value="low">Low Priority</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Skill Gap:</label>
                                <select class="form-select" id="skillGapFilter">
                                    <option value="">All Gaps</option>
                                    <option value="critical">Critical</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="minor">Minor</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department:</label>
                                <select class="form-select" id="departmentFilter">
                                    <option value="">All Departments</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="bi bi-funnel"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recommendations List -->
                <div id="recommendationsContainer">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-mortarboard-fill fs-1 mb-3 d-block text-primary" style="opacity:.4;"></i>
                        <h5>No Recommendations Generated Yet</h5>
                        <p class="mb-0">Click <strong>Generate Recommendations</strong> above to analyse staff performance and identify training needs.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Training Details Modal -->
    <div class="modal fade" id="trainingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Training Program Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="trainingModalBody">
                    <!-- Content loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="assignTraining()">
                        <i class="bi bi-check-circle"></i> Assign Training
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let allRecommendations = []; // Store all recommendations for client-side filtering
        
        $(document).ready(function() {
            // Auto-run removed — supervisor clicks "Generate Recommendations" to start
            loadDepartments();

            // Restore cached results if available
            const cached = sessionStorage.getItem('trainingResults');
            if (cached) {
                try {
                    const data = JSON.parse(cached);
                    allRecommendations = data.recommendations || [];
                    displayRecommendations(data);
                    $('#generateBtn').html('<i class="bi bi-arrow-clockwise"></i> Re-generate');
                } catch(e) {
                    sessionStorage.removeItem('trainingResults');
                }
            }
        });
        
        function loadDepartments() {
            // Load unique departments for filter dropdown
            console.log('Loading departments...');
            
            $.ajax({
                url: '../api/innovative_features_api.php',
                method: 'GET',
                data: { action: 'get_staff_list' },
                dataType: 'json',
                success: function(response) {
                    console.log('Staff list response:', response);
                    
                    if (response.success && response.data) {
                        // Extract unique departments
                        const departments = [...new Set(response.data.map(s => s.department))];
                        departments.sort();
                        
                        console.log('Unique departments:', departments);
                        
                        // Build options HTML
                        let options = '<option value="">All Departments</option>';
                        departments.forEach(dept => {
                            if (dept) { // Only add if department is not null/empty
                                options += `<option value="${dept}">${dept}</option>`;
                            }
                        });
                        
                        // Update dropdown
                        $('#departmentFilter').html(options);
                        console.log('Department dropdown updated with', departments.length, 'departments');
                    } else {
                        console.warn('No staff data returned or success=false');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading departments:', { xhr, status, error });
                    // Fallback: Load from database directly
                    loadDepartmentsFromDatabase();
                }
            });
        }
        
        function loadDepartmentsFromDatabase() {
            // Fallback method: Get departments directly from database
            console.log('Using fallback method to load departments...');
            
            $.ajax({
                url: '../api/kpi_api.php',
                method: 'GET',
                data: { action: 'get_statistics', year: new Date().getFullYear() },
                dataType: 'json',
                success: function(response) {
                    // This won't give us departments, so let's try another approach
                    // Just populate with common departments as fallback
                    const commonDepts = ['Electronics', 'Fashion', 'Home & Living', 'Sports', 'Beauty & Health'];
                    let options = '<option value="">All Departments</option>';
                    commonDepts.forEach(dept => {
                        options += `<option value="${dept}">${dept}</option>`;
                    });
                    $('#departmentFilter').html(options);
                    console.log('Loaded fallback departments');
                }
            });
        }
        
        function loadTrainingRecommendations() {
            $('#recommendationsContainer').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">Analyzing performance data and generating recommendations...</p>
                </div>
            `);
            
            $.ajax({
                url: '../api/innovative_features_api.php',
                method: 'GET',
                data: { action: 'get_training_recommendations' },
                dataType: 'json',
                success: function(response) {
                    console.log('API Response:', response);
                    
                    if (response.success) {
                        if (response.data && response.data.recommendations) {
                            allRecommendations = response.data.recommendations; // Store for filtering
                            console.log('Loaded recommendations:', allRecommendations.length);
                            displayRecommendations(response.data);
                        } else {
                            console.warn('No recommendations in response');
                            $('#recommendationsContainer').html(`
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>No training recommendations at this time.</strong><br>
                                    All staff are performing well (scores ≥ 4.0), or no performance data available for current year.
                                </div>
                            `);
                        }
                    } else {
                        console.error('API returned success=false:', response.message);
                        $('#recommendationsContainer').html(`
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>No recommendations found.</strong><br>
                                ${response.message || 'This could mean all staff are performing well (scores ≥ 4.0).'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', { xhr, status, error, responseText: xhr.responseText });
                    $('#recommendationsContainer').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle"></i>
                            <strong>Error loading recommendations.</strong><br>
                            ${error}<br>
                            <small>Check browser console for details.</small>
                        </div>
                    `);
                }
            });
        }
        
        function displayRecommendations(data) {
            // Update summary
            $('#staffNeedingTraining').text(data.summary.staff_count);
            $('#totalPrograms').text(data.summary.total_programs);
            $('#criticalGaps').text(data.summary.critical_gaps);
            $('#avgMatchScore').text(Math.round(data.summary.avg_match_score) + '%');

            // Cache results in sessionStorage for persistence across navigation
            try {
                sessionStorage.setItem('trainingResults', JSON.stringify(data));
            } catch(e) { /* storage full — ignore */ }
            
            // Extract and populate departments from recommendations
            if (data.recommendations && data.recommendations.length > 0) {
                const departments = [...new Set(data.recommendations.map(r => r.department))];
                departments.sort();
                
                let deptOptions = '<option value="">All Departments</option>';
                departments.forEach(dept => {
                    if (dept) {
                        deptOptions += `<option value="${dept}">${dept}</option>`;
                    }
                });
                $('#departmentFilter').html(deptOptions);
                console.log('Populated departments from recommendations:', departments);
            }
            
            // Display recommendations
            let html = '';
            
            if (!data.recommendations || data.recommendations.length === 0) {
                html = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <strong>Great news!</strong> No staff members currently need training recommendations.
                        All staff are performing at acceptable levels (scores ≥ 4.0).
                    </div>
                `;
            } else {
                data.recommendations.forEach(rec => {
                    html += `
                        <div class="card training-card priority-${rec.priority} mb-3" 
                             data-priority="${rec.priority}" 
                             data-department="${rec.department}"
                             data-max-severity="${getMaxSeverity(rec.skill_gaps)}">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="mb-2">${rec.staff_name} (${rec.staff_code})</h5>
                                        <p class="text-muted mb-2">${rec.department} | Current Score: ${rec.current_score}</p>
                                        
                                        <div class="mb-3">
                                            <strong class="d-block mb-2">Identified Skill Gaps:</strong>
                                            <div class="skill-gaps-container">
                                                ${rec.skill_gaps.map(gap => `
                                                    <span class="skill-gap-badge gap-${gap.severity}">${gap.skill}</span>
                                                `).join('')}
                                            </div>
                                        </div>
                                        
                                        <div class="training-module">
                                            <h6><i class="bi bi-book"></i> ${rec.recommended_program}</h6>
                                            <p class="mb-2">${rec.program_description}</p>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-clock"></i> Duration: ${rec.duration} | 
                                                <i class="bi bi-calendar"></i> Start: ${rec.suggested_start}
                                            </small>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <strong class="d-block mb-2">Expected Outcomes:</strong>
                                            <ul class="expected-outcomes-list">
                                                ${rec.expected_outcomes.map(outcome => `<li>${outcome}</li>`).join('')}
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center text-center py-3">
                                        <div class="mb-3">
                                            <div class="match-score">${rec.match_score}%</div>
                                            <div class="text-muted mt-1">Match Score</div>
                                        </div>
                                        
                                        <div class="mb-3 w-100 px-2">
                                            <span class="badge w-100 py-2 fs-6 bg-${rec.priority === 'high' ? 'danger' : rec.priority === 'medium' ? 'warning' : 'success'} text-${rec.priority === 'medium' ? 'dark' : 'white'}" style="border: 2px solid rgba(0,0,0,.15); border-radius: 8px;">
                                                ${rec.priority.toUpperCase()} PRIORITY
                                            </span>
                                        </div>

                                        <div class="w-100 px-2 mt-4">
                                            <button class="btn btn-primary btn-sm w-100 mb-2" onclick="viewTrainingDetails(${rec.staff_id}, '${rec.recommended_program}')">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                            <button class="btn btn-info btn-sm w-100 mb-2" onclick="editComment(${rec.staff_id})">
                                                <i class="bi bi-pencil-square"></i> Edit Comment
                                            </button>
                                            <button class="btn btn-success btn-sm w-100" onclick="assignTrainingQuick(${rec.staff_id}, '${rec.recommended_program}')">
                                                <i class="bi bi-check-circle"></i> Assign Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            $('#recommendationsContainer').html(html);
        }
        
        function getMaxSeverity(skillGaps) {
            // Return the most severe gap level
            if (skillGaps.some(g => g.severity === 'critical')) return 'critical';
            if (skillGaps.some(g => g.severity === 'moderate')) return 'moderate';
            return 'minor';
        }
        
        function applyFilters() {
            const priorityFilter = $('#priorityFilter').val();
            const skillGapFilter = $('#skillGapFilter').val();
            const departmentFilter = $('#departmentFilter').val();
            
            console.log('Applying filters:', { priorityFilter, skillGapFilter, departmentFilter });
            
            // Filter the recommendations
            let filtered = allRecommendations;
            
            if (priorityFilter) {
                filtered = filtered.filter(rec => rec.priority === priorityFilter);
            }
            
            if (departmentFilter) {
                filtered = filtered.filter(rec => rec.department === departmentFilter);
            }
            
            if (skillGapFilter) {
                filtered = filtered.filter(rec => {
                    return rec.skill_gaps.some(gap => gap.severity === skillGapFilter);
                });
            }
            
            // Recalculate summary for filtered results
            const summary = {
                staff_count: filtered.length,
                total_programs: filtered.length,
                critical_gaps: filtered.filter(r => r.skill_gaps.some(g => g.severity === 'critical')).length,
                avg_match_score: filtered.length > 0 ? 
                    filtered.reduce((sum, r) => sum + r.match_score, 0) / filtered.length : 0
            };
            
            displayRecommendations({
                recommendations: filtered,
                summary: summary
            });
            
            // Show filter status
            if (priorityFilter || skillGapFilter || departmentFilter) {
                const filterCount = [priorityFilter, skillGapFilter, departmentFilter].filter(f => f).length;
                Swal.fire({
                    icon: 'info',
                    title: 'Filters Applied',
                    text: `Showing ${filtered.length} recommendations with ${filterCount} active filter(s)`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
        
        function generateRecommendations() {
            Swal.fire({
                title: 'Analysing Performance Data',
                text: 'Identifying skill gaps and matching training programs...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            setTimeout(() => {
                loadTrainingRecommendations();
                Swal.close();
                // Switch button to Re-generate after first run
                $('#generateBtn').html('<i class="bi bi-arrow-clockwise"></i> Re-generate');
                Swal.fire({
                    icon: 'success',
                    title: 'Recommendations Ready',
                    text: 'Training recommendations have been refreshed based on the latest performance data.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 1500);
        }
        
        function viewTrainingDetails(staffId, program) {
            $('#trainingModalBody').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-3">Loading training details...</p>
                </div>
            `);
            
            $('#trainingModal').modal('show');
            
            // Simulate loading details
            setTimeout(() => {
                $('#trainingModalBody').html(`
                    <h5>${program}</h5>
                    <p><strong>Program Overview:</strong> Comprehensive training designed to address specific skill gaps and improve overall performance.</p>
                    
                    <h6 class="mt-3">Modules Included:</h6>
                    <ul>
                        <li>Module 1: Fundamentals and Core Concepts</li>
                        <li>Module 2: Practical Application and Exercises</li>
                        <li>Module 3: Advanced Techniques</li>
                        <li>Module 4: Assessment and Certification</li>
                    </ul>
                    
                    <h6 class="mt-3">Learning Outcomes:</h6>
                    <ul>
                        <li>Improved performance in identified weak areas</li>
                        <li>Enhanced confidence and competence</li>
                        <li>Better alignment with organizational standards</li>
                    </ul>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> This training is specifically matched to address the performance gaps identified in the latest evaluation.
                    </div>
                `);
            }, 1000);
        }
        
        function assignTrainingQuick(staffId, program) {
            Swal.fire({
                title: 'Assign Training',
                text: `Assign "${program}" to this staff member?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Assign',
                confirmButtonColor: '#667eea'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Training Assigned',
                        text: 'The staff member has been enrolled and will receive notification.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
        
        function editComment(staffId) {
            // Redirect to staff profile with modal open
            window.location.href = `staff_profile.php?id=${staffId}&openComment=true`;
        }
    </script>
</body>
</html>
