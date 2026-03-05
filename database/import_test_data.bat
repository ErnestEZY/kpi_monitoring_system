@echo off
echo ========================================
echo  Import Test Data for Innovative Features
echo ========================================
echo.
echo This will add test data to trigger:
echo  - Predictive Performance Alerts
echo  - Training Recommendations
echo.
echo Make sure XAMPP MySQL is running!
echo.
pause

echo.
echo Importing test data...
echo.

REM Try the simple version first (easier to debug)
mysql -u root kpi_system < test_innovative_features_data_simple.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  SUCCESS! Test data imported.
    echo ========================================
    echo.
    echo Next steps:
    echo 1. Open browser and login to the system
    echo 2. Navigate to "Predictive Alerts" page
    echo 3. Navigate to "Training Recommendations" page
    echo.
    echo You should see:
    echo  - 3+ risk alerts with different severity levels
    echo  - 5+ training recommendations with skill gaps
    echo.
    echo See TESTING_INNOVATIVE_FEATURES.md for details
    echo.
) else (
    echo.
    echo ========================================
    echo  ERROR! Import failed.
    echo ========================================
    echo.
    echo Possible issues:
    echo  - MySQL is not running (start XAMPP)
    echo  - Database 'kpi_system' doesn't exist
    echo  - No permission to access database
    echo  - No data for year 2025 exists yet
    echo.
    echo Try running this command manually:
    echo mysql -u root kpi_system ^< test_innovative_features_data_simple.sql
    echo.
    echo Or import via phpMyAdmin:
    echo 1. Open phpMyAdmin
    echo 2. Select 'kpi_system' database
    echo 3. Click 'Import' tab
    echo 4. Choose: test_innovative_features_data_simple.sql
    echo 5. Click 'Go'
    echo.
)

pause
