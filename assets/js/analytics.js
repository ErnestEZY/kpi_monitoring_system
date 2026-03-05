// Analytics Dashboard JavaScript

let charts = {};
let currentFilters = {
    year: document.getElementById('filterYear').value,
    department: '',
    performance: ''
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAnalytics();
});

function applyFilters() {
    currentFilters = {
        year: document.getElementById('filterYear').value,
        department: document.getElementById('filterDepartment').value,
        performance: document.getElementById('filterPerformance').value
    };
    loadAnalytics();
}

async function loadAnalytics() {
    try {
        // Load all data
        await Promise.all([
            loadKeyMetrics(),
            loadDistributionChart(),
            loadDepartmentChart(),
            loadTrendChart(),
            loadCategoryChart(),
            loadBoxPlotChart(),
            loadStaffTable()
        ]);
    } catch (error) {
        console.error('Error loading analytics:', error);
    }
}

async function loadKeyMetrics() {
    const response = await fetch(`../api/kpi_calculations.php?action=all_staff_scores&year=${currentFilters.year}`);
    const data = await response.json();
    
    if (data.success) {
        let filteredData = filterStaffData(data.data);
        
        // Total staff
        document.getElementById('totalStaff').textContent = filteredData.length;
        
        // Average score
        const scores = filteredData.filter(s => s.has_data).map(s => s.overall_score);
        const avgScore = scores.length > 0 ? (scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1) : 0;
        document.getElementById('avgScore').textContent = avgScore + '%';
        
        // Top performers
        const topPerformers = filteredData.filter(s => s.overall_score >= 85).length;
        document.getElementById('topPerformers').textContent = topPerformers;
        
        // At-risk (score < 70 or critical)
        const atRisk = filteredData.filter(s => s.overall_score < 70 && s.has_data).length;
        document.getElementById('atRisk').textContent = atRisk;
    }
}

async function loadDistributionChart() {
    const response = await fetch(`../api/kpi_calculations.php?action=performance_distribution&year=${currentFilters.year}`);
    const data = await response.json();
    
    if (data.success) {
        const ctx = document.getElementById('distributionChart').getContext('2d');
        
        if (charts.distribution) {
            charts.distribution.destroy();
        }
        
        charts.distribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(data.data),
                datasets: [{
                    data: Object.values(data.data),
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8',
                        '#ffc107',
                        '#fd7e14',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}

async function loadDepartmentChart() {
    const response = await fetch(`../api/kpi_calculations.php?action=all_staff_scores&year=${currentFilters.year}`);
    const data = await response.json();
    
    if (data.success) {
        // Group by department
        const deptScores = {};
        data.data.forEach(staff => {
            if (staff.has_data) {
                if (!deptScores[staff.department]) {
                    deptScores[staff.department] = [];
                }
                deptScores[staff.department].push(staff.overall_score);
            }
        });
        
        // Calculate averages
        const deptAverages = {};
        Object.keys(deptScores).forEach(dept => {
            const scores = deptScores[dept];
            deptAverages[dept] = (scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1);
        });
        
        const ctx = document.getElementById('departmentChart').getContext('2d');
        
        if (charts.department) {
            charts.department.destroy();
        }
        
        charts.department = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(deptAverages),
                datasets: [{
                    label: 'Average Score',
                    data: Object.values(deptAverages),
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

async function loadTrendChart() {
    // Get all years data
    const years = [];
    const yearSelect = document.getElementById('filterYear');
    for (let option of yearSelect.options) {
        years.push(option.value);
    }
    years.sort();
    
    const trendData = {};
    
    for (const year of years) {
        const response = await fetch(`../api/kpi_calculations.php?action=all_staff_scores&year=${year}`);
        const data = await response.json();
        
        if (data.success) {
            const scores = data.data.filter(s => s.has_data).map(s => s.overall_score);
            if (scores.length > 0) {
                trendData[year] = {
                    avg: (scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1),
                    min: Math.min(...scores).toFixed(1),
                    max: Math.max(...scores).toFixed(1)
                };
            }
        }
    }
    
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    if (charts.trend) {
        charts.trend.destroy();
    }
    
    charts.trend = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Object.keys(trendData),
            datasets: [
                {
                    label: 'Average Score',
                    data: Object.values(trendData).map(d => d.avg),
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 5
                },
                {
                    label: 'Maximum Score',
                    data: Object.values(trendData).map(d => d.max),
                    borderColor: '#1cc88a',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    borderDash: [5, 5],
                    pointRadius: 3
                },
                {
                    label: 'Minimum Score',
                    data: Object.values(trendData).map(d => d.min),
                    borderColor: '#e74a3b',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    borderDash: [5, 5],
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

async function loadCategoryChart() {
    const response = await fetch(`../api/kpi_calculations.php?action=category_averages&year=${currentFilters.year}`);
    const data = await response.json();
    
    if (data.success) {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        
        if (charts.category) {
            charts.category.destroy();
        }
        
        charts.category = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: data.data.map(c => c.category),
                datasets: [{
                    label: 'Average Score',
                    data: data.data.map(c => c.average),
                    backgroundColor: 'rgba(78, 115, 223, 0.2)',
                    borderColor: '#4e73df',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

async function loadBoxPlotChart() {
    const response = await fetch(`../api/kpi_calculations.php?action=all_staff_scores&year=${currentFilters.year}`);
    const data = await response.json();
    
    if (data.success) {
        const scores = data.data.filter(s => s.has_data).map(s => s.overall_score);
        
        if (scores.length === 0) {
            return;
        }
        
        // Sort scores
        scores.sort((a, b) => a - b);
        
        // Calculate quartiles
        const q1Index = Math.floor(scores.length * 0.25);
        const q2Index = Math.floor(scores.length * 0.5);
        const q3Index = Math.floor(scores.length * 0.75);
        
        const min = scores[0];
        const q1 = scores[q1Index];
        const median = scores[q2Index];
        const q3 = scores[q3Index];
        const max = scores[scores.length - 1];
        
        // Create score ranges for histogram
        const ranges = [
            { label: '0-20', min: 0, max: 20, count: 0 },
            { label: '21-40', min: 21, max: 40, count: 0 },
            { label: '41-60', min: 41, max: 60, count: 0 },
            { label: '61-80', min: 61, max: 80, count: 0 },
            { label: '81-100', min: 81, max: 100, count: 0 }
        ];
        
        scores.forEach(score => {
            for (let range of ranges) {
                if (score >= range.min && score <= range.max) {
                    range.count++;
                    break;
                }
            }
        });
        
        const ctx = document.getElementById('boxPlotChart').getContext('2d');
        
        if (charts.boxPlot) {
            charts.boxPlot.destroy();
        }
        
        charts.boxPlot = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ranges.map(r => r.label),
                datasets: [{
                    label: 'Number of Staff',
                    data: ranges.map(r => r.count),
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#17a2b8',
                        '#28a745'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Number of Staff'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Score Range (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed.y / total) * 100).toFixed(1);
                                return `${percentage}% of total staff`;
                            },
                            footer: function(tooltipItems) {
                                return `\nStatistics:\nMin: ${min.toFixed(1)}%\nQ1: ${q1.toFixed(1)}%\nMedian: ${median.toFixed(1)}%\nQ3: ${q3.toFixed(1)}%\nMax: ${max.toFixed(1)}%`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: `Score Distribution (Min: ${min.toFixed(1)}%, Median: ${median.toFixed(1)}%, Max: ${max.toFixed(1)}%)`,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        });
    }
}

async function loadStaffTable() {
    const response = await fetch(`../api/kpi_calculations.php?action=all_staff_scores&year=${currentFilters.year}`);
    const data = await response.json();
    
    if (data.success) {
        let filteredData = filterStaffData(data.data);
        
        const tbody = document.getElementById('staffTableBody');
        tbody.innerHTML = '';
        
        if (filteredData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i>
                            <strong>No records found</strong> for the selected criteria.
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        filteredData.forEach(staff => {
            // Get trend
            fetch(`../api/kpi_calculations.php?action=performance_trend&staff_id=${staff.staff_id}`)
                .then(res => res.json())
                .then(trendData => {
                    if (trendData.success && trendData.data.length > 1) {
                        const latest = trendData.data[trendData.data.length - 1];
                        const trend = latest.trend;
                        
                        const trendIcon = trend === 'improving' ? '↑' : (trend === 'declining' ? '↓' : '→');
                        const trendColor = trend === 'improving' ? 'success' : (trend === 'declining' ? 'danger' : 'secondary');
                        
                        const row = `
                            <tr>
                                <td>${staff.staff_number}</td>
                                <td>${staff.full_name}</td>
                                <td>${staff.department}</td>
                                <td><strong>${staff.overall_score}%</strong></td>
                                <td><span class="badge bg-${staff.badge_class}">${staff.classification}</span></td>
                                <td><span class="badge bg-${trendColor}">${trendIcon} ${trend}</span></td>
                                <td>
                                    <a href="staff_profile.php?id=${staff.staff_id}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    }
                });
        });
    }
}

function filterStaffData(data) {
    let filtered = data;
    
    // Filter by department
    if (currentFilters.department) {
        filtered = filtered.filter(s => s.department_id == currentFilters.department);
    }
    
    // Filter by performance level
    if (currentFilters.performance) {
        switch (currentFilters.performance) {
            case 'top':
                filtered = filtered.filter(s => s.overall_score >= 85);
                break;
            case 'good':
                filtered = filtered.filter(s => s.overall_score >= 75 && s.overall_score < 85);
                break;
            case 'satisfactory':
                filtered = filtered.filter(s => s.overall_score >= 65 && s.overall_score < 75);
                break;
            case 'needs_improvement':
                filtered = filtered.filter(s => s.overall_score >= 50 && s.overall_score < 65);
                break;
            case 'critical':
                filtered = filtered.filter(s => s.overall_score < 50);
                break;
        }
    }
    
    return filtered;
}
