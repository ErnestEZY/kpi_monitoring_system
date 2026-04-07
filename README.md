# Sales Assistant KPI Monitoring System

A web-based performance monitoring system for sales assistants, built with PHP, MySQL, Bootstrap 5, and TensorFlow.js.

---

## Installation (XAMPP Localhost)

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- A modern browser (Chrome / Edge recommended for TensorFlow.js)

### Steps

**1. Copy project files**
Extract the zip and place the folder in:
```
C:\xampp\htdocs\kpi_system\
```

**2. Start XAMPP**
Start both **Apache** and **MySQL** in the XAMPP Control Panel.

**3. Create the database**
- Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Click **New** → name it `kpi_system` → click **Create**

**4. Import the database**
- Select the `kpi_system` database
- Click **Import** → choose `database/kpi_system.sql` → click **Go**
- This creates all tables, loads all 13 staff, KPI master data, and sample scores (2022–2026)

**5. Open the system**
```
http://localhost/kpi_system/
```

**Default login credentials:**
| Username | Password |
|---|---|
| admin | admin123 |

---

## Features

### Standard Features

| Feature | Location |
|---|---|
| Supervisor login & session management | `auth/login.php` |
| Supervisor dashboard with KPI overview | `supervisor/dashboard.php` |
| KPI score entry (21 KPIs, collapsible) | `supervisor/kpi_entry.php` |
| Staff list with photo, filters & search | `supervisor/staff_list.php` |
| Individual staff profile with charts | `supervisor/staff_profile.php` |
| Interactive analytics dashboard | `supervisor/analytics.php` |
| Top performers & at-risk reports | `supervisor/reports.php` |
| Supervisor comments & training recommendations | `supervisor/staff_profile.php` |
| Training needs summary with edit | `supervisor/training_recommendations.php` |
| Gamified performance leaderboard | `supervisor/gamification.php` |

### Innovative Features

| Feature | Technology | Location |
|---|---|---|
| **Predictive Performance Alerts** | TensorFlow.js v4.17.0 — Dense Neural Network | `supervisor/predictive_alerts_new.php` |
| **Automated Training Recommendations** | PHP rule-based matching with skill gap scoring | `supervisor/training_recommendations.php` |
| **Anomaly Detection + Auto Narrative** | Statistical spike/drop detection + PHP narrative engine | `supervisor/anomaly_detection.php` |
| **Intelligent Peer Comparison** | Similarity scoring algorithm | `supervisor/peer_comparison_new.php` |

---

## TensorFlow.js Predictive Alerts

The predictive alerts page trains a **Dense Neural Network** in the browser for each staff member using their historical KPI scores.

**Model architecture:**
```
Input (normalised year index)
  → Dense(8, ReLU)
  → Dense(4, ReLU)
  → Dense(1, Linear)  →  predicted score (0–100%)
```

**Risk classification (matches threshold bar on page):**

| Predicted Score | Risk Level |
|---|---|
| < 50% | Critical |
| 50% – 64% | High |
| 65% – 74% | Medium |
| ≥ 75% | Low / Stable |

**Confidence** = `100 − MAE` on training data, clamped to 50–99%.
Minimum **3 years** of data required per staff member.

---

## KPI Calculation Formula

```
weighted_score  = (score / 5) × (weight_percentage / 100)
overall_score % = SUM(all 21 weighted_scores) × 100
```

Scores are entered on a **1–5 scale**. All 21 KPI weights sum to exactly 100%.

**Performance classification:**

| Score | Label |
|---|---|
| ≥ 85% | Top Performer |
| ≥ 75% | Good Performer |
| ≥ 65% | Satisfactory |
| ≥ 50% | Needs Improvement |
| < 50% | Critical |

---

## Technology Stack

| Layer | Technologies |
|---|---|
| Frontend | Bootstrap 5.3, Chart.js 4.4, Alpine.js 3, jQuery 3.7, DataTables 1.13, SweetAlert2, Bootstrap Icons |
| Machine Learning | **TensorFlow.js 4.17** (Predictive Alerts) |
| Backend | PHP 7.4+, PDO (prepared statements) |
| Database | MySQL / MariaDB — 3NF normalised, foreign key constraints |

---

## Database Structure

| Table | Purpose |
|---|---|
| `supervisors` | Supervisor accounts |
| `staff` | Sales assistant profiles (includes `photo` column) |
| `kpi_master` | 21 KPI definitions with weights |
| `kpi_scores` | Individual yearly KPI scores |
| `staff_comments` | Supervisor feedback & training recommendations |

---

## Running Tests

Open in browser:
```
http://localhost/kpi_system/tests/test_system.php
```

Covers: database connection, table structure, KPI weights, score calculation, classification, trend analysis, at-risk detection, top performers, narrative generation, predictive model, and more — **17+ test cases** with pass/fail indicators.

---

## Database Files Reference

| File | Purpose |
|---|---|
| `database/kpi_system.sql` | **Single import** — full schema, all tables, all sample data (2022–2026) |

---

## Project Structure

```
kpi_system/
├── api/                        # JSON API endpoints
│   ├── kpi_api.php             # Score saving, comments
│   ├── kpi_calculations.php    # Score calculations
│   └── innovative_features_api.php
├── assets/
│   ├── css/dashboard.css
│   ├── js/analytics.js
│   └── photos/                 # Staff profile photos
├── auth/                       # Login / logout
├── config/database.php         # DB connection
├── database/                   # SQL files
├── includes/
│   ├── auth.php                # Session helpers
│   └── KPICalculator.php       # Core calculation engine
├── supervisor/                 # All supervisor pages
│   ├── includes/               # Navbar & sidebar
│   └── ...
├── tests/test_system.php       # Functional test suite
└── README.md
```
