/**
 * KPI Dashboard JavaScript
 * Uses jQuery, AJAX, Chart.js, DataTables, SweetAlert2, and Day.js
 */

let staffTable;
let performanceChart;
let categoryChart;

$(document).ready(function() {
    // Initialize dashboard
    loadDashboardData();
    
    // Year filter change
    $('#yearFilter').on('change', function() {
        const year = $(this).val();
        window.location.href = `?year=${year}`;
    });
});

/**
 * Load all dashboard data
 */
function loadDashboardData() {
    const year = $('#yearFilter').val() || new Date().getFullYear();
    
    // Load statistics
    loadStatistics(year);
    
    // Load charts
    loadCharts(year);
    
    // Load staff table
    loadStaffTable(year);
}

/**
 * Load dashboard statistics
 */
function loadStatistics(year) {
    $.ajax({
        url: '../api/kpi_api.php',
        method: 'GET',
        data: {
            action: 'get_statistics',
            year: year
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                
                // Animate numbers
                animateValue('totalStaff', 0, stats.total_staff, 1000);
                animateValue('topPerformers', 0, stats.top_performers, 1000);
                animateValue('atRisk', 0, stats.at_risk, 1000);
                
                $('#avgScore').text(stats.average_score.toFixed(2));
            } else {
                showError('Failed to load statistics');
            }
        },
        error: function() {
            showError('Error loading statistics');
        }
    });
}

/**
 * Load charts
 */
function loadCharts(year) {
    $.ajax({
        url: '../api/kpi_api.php',
        method: 'GET',
        data: {
            action: 'get_chart_data',
            year: year
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Performance Distribution Chart
                createPerformanceChart(data.performance_distribution);
                
                // Category Chart
                createCategoryChart(data.score_categories);
            }
        },
        error: function() {
            showError('Error loading charts');
        }
    });
}

/**
 * Create performance distribution chart
 */
function createPerformanceChart(data) {
    const ctx = document.getElementById('performanceChart');
    
    if (performanceChart) {
        performanceChart.destroy();
    }
    
    performanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Number of Staff',
                data: data.values,
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(118, 75, 162, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(102, 126, 234, 1)',
                    'rgba(118, 75, 162, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

/**
 * Create category pie chart
 */
function createCategoryChart(data) {
    const ctx = document.getElementById('categoryChart');
    
    if (categoryChart) {
        categoryChart.destroy();
    }
    
    categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

/**
 * Load staff table with DataTables
 */
function loadStaffTable(year) {
    if (staffTable) {
        staffTable.destroy();
    }
    
    // Show loading
    $('#staffTable tbody').html('<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Loading staff data...</div></td></tr>');
    
    // Simple AJAX call with timeout
    $.ajax({
        url: '../api/kpi_api.php',
        type: 'GET',
        timeout: 10000,
        data: {
            action: 'get_staff_performance',
            year: year
        },
        dataType: 'json',
        success: function(response) {
            console.log('Staff data response:', response);
            
            if (response.success && response.data && response.data.length > 0) {
                // Clear loading message
                $('#staffTable tbody').html('');
                
                // Initialize DataTable with real data
                staffTable = $('#staffTable').DataTable({
                    data: response.data,
                    columns: [
                        { data: 'staff_code' },
                        { data: 'name' },
                        { data: 'department' },
                        { 
                            data: 'core_competencies_score',
                            render: function(data) {
                                return data ? parseFloat(data).toFixed(2) : '0.00';
                            }
                        },
                        { 
                            data: 'kpi_achievement_score',
                            render: function(data) {
                                return data ? parseFloat(data).toFixed(2) : '0.00';
                            }
                        },
                        { 
                            data: 'final_score',
                            render: function(data) {
                                const score = parseFloat(data);
                                let badgeClass = 'bg-secondary';
                                if (score >= 4.5) badgeClass = 'bg-success';
                                else if (score >= 3.5) badgeClass = 'bg-info';
                                else if (score >= 2.5) badgeClass = 'bg-warning';
                                else if (score >= 1.5) badgeClass = 'bg-danger';
                                
                                return `<span class="badge ${badgeClass}">${score.toFixed(2)}</span>`;
                            }
                        },
                        { 
                            data: 'rating',
                            render: function(data) {
                                let badgeClass = 'bg-secondary';
                                if (data === 'Excellent') badgeClass = 'bg-success';
                                else if (data === 'Good') badgeClass = 'bg-info';
                                else if (data === 'Satisfactory') badgeClass = 'bg-warning';
                                else if (data === 'Poor' || data === 'Very Poor') badgeClass = 'bg-danger';
                                
                                return `<span class="badge ${badgeClass}">${data}</span>`;
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `
                                    <button class="btn btn-sm btn-primary view-details" data-id="${row.staff_id}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning edit-comment" data-id="${row.staff_id}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                `;
                            }
                        }
                    ],
                    order: [[5, 'desc']],
                    pageLength: 10,
                    responsive: true,
                    language: {
                        search: "Search staff:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ staff members",
                        emptyTable: "No staff data available",
                        zeroRecords: "No matching records found"
                    }
                });
                
                // Add event handlers
                $('#staffTable').on('click', '.view-details', function() {
                    const staffId = $(this).data('id');
                    viewStaffDetails(staffId);
                });
                
                $('#staffTable').on('click', '.edit-comment', function() {
                    const staffId = $(this).data('id');
                    editStaffComment(staffId);
                });
                
            } else {
                // Show no records message
                $('#staffTable tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-info-circle"></i>
                                <strong>No records found</strong> for year ${year}. 
                                <br><small>Try selecting a different year or import sample data.</small>
                            </div>
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Staff table error:', { status, error, xhr, responseText: xhr.responseText });
            let errorMessage = 'Failed to load staff data';
            
            if (status === 'timeout') {
                errorMessage = 'Request timed out. Please try again.';
            } else if (xhr.status === 404) {
                errorMessage = 'API endpoint not found.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error. Please check the console for details.';
            }
            
            $('#staffTable tbody').html(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Error:</strong> ${errorMessage}
                            <br><small>Check browser console for more details.</small>
                        </div>
                    </td>
                </tr>
            `);
        }
    });
}

/**
 * View staff details
 */
function viewStaffDetails(staffId) {
    window.location.href = `staff_profile.php?id=${staffId}`;
}

/**
 * Edit staff comment
 */
function editStaffComment(staffId) {
    const year = $('#yearFilter').val() || new Date().getFullYear();
    
    // Load current comment
    $.ajax({
        url: '../api/kpi_api.php',
        method: 'GET',
        data: {
            action: 'get_staff_comment',
            staff_id: staffId,
            year: year
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const comment = response.data;
                showCommentEditor(staffId, year, comment);
            }
        }
    });
}

/**
 * Show comment editor modal
 */
function showCommentEditor(staffId, year, comment) {
    Swal.fire({
        title: 'Edit Supervisor Comment',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Supervisor Comment:</label>
                    <textarea id="supervisorComment" class="form-control" rows="4">${comment.supervisor_comment || ''}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Training/Development Recommendation:</label>
                    <textarea id="trainingRecommendation" class="form-control" rows="4">${comment.training_recommendation || ''}</textarea>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#667eea',
        preConfirm: () => {
            const supervisorComment = $('#supervisorComment').val();
            const trainingRecommendation = $('#trainingRecommendation').val();
            
            if (!supervisorComment.trim()) {
                Swal.showValidationMessage('Supervisor comment is required');
                return false;
            }
            
            return {
                supervisor_comment: supervisorComment,
                training_recommendation: trainingRecommendation
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            saveComment(staffId, year, result.value);
        }
    });
}

/**
 * Save comment via AJAX
 */
function saveComment(staffId, year, data) {
    $.ajax({
        url: '../api/kpi_api.php',
        method: 'POST',
        data: {
            action: 'save_comment',
            staff_id: staffId,
            year: year,
            supervisor_comment: data.supervisor_comment,
            training_recommendation: data.training_recommendation
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Comment has been updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Reload table
                staffTable.ajax.reload();
            } else {
                showError(response.message || 'Failed to save comment');
            }
        },
        error: function() {
            showError('Error saving comment');
        }
    });
}

/**
 * Animate number counter
 */
function animateValue(id, start, end, duration) {
    const element = document.getElementById(id);
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(function() {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 16);
}

/**
 * Show error message
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#667eea'
    });
}

/**
 * Show success message
 */
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}
