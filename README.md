# Sales Assistant KPI Monitoring System

A comprehensive web-based performance monitoring system for sales assistants with advanced analytics and intelligent insights.

## 🎯 Project Overview

This system allows supervisors to monitor, analyze, and improve sales assistant performance through:
- **21 KPIs** across 7 performance groups
- **Weighted scoring** system (0-5 scale)
- **Multi-year** performance tracking (2022-2026)
- **4 Innovative Features** for advanced analytics

## 🚀 Key Features

### Standard Features
1. **Authentication & Session Management** - Secure supervisor login
2. **Performance Dashboard** - Real-time metrics and visualizations
3. **KPI Management** - Score recording and tracking across 21 KPIs
4. **Staff Profiles** - Comprehensive individual performance views
5. **Interactive Analytics** - Multi-dimensional analysis with drill-down
6. **Performance Reports** - Top performers, at-risk staff, training needs
7. **Supervisor Comments** - Feedback and training recommendations
8. **Data Export** - Excel and PDF export capabilities

### Innovative Features (Advanced Analytics)
1. **Predictive Performance Alerts** 
   - Linear regression prediction algorithm
   - Risk level classification (Critical/High/Medium/Low)
   - Confidence scoring (65-95%)
   - Trend analysis and forecasting

2. **Smart Training Recommendations**
   - Automated skill gap identification
   - Intelligent program matching with compatibility scores
   - Priority ranking system
   - 6 training programs with detailed outcomes

3. **Anomaly Detection + Auto-Narrative Insights**
   - Chart.js visualization with anomaly markers
   - Detects spikes, drops, and consecutive declines
   - AI-generated narrative insights
   - Actionable recommendations

4. **Advanced Peer Comparison**
   - Intelligent peer matching algorithm
   - Multi-dimensional radar charts
   - Similarity scoring (0-100%)
   - Side-by-side performance analysis

### Gamified Visualizations
- **Progress Bars with Achievement Milestones** (Profile page)
  - Animated progress bars for each KPI category
  - Target thresholds (4.0 = Excellent)
  - Achievement unlock notifications
  - Color-coded performance levels

## 💻 Technology Stack

### Frontend
- HTML5, CSS3, JavaScript (ES6+)
- Bootstrap 5.3.0 (Responsive framework)
- Chart.js 4.4.0 (Data visualization)
- jQuery 3.7.1 (DOM manipulation)
- DataTables.js 1.13.7 (Table management)
- SweetAlert2 11.x (Notifications)
- Day.js (Date handling)
- Bootstrap Icons 1.11.0

### Backend
- PHP 7.4+ with PDO (Prepared Statements)
- MySQL 5.7+ / MariaDB 10.3+
- RESTful JSON API architecture

### Architecture
- 3-tier architecture (Presentation, Business Logic, Data Access)
- MVC-inspired structure
- AJAX for real-time updates

## 📊 Database Design

### Tables (3NF Normalized)
1. **supervisors** - Supervisor accounts
2. **staff** - Sales assistant information
3. **kpi_master** - KPI definitions (21 KPIs)
4. **kpi_scores** - Individual KPI scores
5. **staff_comments** - Supervisor feedback

### Foreign Key Relationships
- kpi_scores → staff (CASCADE delete)
- kpi_scores → kpi_master (RESTRICT delete)
- staff_comments → staff (CASCADE delete)
- staff_comments → supervisors (SET NULL delete)

## 🔧 Installation

### Prerequisites
- XAMPP/WAMP/LAMP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Steps

1. **Clone/Extract** the project to your web server directory
   ```
   C:\xampp\htdocs\kpi_system\
   ```

2. **Create Database**
   - Open phpMyAdmin
   - Create database: `kpi_system`
   - Import: `database/new_schema.sql`

3. **Import Sample Data** (Choose ONE option)
   
   **Option A: Updated Sample Data (Recommended for Training Recommendations)**
   - Import: `database/updated_sample_data.sql` (2022 base data)
   - Run: `database/import_data.bat` OR
   - Via browser: `http://localhost/kpi_system/database/generate_multiyear_data.php`
   - This generates 2023-2025 data with progressive improvements
   - See `database/IMPORT_INSTRUCTIONS.md` for details
   
   **Option B: Original Sample Data**
   - Import: `database/actual_data_from_csv.sql`
   
   **Option C: Complete Database Import (If you received a complete export)**
   - Double-click: `database/import_complete_database.bat`
   - This imports everything in one step (structure + data)
   - See `database/README_FOR_RECIPIENTS.txt` for details

4. **Configure Database Connection**
   - Edit `config/database.php`
   - Update credentials if needed (default: root/no password)

5. **Access the System**
   - URL: `http://localhost/kpi_system/`
   - Login: `admin` / `admin123`

## 📤 Database Export & Distribution

### Export Your Database

To share your database with others without requiring manual setup:

1. **Export Database:**
   - Double-click: `database/export_complete_database.bat`
   - Creates: `database/kpi_system_complete.sql`

2. **Create Distribution Package:**
   - Include: `kpi_system_complete.sql`
   - Include: `import_complete_database.bat`
   - Include: `README_FOR_RECIPIENTS.txt`
   - Compress to ZIP

3. **Share:**
   - Email, cloud storage, or USB drive
   - Recipients can import in one click

### For Recipients

If you received a database export:

1. Install XAMPP
2. Start MySQL
3. Double-click: `import_complete_database.bat`
4. Access: `http://localhost/kpi_system`
5. Login: `admin` / `admin123`

**See:** `database/DATABASE_EXPORT_GUIDE.md` for detailed instructions

## 📁 Project Structure

```
kpi_system/
├── api/                          # API endpoints
│   ├── get_dashboard_data.php
│   ├── innovative_features_api.php
│   ├── kpi_api.php
│   └── kpi_calculations.php
├── assets/
│   ├── css/
│   │   └── dashboard.css
│   └── js/
│       ├── analytics.js
│       └── kpi_dashboard.js
├── auth/                         # Authentication
│   ├── login.php                 # Login page
│   └── logout.php                # Logout handler
├── config/
│   └── database.php              # Database configuration
├── data/                         # Excel data files
│   ├── 2026-01-07 Dataset.xlsx
│   └── 2026-01-07 Sample KPI.xlsx
├── database/                     # Database files
│   ├── new_schema.sql            # Database schema
│   ├── updated_sample_data.sql   # Sample data (2022)
│   ├── generate_multiyear_data.php
│   ├── import_data.bat
│   ├── IMPORT_INSTRUCTIONS.md
│   ├── QUICK_START.md
│   └── DATA_OVERVIEW.txt
├── docs/                         # Documentation
│   ├── DATABASE_DESIGN_DOCUMENTATION.txt
│   ├── FUNCTIONAL_SPECIFICATIONS_REPORT.txt
│   ├── KPI_SYSTEM_PROJECT_PROPOSAL.md
│   ├── IMPLEMENTATION_COMPLETE.md
│   ├── SAMPLE_DATA_UPDATE_SUMMARY.md
│   └── TASK_30_COMPLETE.md
├── includes/
│   ├── auth.php                  # Authentication helper
│   └── KPICalculator.php         # Core calculations
├── supervisor/                   # Supervisor pages
│   ├── includes/
│   │   ├── navbar.php
│   │   └── sidebar.php
│   ├── dashboard.php
│   ├── kpi_dashboard.php
│   ├── staff_list.php
│   ├── staff_profile.php
│   ├── analytics.php
│   ├── reports.php
│   ├── predictive_alerts_new.php
│   ├── training_recommendations.php
│   ├── anomaly_detection.php
│   └── peer_comparison_new.php
├── tests/                        # Test files
│   ├── test_kpi_calculations.php
│   ├── test_peer_comparison.php
│   └── test_peer_comparison_api.php
├── utils/                        # Utility scripts
│   ├── check_installation.php
│   ├── check_supervisors.php
│   ├── setup_passwords.php
│   ├── generate_password.php
│   ├── extract_excel_structure.php
│   ├── read_excel_data.php
│   └── convert_to_plaintext.php
├── .htaccess                     # Security configuration
├── index.html                    # Landing page
└── README.md                     # This file
```

## 🎓 KPI Framework

### 21 KPIs across 7 Groups

1. **Core Competencies** (10% weight)
   - Product Knowledge (3.33%)
   - Communication Skills (3.33%)
   - Problem-Solving Ability (3.34%)

2. **Daily Sales Operations** (20% weight)
   - Daily Sales Target Achievement (5%)
   - Average Transaction Value (5%)
   - Units Per Transaction (5%)
   - Product Attachment Rate (5%)

3. **Customer Service Quality** (15% weight)
   - Customer Satisfaction Score (5%)
   - Service Recovery Rate (5%)
   - Complaint Resolution Time (5%)

4. **Sales Target Contribution** (20% weight)
   - Monthly Sales Target Achievement (10%)
   - Quarterly Sales Growth (10%)

5. **Training & Team Contribution** (15% weight)
   - Training Participation (3.75%)
   - Knowledge Sharing (3.75%)
   - Team Collaboration (3.75%)
   - Mentoring Activities (3.75%)

6. **Inventory & Cost Control** (10% weight)
   - Stock Accuracy (5%)
   - Shrinkage Prevention (5%)

7. **Store Operations Support** (10% weight)
   - Punctuality & Attendance (3.33%)
   - Visual Merchandising (3.33%)
   - Store Cleanliness (3.34%)

### Performance Levels
- **Excellent**: 4.5-5.0 (90-100%)
- **Good**: 3.5-4.49 (70-89%)
- **Satisfactory**: 2.5-3.49 (50-69%)
- **Needs Improvement**: <2.5 (<50%)

## 🔐 Security Features

- Session-based authentication
- PDO prepared statements (SQL injection prevention)
- XSS prevention (htmlspecialchars)
- Role-based access control
- 30-minute session timeout
- Page-level authorization

## 📈 Prediction Algorithm

The system uses **Linear Regression** for performance prediction:

```
y = mx + b

Where:
- y = predicted score
- m = slope (trend)
- x = time period
- b = intercept

Confidence = R² × 100 (scaled to 65-95%)
```

## 🎯 Training Matching Algorithm

Smart matching based on:
1. **Skill Gap Severity** (Critical/Moderate/Minor)
2. **Focus Area Alignment** (Program vs. Weak areas)
3. **Prerequisites Check** (Score requirements)
4. **Compatibility Score** (0-100%)

## 📊 Sample Data

### Updated Sample Data (Recommended)

The `updated_sample_data.sql` file includes optimized data for testing training recommendations:

**13 Staff Members with Varied Performance Profiles:**

| Staff Code | Name | Performance Profile | Primary Training Need |
|------------|------|---------------------|----------------------|
| SA001 | Alice Johnson | High performer (≥85%) | Leadership Fundamentals |
| SA002 | Bob Smith | Weak Customer Service (<50%) | Customer Service Mastery |
| SA003 | Carol Martinez | Weak Sales (<50%) | Sales Excellence |
| SA004 | David Lee | Weak Operations (<50%) | Operational Excellence |
| SA005 | Emma Wilson | Weak Punctuality (<50%) | Time Management |
| SA006 | Frank Chen | Weak Competency (<50%) | Product Knowledge |
| SA007 | Grace Taylor | Weak Sales Target (<50%) | Sales Excellence |
| SA008 | Henry Brown | Weak Inventory (<50%) | Operational Excellence |
| SA009 | Iris Wang | Average (60-70%) | Multiple improvements |
| SA010 | Jack Miller | High performer (≥85%) | Leadership Fundamentals |
| SA011 | Karen Davis | Weak Customer Service (<50%) | Customer Service Mastery |
| SA012 | Leo Garcia | Weak Operations (<50%) | Time Management + Operations |
| SA013 | Maria Rodriguez | Balanced (70-85%) | Minor improvements |

**Data Coverage:**
- **Years**: 2022 (base), 2023-2025 (progressive improvements)
- **KPIs**: All 21 KPIs with realistic scores
- **Comments**: Supervisor feedback for each year
- **Total Records**: 1,092 KPI scores + 52 comments

**Progressive Improvements:**
- Poor performers (<3.0): +0.3 per year
- Average performers (3.0-4.0): +0.2 per year
- Good performers (≥4.0): +0.1 per year

This data structure ensures all 6 training programs are triggered appropriately.

## 🎨 UI/UX Features

- Responsive design (mobile-friendly)
- Color-coded indicators (green/yellow/red)
- Interactive charts with hover tooltips
- Animated progress bars
- SweetAlert2 notifications
- DataTables with search/sort/filter
- Print-optimized views

## 📝 Assignment Compliance

This system fulfills all assignment requirements:

✅ KPI & Performance Management
✅ Database & Data Persistence (3NF)
✅ Sales Assistant Profile Dashboard
✅ Supervisor Dashboard
✅ Interactive Analytics Dashboard
✅ Storytelling with Data (Narrative generation)
✅ 4 Innovative Features
✅ Supervisor-only perspective (no sales assistant login)

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check `config/database.php` credentials
   - Ensure MySQL service is running
   - Verify database name is `kpi_system`

2. **Login Not Working**
   - Default credentials: `admin` / `admin123`
   - Check supervisors table has data
   - Clear browser cache/cookies

3. **Charts Not Displaying**
   - Ensure Chart.js CDN is accessible
   - Check browser console for errors
   - Verify data exists for selected year

4. **Undefined Array Key Errors**
   - Ensure all sample data is imported
   - Check KPI scores exist for staff members
   - Verify year parameter is valid

## 📚 Documentation

- **DATABASE_DESIGN_DOCUMENTATION.txt** - Complete database design
- **FUNCTIONAL_SPECIFICATIONS_REPORT.txt** - Feature specifications
- **REDESIGN_PLAN.md** - Development roadmap

## 👥 Default Login Credentials

| Username | Password | Role |
|----------|----------|------|
| admin    | admin123 | Supervisor |
| manager1 | pass123  | Supervisor |
| super1   | super123 | Supervisor |

**Note**: Change passwords in production!

## 🔄 Future Enhancements

- Sales assistant self-service portal
- Mobile app version
- Email notifications
- Advanced reporting (PDF generation)
- Multi-language support
- Role hierarchy (Admin/Manager/Supervisor)

## 📄 License

This project is developed for academic purposes.

## 👨‍💻 Development

- **Version**: 1.0
- **Last Updated**: February 26, 2026
- **Status**: Production Ready

---

**For support or questions, please refer to the documentation files or contact your system administrator.**
