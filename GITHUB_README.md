# 📊 Sales Assistant KPI Monitoring System

A comprehensive web-based performance monitoring system for sales assistants with advanced analytics and intelligent insights.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.0-purple)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-Academic-green)](LICENSE)

## 🎯 Project Overview

This system enables supervisors to monitor, analyze, and improve sales assistant performance through a comprehensive KPI framework with 21 performance indicators across 7 categories. The system features advanced analytics including predictive alerts, smart training recommendations, anomaly detection, and peer comparison.

### Key Highlights

- **21 KPIs** across 7 performance groups with weighted scoring
- **Multi-year tracking** (2022-2026) with trend analysis
- **4 Innovative Features** powered by AI/ML algorithms
- **Real-time dashboards** with interactive visualizations
- **One-click database export/import** for easy distribution

## ✨ Features

### Standard Features

1. **Authentication & Session Management**
   - Secure supervisor login with session timeout
   - Role-based access control

2. **Performance Dashboard**
   - Real-time metrics and KPI visualizations
   - Department and staff filtering
   - Interactive Chart.js visualizations

3. **KPI Management**
   - Score recording across 21 KPIs
   - Weighted scoring system (0-5 scale)
   - Historical performance tracking

4. **Staff Profiles**
   - Comprehensive individual performance views
   - KPI breakdown by category
   - Progress bars with achievement milestones

5. **Interactive Analytics**
   - Multi-dimensional analysis with drill-down
   - Department and year filtering
   - Detailed staff data views

6. **Performance Reports**
   - Top performers identification
   - At-risk staff detection
   - Training needs analysis

7. **Supervisor Comments**
   - Feedback and recommendations
   - Historical comment tracking

8. **Data Export**
   - Excel and PDF export capabilities
   - Complete database export/import

### 🚀 Innovative Features

#### 1. Predictive Performance Alerts
- **Algorithm:** Linear regression prediction
- **Features:**
  - Risk level classification (Critical/High/Medium/Low)
  - Confidence scoring (65-95%)
  - Trend analysis and forecasting
  - Early warning system for performance decline

#### 2. Smart Training Recommendations
- **Algorithm:** Intelligent skill gap analysis
- **Features:**
  - Automated skill gap identification
  - 6 training programs with compatibility scoring
  - Priority ranking (High/Medium/Low)
  - Personalized program matching

#### 3. Anomaly Detection + Auto-Narrative Insights
- **Algorithm:** Statistical anomaly detection
- **Features:**
  - Visual anomaly markers on charts
  - Detects spikes, drops, and consecutive declines
  - AI-generated narrative insights
  - Actionable recommendations

#### 4. Advanced Peer Comparison
- **Algorithm:** Multi-dimensional similarity matching
- **Features:**
  - Intelligent peer matching
  - Radar chart visualizations
  - Similarity scoring (0-100%)
  - Side-by-side performance analysis

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

## 📊 KPI Framework

### 21 KPIs across 7 Performance Groups

| Group | KPIs | Weight |
|-------|------|--------|
| **Core Competencies** | Product Knowledge, Communication Skills, Problem-Solving | 10% |
| **Daily Sales Operations** | Sales Target, Transaction Value, Units/Transaction, Attachment Rate | 20% |
| **Customer Service Quality** | Satisfaction Score, Service Recovery, Resolution Time | 15% |
| **Sales Target Contribution** | Monthly Target, Quarterly Growth | 20% |
| **Training & Team** | Participation, Knowledge Sharing, Collaboration, Mentoring | 15% |
| **Inventory & Cost Control** | Stock Accuracy, Shrinkage Prevention | 10% |
| **Store Operations** | Punctuality, Visual Merchandising, Cleanliness | 10% |

### Performance Levels
- **Excellent**: 4.5-5.0 (90-100%)
- **Good**: 3.5-4.49 (70-89%)
- **Satisfactory**: 2.5-3.49 (50-69%)
- **Needs Improvement**: <2.5 (<50%)

## 🔧 Installation

### Prerequisites
- XAMPP/WAMP/LAMP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Git (for cloning)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/ErnestEZY/kpi_monitoring_system.git
   cd kpi_monitoring_system
   ```

2. **Set up database**
   ```bash
   # Option A: Complete import (recommended)
   # Double-click: database/import_complete_database.bat
   
   # Option B: Manual setup
   # 1. Create database in phpMyAdmin: kpi_system
   # 2. Import: database/new_schema.sql
   # 3. Import: database/updated_sample_data.sql
   # 4. Run: database/import_data.bat
   ```

3. **Configure database connection**
   ```php
   // Edit config/database.php if needed
   // Default: localhost, root, no password
   ```

4. **Access the system**
   ```
   URL: http://localhost/kpi_monitoring_system/
   Login: admin / admin123
   ```

### Detailed Installation

See [Installation Guide](docs/INSTALLATION_GUIDE.md) for step-by-step instructions.

## 📁 Project Structure

```
kpi_system/
├── api/                          # RESTful API endpoints
│   ├── get_dashboard_data.php
│   ├── innovative_features_api.php
│   ├── kpi_api.php
│   └── kpi_calculations.php
├── assets/
│   ├── css/                      # Stylesheets
│   └── js/                       # JavaScript modules
├── auth/                         # Authentication
├── config/                       # Configuration files
├── database/                     # Database files & scripts
│   ├── new_schema.sql
│   ├── updated_sample_data.sql
│   ├── export_complete_database.bat
│   ├── import_complete_database.bat
│   └── *.md (documentation)
├── docs/                         # Project documentation
├── includes/                     # PHP includes
├── supervisor/                   # Supervisor pages
│   ├── dashboard.php
│   ├── kpi_dashboard.php
│   ├── staff_profile.php
│   ├── analytics.php
│   ├── predictive_alerts_new.php
│   ├── training_recommendations.php
│   ├── anomaly_detection.php
│   └── peer_comparison_new.php
├── tests/                        # Test files
├── utils/                        # Utility scripts
└── README.md
```

## 📤 Database Export & Distribution

### Export Your Database

```bash
# One-click export
Double-click: database/export_complete_database.bat

# Creates: database/kpi_system_complete.sql
```

### Share with Others

Package these files:
- `kpi_system_complete.sql` (database)
- `import_complete_database.bat` (import script)
- `README_FOR_RECIPIENTS.txt` (instructions)

Recipients can import in one click!

See [Database Export Guide](database/DATABASE_EXPORT_GUIDE.md) for details.

## 🔐 Security Features

- Session-based authentication with timeout
- PDO prepared statements (SQL injection prevention)
- XSS prevention (htmlspecialchars)
- Role-based access control
- Input validation and sanitization

## 📈 Algorithms & Calculations

### Linear Regression (Predictive Alerts)
```
y = mx + b

Where:
- y = predicted score
- m = slope (performance trend)
- x = time period
- b = intercept

Confidence = R² × 100 (scaled to 65-95%)
```

### Training Matching Algorithm
1. Skill gap severity analysis (Critical/Moderate/Minor)
2. Focus area alignment (Program vs. weak areas)
3. Prerequisites validation (Score requirements)
4. Compatibility scoring (0-100%)

### Anomaly Detection
- Statistical threshold detection (±2 standard deviations)
- Consecutive decline pattern recognition
- Spike and drop identification
- Context-aware narrative generation

## 📊 Sample Data

The system includes comprehensive sample data:

- **13 Staff Members** with varied performance profiles
- **21 KPIs** with realistic scores
- **5 Years** of data (2022-2026)
- **1,092+ KPI scores** across all staff and years
- **52+ Supervisor comments**
- **Progressive improvements** showing training effectiveness

See [Data Overview](database/DATA_OVERVIEW.txt) for details.

## 🎨 Screenshots

### Dashboard
![Dashboard](docs/screenshots/dashboard.png)

### Predictive Alerts
![Predictive Alerts](docs/screenshots/predictive_alerts.png)

### Training Recommendations
![Training Recommendations](docs/screenshots/training_recommendations.png)

### Peer Comparison
![Peer Comparison](docs/screenshots/peer_comparison.png)

## 📚 Documentation

- [Installation Guide](docs/INSTALLATION_GUIDE.md)
- [Database Design](docs/DATABASE_DESIGN_DOCUMENTATION.txt)
- [Functional Specifications](docs/FUNCTIONAL_SPECIFICATIONS_REPORT.txt)
- [Project Proposal](docs/KPI_SYSTEM_PROJECT_PROPOSAL.md)
- [Database Export Guide](database/DATABASE_EXPORT_GUIDE.md)
- [Training Recommendations Explained](docs/TRAINING_RECOMMENDATIONS_EXPLAINED.md)

## 🧪 Testing

### Run Tests

```bash
# Test KPI calculations
http://localhost/kpi_system/tests/test_kpi_calculations.php

# Test peer comparison
http://localhost/kpi_system/tests/test_peer_comparison.php

# Test database connection
http://localhost/kpi_system/test_db.php
```

### Test Data

Import test data for innovative features:
```bash
# Predictive alerts & training recommendations
database/import_test_data.bat

# Training recommendations (2026 data)
database/import_training_2026.bat
```

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check `config/database.php` credentials
- Ensure MySQL service is running
- Verify database name is `kpi_system`

**Login Not Working**
- Default credentials: `admin` / `admin123`
- Check supervisors table has data
- Clear browser cache/cookies

**Charts Not Displaying**
- Ensure Chart.js CDN is accessible
- Check browser console for errors
- Verify data exists for selected year

See [Troubleshooting Guide](docs/TROUBLESHOOTING.md) for more solutions.

## 👥 Default Credentials

| Username | Password | Role |
|----------|----------|------|
| admin    | admin123 | Supervisor |
| manager1 | pass123  | Supervisor |
| super1   | super123 | Supervisor |

**⚠️ Change passwords in production!**

## 🔄 Version History

### Version 1.0 (March 2026)
- Initial release
- 21 KPIs across 7 performance groups
- 4 innovative features
- Complete database export/import system
- Comprehensive documentation

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## 🤝 Contributing

This is an academic project. For suggestions or improvements:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is developed for academic purposes.

## 👨‍💻 Development Team

- **Developer:** Ernest EZY
- **Institution:** [Your Institution]
- **Course:** [Your Course]
- **Year:** 2026

## 📞 Support

For questions or support:
- **GitHub Issues:** [Create an issue](https://github.com/ErnestEZY/kpi_monitoring_system/issues)
- **Documentation:** See `docs/` folder
- **Email:** [Your Email]

## 🙏 Acknowledgments

- Bootstrap team for the responsive framework
- Chart.js team for visualization library
- PHP and MySQL communities
- All open-source contributors

## 🎓 Academic Compliance

This system fulfills all assignment requirements:

✅ KPI & Performance Management  
✅ Database & Data Persistence (3NF)  
✅ Sales Assistant Profile Dashboard  
✅ Supervisor Dashboard  
✅ Interactive Analytics Dashboard  
✅ Storytelling with Data (Narrative generation)  
✅ 4 Innovative Features  
✅ Supervisor-only perspective  
✅ Git version control  
✅ Comprehensive documentation  

---

**⭐ If you find this project useful, please give it a star!**

**📖 For detailed documentation, see the [docs](docs/) folder.**

**🚀 Ready to deploy? See [Deployment Guide](docs/DEPLOYMENT.md)**
