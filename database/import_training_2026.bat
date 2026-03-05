@echo off
echo ========================================
echo  Import Training Test Data for 2026
echo ========================================
echo.
echo This will create test data for year 2026
echo (6 staff with various skill gaps)
echo.
pause

mysql -u root kpi_system < test_training_2026.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  SUCCESS!
    echo ========================================
    echo.
    echo Now visit: http://localhost/kpi_system/supervisor/training_recommendations.php
    echo.
    echo You should see 6 staff with training recommendations!
    echo.
) else (
    echo.
    echo ERROR! Import failed.
    echo Try via phpMyAdmin instead.
    echo.
)

pause
