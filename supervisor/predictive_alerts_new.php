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
    <title>Predictive Performance Risk Alerts</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .alert-card { border-left: 4px solid; margin-bottom: 14px; transition: transform .25s; }
        .alert-card:hover { transform: translateX(5px); }
        .alert-critical { border-left-color:#dc3545; background:#f8d7da; }
        .alert-high     { border-left-color:#fd7e14; background:#ffe5d0; }
        .alert-medium   { border-left-color:#ffc107; background:#fff3cd; }
        .alert-low      { border-left-color:#0dcaf0; background:#cff4fc; }
        .risk-badge { padding:4px 14px; border-radius:20px; font-weight:600; font-size:.82rem; }
        .risk-critical { background:#dc3545; color:#fff; }
        .risk-high     { background:#fd7e14; color:#fff; }
        .risk-medium   { background:#ffc107; color:#000; }
        .risk-low      { background:#0dcaf0; color:#000; }
        .trend-down { color:#dc3545; }
        .trend-up   { color:#28a745; }
        .trend-stable { color:#6c757d; }
        .tf-badge {
            background: linear-gradient(135deg,#ff6f00,#ffa000);
            color:#fff; padding:3px 10px; border-radius:12px;
            font-size:.75rem; font-weight:700; letter-spacing:.5px;
        }
        .confidence-bar { height:6px; border-radius:3px; background:#e9ecef; }
        .confidence-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,#667eea,#764ba2); }
        #tfStatus { font-size:.82rem; }

        /* Classification threshold indicator */
        .threshold-bar-wrap {
            background:#fff;
            border-radius:12px;
            padding:18px 24px;
            box-shadow:0 2px 8px rgba(0,0,0,.08);
            margin-bottom:24px;
        }
        .threshold-bar-wrap h6 {
            font-size:.82rem;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:.5px;
            color:#666;
            margin-bottom:12px;
        }
        .threshold-track {
            position:relative;
            height:28px;
            border-radius:14px;
            overflow:hidden;
            display:flex;
        }
        .threshold-seg {
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:.72rem;
            font-weight:700;
            color:#fff;
            transition:flex .4s ease;
            white-space:nowrap;
            overflow:hidden;
        }
        .seg-critical { background:#dc3545; }
        .seg-high     { background:#fd7e14; }
        .seg-medium   { background:#ffc107; color:#333; }
        .seg-low      { background:#20c997; }
        .threshold-legend {
            display:flex;
            flex-wrap:wrap;
            gap:16px;
            margin-top:14px;
        }
        .legend-dot {
            width:10px; height:10px;
            border-radius:50%;
            display:inline-block;
            margin-right:5px;
        }
        .legend-item { font-size:.8rem; color:#555; display:flex; align-items:center; }

        /* Summary card percentage pill */
        .pct-pill {
            font-size:.72rem;
            font-weight:600;
            padding:2px 8px;
            border-radius:10px;
            margin-top:4px;
            display:inline-block;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
                <div>
                    <h2>
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                        Predictive Performance Risk Alerts
                        <span class="tf-badge ms-2">TensorFlow.js</span>
                    </h2>
                    <p class="text-muted mb-0">
                        Neural-network time-series forecasting — runs entirely in your browser.
                    </p>
                    <small id="tfStatus" class="text-muted">
                        <span class="spinner-border spinner-border-sm me-1"></span> Loading TensorFlow.js model…
                    </small>
                </div>
                <button class="btn btn-primary" id="runBtn" onclick="runPredictions()">
                    <i class="bi bi-play-fill"></i> Run Predictions
                </button>
            </div>

            <!-- How it works info box -->
            <div class="alert alert-info d-flex gap-2 align-items-start mb-4">
                <i class="bi bi-cpu-fill fs-5 mt-1"></i>
                <div>
                    <strong>How to use:</strong>
                    Click <strong>Run Predictions</strong> to train a neural network for each staff member
                    and generate risk alerts. Results are saved for your session — you can navigate away
                    and return without losing them. Click <strong>Re-run Predictions</strong> to refresh
                    with the latest data.
                    <br><small class="text-muted">Minimum 3 years of KPI data required per staff member. Powered by TensorFlow.js.</small>
                </div>
            </div>

            <!-- Classification threshold indicator — static reference -->
            <div class="threshold-bar-wrap">
                <h6><i class="bi bi-sliders me-1"></i> TensorFlow.js Classification Thresholds</h6>

                <!-- Coloured bar -->
                <div class="threshold-track">
                    <div class="threshold-seg seg-critical" style="flex:50">0 – 49%</div>
                    <div class="threshold-seg seg-high"     style="flex:15">50 – 64%</div>
                    <div class="threshold-seg seg-medium"   style="flex:10">65 – 74%</div>
                    <div class="threshold-seg seg-low"      style="flex:25">75 – 100%</div>
                </div>

                <!-- Boundary labels pinned exactly at 0%, 50%, 65%, 75%, 100% -->
                <div style="position:relative; height:18px; margin-top:4px;">
                    <span style="position:absolute; left:0%;    transform:translateX(0);    font-size:.72rem; color:#888;">0%</span>
                    <span style="position:absolute; left:50%;   transform:translateX(-50%); font-size:.72rem; color:#888;">50%</span>
                    <span style="position:absolute; left:65%;   transform:translateX(-50%); font-size:.72rem; color:#888;">65%</span>
                    <span style="position:absolute; left:75%;   transform:translateX(-50%); font-size:.72rem; color:#888;">75%</span>
                    <span style="position:absolute; left:100%;  transform:translateX(-100%);font-size:.72rem; color:#888;">100%</span>
                </div>

                <div class="threshold-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#dc3545"></span>
                        <strong>Critical</strong>&nbsp;— predicted &lt; 50%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#fd7e14"></span>
                        <strong>High</strong>&nbsp;— predicted &lt; 65%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#ffc107"></span>
                        <strong>Medium</strong>&nbsp;— predicted &lt; 75%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#20c997"></span>
                        <strong>Low / Stable</strong>&nbsp;— predicted ≥ 75%
                    </div>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size:2rem"></i>
                            <h3 class="mt-2 text-danger" id="criticalCount">—</h3>
                            <p class="text-muted mb-1">Critical Risk</p>
                            <div class="pct-pill bg-danger bg-opacity-10 text-danger" id="criticalPct">—</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:2rem"></i>
                            <h3 class="mt-2 text-warning" id="highCount">—</h3>
                            <p class="text-muted mb-1">High Risk</p>
                            <div class="pct-pill bg-warning bg-opacity-10 text-warning" id="highPct">—</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-exclamation-circle-fill text-info" style="font-size:2rem"></i>
                            <h3 class="mt-2 text-info" id="mediumCount">—</h3>
                            <p class="text-muted mb-1">Medium Risk</p>
                            <div class="pct-pill bg-info bg-opacity-10 text-info" id="mediumPct">—</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-check-circle-fill text-success" style="font-size:2rem"></i>
                            <h3 class="mt-2 text-success" id="lowCount">—</h3>
                            <p class="text-muted mb-1">Low / Stable</p>
                            <div class="pct-pill bg-success bg-opacity-10 text-success" id="lowPct">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts container -->
            <div class="card shadow">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bell-fill"></i> Active Risk Alerts</h5>
                    <small class="text-muted" id="modelInfo"></small>
                </div>
                <div class="card-body" id="alertsContainer">
                    <div class="text-center py-5 text-muted" id="readyState">
                        <i class="bi bi-cpu fs-1 mb-3 d-block" style="color:#ffa000;"></i>
                        <h5>Ready to Analyse</h5>
                        <p class="mb-0">Click <strong>Run Predictions</strong> above to start.</p>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- TensorFlow.js — loaded from CDN, no install needed -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.17.0/dist/tf.min.js"></script>

<script>
/* ============================================================
   TensorFlow.js Predictive Alerts
   ============================================================
   Model: Sequential Dense NN
     Input  → Dense(8, relu) → Dense(4, relu) → Dense(1, linear)
   Training: Adam optimiser, MSE loss, up to 200 epochs
   Input feature: normalised year index (0 → 1 over the data range)
   Output: predicted overall score (0–100 %)
   Confidence: 100 − (MAE on training set), clamped 50–99 %

   References & Resources:
   - TensorFlow.js official docs: https://www.tensorflow.org/js
   - TF.js Layers API (tf.layers.dense, tf.sequential):
       https://js.tensorflow.org/api/latest/#layers.dense
   - Adam optimiser & MSE loss concepts:
       https://www.tensorflow.org/js/guide/train_models
   - Dense neural network architecture guidance:
       https://developers.google.com/machine-learning/crash-course
   - Normalisation technique (min-max scaling to [0,1]):
       standard ML preprocessing — referenced from TF.js tutorials
   - MAE confidence scoring: derived from model evaluation concepts
       in the TF.js "Fit a model" guide
   - ChatGPT (OpenAI) was used as a learning aid to understand
       neural network concepts, layer configurations, activation
       functions (ReLU vs Linear), and how to structure the
       training loop in TensorFlow.js. The implementation was
       written and adapted by the developer based on that learning.
   ============================================================ */

let tfReady = false;

// Wait for TF.js to initialise
tf.ready().then(() => {
    tfReady = true;
    document.getElementById('tfStatus').innerHTML =
        '<i class="bi bi-check-circle-fill text-success me-1"></i>' +
        'TensorFlow.js ready — backend: <strong>' + tf.getBackend() + '</strong>';

    // Restore cached results from sessionStorage if available
    const cached = sessionStorage.getItem('predictiveResults');
    if (cached) {
        try {
            const results = JSON.parse(cached);
            restoreFromCache(results);
        } catch(e) {
            sessionStorage.removeItem('predictiveResults');
        }
    }
}).catch(err => {
    document.getElementById('tfStatus').innerHTML =
        '<i class="bi bi-x-circle-fill text-danger me-1"></i> TF.js failed to load: ' + err;
});

/* ── Restore previously computed results without retraining ── */
function restoreFromCache(results) {
    const counts = { critical: 0, high: 0, medium: 0, low: 0 };
    results.forEach(r => counts[r.risk.level]++);
    const total = results.length;
    const pct = (n) => total > 0 ? Math.round((n / total) * 100) + '% of staff' : '0%';

    $('#criticalCount').text(counts.critical);
    $('#highCount').text(counts.high);
    $('#mediumCount').text(counts.medium);
    $('#lowCount').text(counts.low);
    $('#criticalPct').text(pct(counts.critical));
    $('#highPct').text(pct(counts.high));
    $('#mediumPct').text(pct(counts.medium));
    $('#lowPct').text(pct(counts.low));
    $('#modelInfo').text(`${results.filter(r => r.scores).length || results.length} models trained · ${total} staff analysed (cached)`);

    // Switch button to Re-run
    document.getElementById('runBtn').innerHTML =
        '<i class="bi bi-arrow-clockwise"></i> Re-run Predictions';

    renderAlerts(results);
}

/* ── Build & train a tiny dense model for one staff member ── */
async function trainModel(years, scores) {
    // Normalise x to [0, 1]
    const n   = years.length;
    const xRaw = years.map((_, i) => i / Math.max(n - 1, 1));
    const yRaw = scores.map(s => s / 100);          // normalise scores to [0,1]

    const xs = tf.tensor2d(xRaw, [n, 1]);
    const ys = tf.tensor2d(yRaw, [n, 1]);

    const model = tf.sequential({
        layers: [
            tf.layers.dense({ inputShape: [1], units: 8,  activation: 'relu' }),
            tf.layers.dense({                  units: 4,  activation: 'relu' }),
            tf.layers.dense({                  units: 1,  activation: 'linear' }),
        ]
    });

    model.compile({ optimizer: tf.train.adam(0.05), loss: 'meanSquaredError' });

    // Train silently
    await model.fit(xs, ys, {
        epochs: 200,
        verbose: 0,
        callbacks: { onEpochEnd: null }
    });

    // Predict next year (index = n)
    const nextX   = tf.tensor2d([n / Math.max(n - 1, 1)], [1, 1]);
    const predRaw = model.predict(nextX).dataSync()[0];
    const predicted = Math.min(100, Math.max(0, predRaw * 100));

    // Confidence = 100 − MAE on training data
    const trainPreds = model.predict(xs).dataSync();
    let mae = 0;
    for (let i = 0; i < n; i++) {
        mae += Math.abs(trainPreds[i] - yRaw[i]);
    }
    mae = (mae / n) * 100;                          // back to % scale
    const confidence = Math.min(99, Math.max(50, Math.round(100 - mae)));

    // Clean up tensors
    xs.dispose(); ys.dispose(); nextX.dispose(); model.dispose();

    return { predicted: Math.round(predicted * 10) / 10, confidence };
}

/* ── Classify risk based purely on predicted score — matches the threshold bar ── */
function classifyRisk(current, predicted) {
    if (predicted < 50)
        return { level: 'critical', label: 'CRITICAL RISK',
                 recs: ['Immediate 1-on-1 meeting', 'Performance Improvement Plan', 'Assign mentor', 'Review workload'] };
    if (predicted < 65)
        return { level: 'high', label: 'HIGH RISK',
                 recs: ['Schedule coaching session', 'Assign targeted training', 'Weekly check-ins'] };
    if (predicted < 75)
        return { level: 'medium', label: 'MEDIUM RISK',
                 recs: ['Monitor closely', 'Provide additional support', 'Set improvement targets'] };
    return { level: 'low', label: 'LOW RISK', recs: [] };
}

/* ── Main entry point ── */
async function runPredictions() {
    if (!tfReady) {
        Swal.fire({
            icon: 'warning',
            title: 'Not Ready',
            text: 'TensorFlow.js is still loading. Please wait a moment and try again.',
            confirmButtonColor: '#667eea'
        });
        return;
    }

    // Show loading overlay while models train
    Swal.fire({
        title: 'Training Neural Networks',
        text: 'Analysing performance history for each staff member…',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    // Fetch historical trend data from PHP API
    let staffData;
    try {
        const res  = await fetch('../api/innovative_features_api.php?action=get_all_trends');
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        staffData = json.data;
    } catch (e) {
        Swal.close();
        $('#alertsContainer').html(`<div class="alert alert-danger">Failed to load data: ${e.message}</div>`);
        return;
    }

    const results = [];
    let modelsBuilt = 0;

    for (const staff of staffData) {
        if (staff.scores.length < 3) continue;   // need ≥3 years

        const { predicted, confidence } = await trainModel(staff.years, staff.scores);
        const current = staff.scores[staff.scores.length - 1];
        const risk    = classifyRisk(current, predicted);
        const trend   = predicted < current - 1 ? 'down' : (predicted > current + 1 ? 'up' : 'stable');

        results.push({ ...staff, current, predicted, confidence, risk, trend });
        modelsBuilt++;
    }

    // Sort: critical first
    const order = { critical: 0, high: 1, medium: 2, low: 3 };
    results.sort((a, b) => order[a.risk.level] - order[b.risk.level]);

    // Update summary counts + percentages
    const counts = { critical: 0, high: 0, medium: 0, low: 0 };
    results.forEach(r => counts[r.risk.level]++);
    const total = results.length;

    const pct = (n) => total > 0 ? Math.round((n / total) * 100) + '% of staff' : '0%';

    $('#criticalCount').text(counts.critical);
    $('#highCount').text(counts.high);
    $('#mediumCount').text(counts.medium);
    $('#lowCount').text(counts.low);

    $('#criticalPct').text(pct(counts.critical));
    $('#highPct').text(pct(counts.high));
    $('#mediumPct').text(pct(counts.medium));
    $('#lowPct').text(pct(counts.low));

    $('#modelInfo').text(`${modelsBuilt} models trained · ${total} staff analysed`);

    // Close loading and render results
    Swal.close();

    // Save results to sessionStorage so they persist across page navigation
    try {
        sessionStorage.setItem('predictiveResults', JSON.stringify(results));
    } catch(e) { /* storage full — ignore */ }

    // Switch button to Re-run after first run
    document.getElementById('runBtn').innerHTML =
        '<i class="bi bi-arrow-clockwise"></i> Re-run Predictions';

    // Brief success toast
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `${modelsBuilt} models trained successfully`,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });

    renderAlerts(results);
}

/* ── Render alert cards ── */
function renderAlerts(results) {
    if (results.length === 0) {
        $('#alertsContainer').html(`
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                No staff members have enough historical data for prediction yet (minimum 3 years required).
            </div>`);
        return;
    }

    const nonLow = results.filter(r => r.risk.level !== 'low');

    if (nonLow.length === 0) {
        $('#alertsContainer').html(`
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>All Clear!</strong> Neural network predictions show no significant risk for any staff member.
            </div>`);
        return;
    }

    let html = '';
    nonLow.forEach(r => {
        const trendIcon  = r.trend === 'down' ? 'bi-graph-down-arrow trend-down'
                         : r.trend === 'up'   ? 'bi-graph-up-arrow trend-up'
                         :                      'bi-dash-lg trend-stable';
        const changeVal  = (r.predicted - r.current).toFixed(1);
        const changeSign = changeVal > 0 ? '+' : '';
        const recsHtml   = r.risk.recs.length
            ? `<div class="mt-3 pt-2 border-top">
                 <strong><i class="bi bi-lightbulb-fill text-warning"></i> Recommended Actions:</strong>
                 <ul class="mb-0 mt-1">${r.risk.recs.map(rec => `<li>${rec}</li>`).join('')}</ul>
               </div>` : '';

        html += `
        <div class="card alert-card alert-${r.risk.level}">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="bi ${trendIcon}" style="font-size:1.8rem"></i>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="mb-0">${r.staff_name}</h6>
                            <small class="text-muted">${r.staff_code} · ${r.department}</small>
                            <span class="risk-badge risk-${r.risk.level}">${r.risk.label}</span>
                        </div>
                        <div class="d-flex gap-4 mb-2">
                            <span><strong>Current:</strong> ${r.current.toFixed(1)}%</span>
                            <span><strong>Predicted:</strong> ${r.predicted.toFixed(1)}%</span>
                            <span><strong>Change:</strong> ${changeSign}${changeVal}%</span>
                        </div>
                        <!-- Confidence bar -->
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted" style="white-space:nowrap">
                                Model confidence: ${r.confidence}%
                            </small>
                            <div class="confidence-bar flex-grow-1">
                                <div class="confidence-fill" style="width:${r.confidence}%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto text-end">
                        <button class="btn btn-sm btn-primary d-block mb-1"
                                onclick="window.location.href='staff_profile.php?id=${r.staff_id}'">
                            <i class="bi bi-eye"></i> Profile
                        </button>
                        <button class="btn btn-sm btn-warning d-block"
                                onclick="takeAction(${r.staff_id}, '${r.staff_name}')">
                            <i class="bi bi-lightning-fill"></i> Action
                        </button>
                    </div>
                </div>
                ${recsHtml}
            </div>
        </div>`;
    });

    // Storytelling insight
    const critCount = results.filter(r => r.risk.level === 'critical').length;
    const total     = results.length;
    let story = '';
    if (critCount > 0) {
        story = `<div class="alert alert-danger mt-3">
            <i class="bi bi-lightbulb-fill me-2"></i>
            <strong>Insight:</strong> ${critCount} staff member${critCount > 1 ? 's are' : ' is'} predicted to fall
            into critical performance territory. Early intervention now is significantly more effective than
            reactive measures later.
        </div>`;
    } else if (total > 0) {
        story = `<div class="alert alert-warning mt-3">
            <i class="bi bi-lightbulb-fill me-2"></i>
            <strong>Insight:</strong> ${total} staff member${total > 1 ? 's show' : ' shows'} a declining trend.
            Proactive coaching and targeted training can reverse this before it becomes critical.
        </div>`;
    }

    $('#alertsContainer').html(html + story + lowSection(results));
}

/* ── Collapsed section for Low / Stable staff ── */
function lowSection(results) {
    const lowStaff = results.filter(r => r.risk.level === 'low');
    if (lowStaff.length === 0) return '';

    const rows = lowStaff.map(r => `
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <strong>${r.staff_name}</strong>
                <small class="text-muted ms-2">${r.staff_code} · ${r.department}</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">Current: ${r.current.toFixed(1)}%</span>
                <span class="text-muted small">Predicted: ${r.predicted.toFixed(1)}%</span>
                <span class="badge" style="background:#20c997;color:#fff;">LOW RISK</span>
                <a href="staff_profile.php?id=${r.staff_id}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-eye"></i>
                </a>
            </div>
        </div>`).join('');

    return `
        <div class="mt-3">
            <button class="btn btn-sm w-100 text-start fw-semibold"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#lowStaffCollapse"
                    aria-expanded="false"
                    style="background:#d1f5ea;color:#0a6640;border:1px solid #20c997;">
                <i class="bi bi-chevron-down me-2"></i>
                <i class="bi bi-check-circle-fill me-1"></i>
                Low / Stable Staff — ${lowStaff.length} staff predicted ≥ 75% (click to view)
            </button>
            <div class="collapse" id="lowStaffCollapse">
                <div class="card card-body mt-1 p-3" style="border:1px solid #20c997;border-top:none;border-radius:0 0 8px 8px;">
                    ${rows}
                </div>
            </div>
        </div>`;
}

/* ── Action modal ── */
function takeAction(staffId, staffName) {
    Swal.fire({
        title: `Action for ${staffName}`,
        html: `
            <div class="text-start">
                <p class="text-muted">Select an intervention:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="act" id="a1" value="meeting" checked>
                    <label class="form-check-label" for="a1">Schedule 1-on-1 Meeting</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="act" id="a2" value="training">
                    <label class="form-check-label" for="a2">Assign Training Program</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="act" id="a3" value="pip">
                    <label class="form-check-label" for="a3">Create Performance Improvement Plan</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="act" id="a4" value="mentor">
                    <label class="form-check-label" for="a4">Assign Mentor</label>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Confirm Action',
        confirmButtonColor: '#667eea'
    }).then(res => {
        if (res.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Action Scheduled',
                text: 'The intervention has been recorded.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}
</script>
</body>
</html>
