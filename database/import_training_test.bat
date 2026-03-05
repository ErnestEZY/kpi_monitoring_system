@echo off
echo ========================================
echo  Import Training Recommendations Test Data
echo ========================================
echo.
echo This will create 6 staff with different skill gaps:
echo  - Staff 1: Critical Customer Service gap
echo  - Staff 2: Critical Sales gap
echo  - Staff 3: Moderate Operations gap
echo  - Staff 4: Multiple critical gaps
echo  - Staff 5: Minor Leadership gap
echo  - Staff 6: Poor across all categories
echo.
echo Make sure XAMPP MySQL is running!
echo.
pause

echo.
echo Importing test data...
echo.

mysql -u root kpi_system < test_training_recommendations.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  SUCCESS! Test data imported.
    echo ========================================
    echo.
    echo Next steps:
    echo 1. Open browser and login to the system
    echo 2. Navigate to: Training Recommendations page
    echo.
    echo You should see:
    echo  ✓ 6 staff needing training
    echo  ✓ Various skill gap badges (red/yellow/blue)
    echo  ✓ Different priority levels (High/Medium/Low)
    echo  ✓ Recommended training programs
    echo.
    echo Test the filters:
    echo  ✓ Priority: High/Medium/Low
    echo  ✓ Skill Gap: Critical/Moderate/Minor
    echo  ✓ Department: Select any department
    echo.
    echo Click "Apply Filters" to see filtering in action!
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
    echo  - No data for year 2025 exists yet
    echo.
    echo Try importing via phpMyAdmin:
    echo 1. Open phpMyAdmin
    echo 2. Select 'kpi_system' database
    echo 3. Click 'Import' tab
    echo 4. Choose: test_training_recommendations.sql
    echo 5. Click 'Go'
    echo.
)

pause
