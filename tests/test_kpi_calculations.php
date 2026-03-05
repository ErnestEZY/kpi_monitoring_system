<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Calculation Test Cases</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-case {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
            margin-bottom: 20px;
        }
        .result-box {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">KPI Calculation System - Test Cases</h1>
        <p class="lead">Testing different KPI calculation scenarios and edge cases</p>
        
        <div class="row">
            <!-- Test Case 1: Basic Overall Score -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Test Case 1: Calculate Overall Weighted Score</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Calculate weighted overall KPI score for Ali (SA002) in 2024</p>
                        <button class="btn btn-primary" onclick="testCase1()">Run Test</button>
                        <div id="result1" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 2: Performance Trend -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Test Case 2: Multi-Year Performance Trend</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Analyze Ali's (SA002) performance trend across all years</p>
                        <button class="btn btn-success" onclick="testCase2()">Run Test</button>
                        <div id="result2" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 3: At-Risk Detection -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Test Case 3: At-Risk Staff Detection</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Identify staff with consecutive low performance or critical scores</p>
                        <button class="btn btn-warning" onclick="testCase3()">Run Test</button>
                        <div id="result3" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 4: Top Performers -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Test Case 4: Top Performers Identification</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Find all staff with scores ≥ 85 in 2024</p>
                        <button class="btn btn-info" onclick="testCase4()">Run Test</button>
                        <div id="result4" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 5: Narrative Generation -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Test Case 5: Automated Narrative Insights</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Generate narrative for Lisa (SA005) showing performance insights</p>
                        <button class="btn btn-secondary" onclick="testCase5()">Run Test</button>
                        <div id="result5" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 6: Team Comparison -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Test Case 6: Compare to Team Average</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Compare Sally's (SA003) performance against team average in 2024</p>
                        <button class="btn btn-dark" onclick="testCase6()">Run Test</button>
                        <div id="result6" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 7: Performance Distribution -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header" style="background: #6f42c1; color: white;">
                        <h5 class="mb-0">Test Case 7: Performance Distribution Analysis</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Show distribution of performance levels in 2024</p>
                        <button class="btn" style="background: #6f42c1; color: white;" onclick="testCase7()">Run Test</button>
                        <div id="result7" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 8: Category Averages -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header" style="background: #e83e8c; color: white;">
                        <h5 class="mb-0">Test Case 8: Category-wise Performance Averages</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Calculate average scores for each KPI category in 2024</p>
                        <button class="btn" style="background: #e83e8c; color: white;" onclick="testCase8()">Run Test</button>
                        <div id="result8" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 9: Year-over-Year Comparison -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header" style="background: #20c997; color: white;">
                        <h5 class="mb-0">Test Case 9: Year-over-Year Category Comparison</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Compare John's (SA004) category scores across all years</p>
                        <button class="btn" style="background: #20c997; color: white;" onclick="testCase9()">Run Test</button>
                        <div id="result9" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Test Case 10: All Staff Scores -->
            <div class="col-12 mb-4">
                <div class="card test-case">
                    <div class="card-header" style="background: #fd7e14; color: white;">
                        <h5 class="mb-0">Test Case 10: All Staff Performance Summary</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Scenario:</strong> Get complete performance summary for all staff in 2024</p>
                        <button class="btn" style="background: #fd7e14; color: white;" onclick="testCase10()">Run Test</button>
                        <div id="result10" class="result-box mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function fetchAPI(action, params = {}) {
            const queryString = new URLSearchParams({action, ...params}).toString();
            const response = await fetch(`api/kpi_calculations.php?${queryString}`);
            return await response.json();
        }
        
        function displayResult(elementId, data) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function testCase1() {
            const result = await fetchAPI('overall_score', {staff_id: 2, year: 2024});
            displayResult('result1', result);
        }
        
        async function testCase2() {
            const result = await fetchAPI('performance_trend', {staff_id: 2});
            displayResult('result2', result);
        }
        
        async function testCase3() {
            const result = await fetchAPI('at_risk_staff', {year: 2024});
            displayResult('result3', result);
        }
        
        async function testCase4() {
            const result = await fetchAPI('top_performers', {year: 2024, threshold: 85});
            displayResult('result4', result);
        }
        
        async function testCase5() {
            const result = await fetchAPI('narrative', {staff_id: 5});
            displayResult('result5', result);
        }
        
        async function testCase6() {
            const result = await fetchAPI('compare_to_team', {staff_id: 3, year: 2024});
            displayResult('result6', result);
        }
        
        async function testCase7() {
            const result = await fetchAPI('performance_distribution', {year: 2024});
            displayResult('result7', result);
        }
        
        async function testCase8() {
            const result = await fetchAPI('category_averages', {year: 2024});
            displayResult('result8', result);
        }
        
        async function testCase9() {
            const result = await fetchAPI('year_over_year_comparison', {staff_id: 4});
            displayResult('result9', result);
        }
        
        async function testCase10() {
            const result = await fetchAPI('all_staff_scores', {year: 2024});
            displayResult('result10', result);
        }
    </script>
</body>
</html>
