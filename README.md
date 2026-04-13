# Sales Assistant KPI Monitoring System

A web-based performance monitoring system for sales assistants, built with PHP, MySQL, Bootstrap 5, and TensorFlow.js.

Student Information:
Eh Zhong Yu (0139116)
Lau Jin Yee (0139351)
Chan Ming Jiang (M44100114)

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
- This creates all tables, loads all 13 staff, 21 KPIs, and sample scores (2022–2026)

> **Note:** The `database/kpi_system.sql` file is included in the `database/` folder of this repository.

**5. Open the system**
```
http://localhost/kpi_system/
```

**Default login credentials:**

| Username | Password | Role |
|---|---|---|
| admin | admin123 | Primary Supervisor |
| manager1 | pass123 | Manager |
| super1 | super123 | Supervisor |

---

## Features

### Standard Features

| Feature | File |
|---|---|
| Supervisor login & session management | `auth/login.php` |
| Supervisor dashboard with KPI overview | `supervisor/dashboard.php` |
| KPI score entry (21 KPIs, collapsible sections) | `supervisor/kpi_entry.php` |
| Staff list with photo, filters & dropdown search | `supervisor/staff_list.php` |
| Individual staff profile with charts & photo | `supervisor/staff_profile.php` |
| Interactive analytics dashboard (5 charts) | `supervisor/analytics.php` |
| Top performers & at-risk reports | `supervisor/reports.php` |
| Training needs report with keyword analysis | `supervisor/reports.php` |
| Supervisor comments & training recommendations | `supervisor/staff_profile.php` |
| Gamified performance leaderboard | `supervisor/gamification.php` |

### Innovative Features

| Feature | Technology | File |
|---|---|---|
| **Predictive Performance Alerts** | TensorFlow.js v4.17.0 — Dense Neural Network | `supervisor/predictive_alerts_new.php` |
| **Smart Training Recommendations** | PHP rule-based skill gap matching | `supervisor/training_recommendations.php` |
| **Anomaly Detection + Auto Narrative** | Statistical spike/drop detection + narrative engine | `supervisor/anomaly_detection.php` |
| **Intelligent Peer Comparison** | Similarity scoring with specific staff selection | `supervisor/peer_comparison_new.php` |

---

## TensorFlow.js Predictive Alerts

Trains a **Dense Neural Network** in the browser for each staff member using their historical KPI scores.

**Model architecture:**
```
Input (normalised year index)
  → Dense(8, ReLU)
  → Dense(4, ReLU)
  → Dense(1, Linear)  →  predicted score (0–100%)
```

**Risk classification:**

| Predicted Score | Risk Level |
|---|---|
| < 50% | Critical |
| 50% – 64% | High |
| 65% – 74% | Medium |
| ≥ 75% | Low / Stable |

- Confidence = `100 − MAE` on training data, clamped 50–99%
- Minimum **3 years** of data required per staff member
- Low/Stable staff shown in a collapsed section at the bottom

---

## KPI Calculation Formula

```
weighted_score  = (score / 5) × (weight_percentage / 100)
overall_score % = SUM(all 21 weighted_scores) × 100
```

Scores entered on a **1–5 scale**. All 21 KPI weights sum to exactly 100%.

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
| Frontend | Bootstrap 5.3, Chart.js 4.4, Alpine.js 3, jQuery 3.7, SweetAlert2, Bootstrap Icons |
| Machine Learning | **TensorFlow.js 4.17** (Predictive Alerts — runs in browser) |
| Backend | PHP 7.4+, PDO prepared statements |
| Database | MySQL / MariaDB — 3NF normalised, foreign key constraints |

---

## Database Structure

| Table | Purpose |
|---|---|
| `supervisors` | Supervisor accounts (3 accounts) |
| `staff` | 13 sales assistant profiles with photo column |
| `kpi_master` | 21 KPI definitions with weights (sums to 100%) |
| `kpi_scores` | 742 individual yearly KPI scores (2022–2026) |
| `staff_comments` | Supervisor feedback & training recommendations |

**Unique key on `kpi_scores`:** `(staff_id, kpi_code, evaluation_year)` — prevents duplicate scores, uses `ON DUPLICATE KEY UPDATE` for re-submissions.

---

## Accessibility

All pages meet the following standards:
- `lang="en"` on every `<html>` element
- `scope="col"` on all `<th>` table headers
- `role="img"` and `aria-label` on all `<canvas>` chart elements
- `aria-label="Close"` on all icon-only close buttons
- Descriptive `alt` text on all staff photos (`"Name — professional headshot"`)
- `aria-label` on standalone `<select>` elements without visible labels
- All form inputs have associated `<label>` elements

---

## Running Tests

```
http://localhost/kpi_system/tests/test_system.php
```

34 automated tests covering: database connection, table structure, KPI weights, score calculation, classification (5 ranges), trend analysis, at-risk year-filtering, top performers, narrative generation, predictive model, unique key, and photo column.

---

## Database Files

| File | Purpose |
|---|---|
| `database/kpi_system.sql` | **Single import** — full schema, all tables, all sample data (2022–2026) |
| `database/sample_training_recommendations.sql` | Adds training recommendation text for 2022, 2024, 2025 |
| `database/sample_top_and_atrisk.sql` | 2025–2026 scores for top performers & at-risk staff |

---

## Project Structure

```
kpi_system/
├── api/
│   ├── innovative_features_api.php   # Predictive alerts, training, gamification, peer comparison
│   ├── kpi_api.php                   # Score saving, comments (AJAX endpoints)
│   └── kpi_calculations.php          # Read-only calculation endpoints for charts
├── assets/
│   ├── css/dashboard.css             # Global styles
│   ├── js/analytics.js               # Analytics dashboard charts & storytelling
│   └── photos/                       # 13 staff profile photos
├── auth/
│   ├── login.php                     # Login form & session creation
│   └── logout.php                    # Session destruction
├── config/
│   ├── database.php                  # PDO connection (singleton pattern)
│   └── .htaccess                     # Blocks direct browser access to config/
├── database/
│   └── kpi_system.sql                # Complete database import
├── includes/
│   ├── auth.php                      # Session helpers & requireLogin()
│   └── KPICalculator.php             # Core calculation engine (all business logic)
├── supervisor/
│   ├── includes/
│   │   ├── navbar.php                # Top navigation with login time
│   │   └── sidebar.php               # Left navigation menu
│   ├── analytics.php                 # Interactive analytics dashboard
│   ├── anomaly_detection.php         # Anomaly detection feature
│   ├── dashboard.php                 # Main supervisor dashboard
│   ├── gamification.php              # Gamified leaderboard
│   ├── kpi_entry.php                 # KPI score entry (collapsible)
│   ├── peer_comparison_new.php       # Peer comparison with specific staff option
│   ├── predictive_alerts_new.php     # TensorFlow.js neural network predictions
│   ├── reports.php                   # Top performers, at-risk, training needs
│   ├── staff_list.php                # Staff cards with photo & filters
│   ├── staff_profile.php             # Individual profile with charts & comments
│   └── training_recommendations.php  # Smart training recommendations
├── tests/
│   └── test_system.php               # 34-test functional test suite
├── SYSTEM_OVERVIEW.txt               # Full system overview
└── README.md                         # This file
```
