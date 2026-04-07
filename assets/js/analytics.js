// Analytics Dashboard JavaScript

let charts = {};
let currentFilters = {
    year: document.getElementById('filterYear').value,
    department: '',
    performance: ''
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
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
    // Run all chart loaders in parallel; individual failures are caught inside each function
    await Promise.allSettled([
        loadKeyMetrics(),
        loadDistributionChart(),
        loadDepartmentChart(),
        loadTrendChart(),
        loadCategoryChart(),
        loadBoxPlotChart(),
        loadStaffTable(),
    ]);
}

async function loadKeyMetrics() {
    try {
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
    } catch (error) {
        console.error('[loadKeyMetrics]', error);
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
            },
            plugins: [{
                id: 'centerText',
                beforeDraw: function(chart) {
                    const { top, bottom, left, right } = chart.chartArea;
                    const centerX = (left + right) / 2;
                    const centerY = (top + bottom) / 2;
                    const ctx = chart.ctx;
                    ctx.restore();
                    
                    const fontSize = ((bottom - top) / 180).toFixed(2);
                    ctx.font = `bold ${fontSize}em sans-serif`;
                    ctx.textBaseline = "middle";
                    ctx.textAlign = "center";
                    ctx.fillStyle = "#999";
                    
                    const text = "Year " + currentFilters.year;
                    ctx.fillText(text, centerX, centerY);
                    ctx.save();
                }
            }]
        });
        
        // Generate storytelling
        const total = Object.values(data.data).reduce((a, b) => a + b, 0);
        const topPerformers = data.data['Top Performer'] || 0;
        const critical = data.data['Critical'] || 0;
        const topPercentage = ((topPerformers / total) * 100).toFixed(1);
        const criticalPercentage = ((critical / total) * 100).toFixed(1);
        
        let story = '';
        if (topPerformers >= total * 0.3) {
            story = `Excellent! ${topPercentage}% of your team are top performers. This indicates strong overall performance and effective training programs.`;
        } else if (critical >= total * 0.2) {
            story = `Alert: ${criticalPercentage}% of staff are in critical performance levels. Immediate intervention and targeted training programs are recommended.`;
        } else {
            story = `Your team shows a balanced distribution. Focus on moving "Satisfactory" performers to "Good Performer" level through targeted coaching.`;
        }
        
        document.getElementById('distributionStory').textContent = story;
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
        
        // Generate storytelling
        const deptScoresArray = Object.entries(deptAverages).map(([dept, score]) => ({dept, score: parseFloat(score)}));
        deptScoresArray.sort((a, b) => b.score - a.score);
        
        const highest = deptScoresArray[0];
        const lowest = deptScoresArray[deptScoresArray.length - 1];
        const gap = (highest.score - lowest.score).toFixed(1);
        
        let story = '';
        if (gap > 15) {
            story = `Significant performance gap detected: ${highest.dept} (${highest.score}%) outperforms ${lowest.dept} (${lowest.score}%) by ${gap}%. Consider cross-department knowledge sharing or investigate resource allocation.`;
        } else if (gap < 5) {
            story = `Consistent performance across departments with only ${gap}% variation. This indicates standardized training and processes are working well.`;
        } else {
            story = `${highest.dept} leads with ${highest.score}%, while ${lowest.dept} has room for improvement at ${lowest.score}%. Focus training resources on lower-performing departments.`;
        }
        
        document.getElementById('departmentStory').textContent = story;
    }
}

async function loadTrendChart() {
    try {
        const years = Array.from(document.getElementById('filterYear').options)
            .map(o => o.value)
            .sort();

        // Fetch all years in parallel instead of sequentially
        const responses = await Promise.all(
            years.map(year =>
                fetch(`../api/kpi_calculations.php?action=all_staff_scores&year=${year}`)
                    .then(r => r.json())
                    .then(data => ({ year, data }))
                    .catch(() => ({ year, data: null }))
            )
        );

        const trendData = {};
        for (const { year, data } of responses) {
            if (!data?.success) continue;
            const scores = data.data.filter(s => s.has_data).map(s => s.overall_score);
            if (scores.length > 0) {
                trendData[year] = {
                    avg: (scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1),
                    min: Math.min(...scores).toFixed(1),
                    max: Math.max(...scores).toFixed(1),
                };
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
    
    // Generate storytelling
    const yearsArray = Object.keys(trendData);
    if (yearsArray.length >= 2) {
        const firstYear = yearsArray[0];
        const lastYear = yearsArray[yearsArray.length - 1];
        const firstAvg = parseFloat(trendData[firstYear].avg);
        const lastAvg = parseFloat(trendData[lastYear].avg);
        const change = (lastAvg - firstAvg).toFixed(1);
        
        let story = '';
        if (change > 5) {
            story = `Positive trend: Team performance improved by ${change}% from ${firstYear} (${firstAvg}%) to ${lastYear} (${lastAvg}%). Your training initiatives are showing measurable results.`;
        } else if (change < -5) {
            story = `Concerning trend: Performance declined by ${Math.abs(change)}% from ${firstYear} to ${lastYear}. Immediate review of training programs and staff support systems recommended.`;
        } else {
            story = `Stable performance: Team maintains consistent scores around ${lastAvg}% over ${yearsArray.length} years. Consider setting new performance targets to drive improvement.`;
        }
        
        document.getElementById('trendStory').textContent = story;
        }
    } catch (error) {
        console.error('[loadTrendChart]', error);
    }
}

async function loadCategoryChart() {
    try {
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
        
        // Generate storytelling
        const categories = data.data.map(c => ({name: c.category, score: parseFloat(c.average)}));
        categories.sort((a, b) => b.score - a.score);
        
        const strongest = categories[0];
        const weakest = categories[categories.length - 1];
        
        let story = `Team excels in ${strongest.name} (${strongest.score.toFixed(1)}%) but needs improvement in ${weakest.name} (${weakest.score.toFixed(1)}%). `;
        story += `Allocate training resources to strengthen weaker categories while maintaining excellence in top-performing areas.`;
        
        document.getElementById('categoryStory').textContent = story;
        }
    } catch (error) {
        console.error('[loadCategoryChart]', error);
    }
}

async function loadBoxPlotChart() {
    try {
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
        
        // Generate storytelling
        const range = max - min;
        const belowMedian = scores.filter(s => s < median).length;
        const aboveMedian = scores.filter(s => s > median).length;
        
        let story = '';
        if (range > 50) {
            story = `Wide performance range (${range.toFixed(1)}% spread) indicates inconsistent skill levels. Consider implementing mentorship programs pairing top performers with those needing support.`;
        } else if (median < 60) {
            story = `Median score of ${median.toFixed(1)}% is below target. ${belowMedian} staff members score below median. Comprehensive training program recommended for team-wide improvement.`;
        } else if (median >= 75) {
            story = `Strong team performance with median at ${median.toFixed(1)}%. ${aboveMedian} staff exceed median. Focus on elevating the ${belowMedian} below-median performers to achieve excellence across the board.`;
        } else {
            story = `Moderate performance with median at ${median.toFixed(1)}%. Balanced approach needed: recognize high performers while providing targeted support to those below median.`;
        }
        
        document.getElementById('boxPlotStory').textContent = story;
        }
    } catch (error) {
        console.error('[loadBoxPlotChart]', error);
    }
}

async function loadStaffTable() {
    try {
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
    } catch (error) {
        console.error('[loadStaffTable]', error);
    }
}

function filterStaffData(data) {
    let filtered = data;

    // Filter by department — data uses 'department' string, not a numeric ID
    if (currentFilters.department) {
        filtered = filtered.filter(s => s.department === currentFilters.department);
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
