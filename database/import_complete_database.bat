@echo off
echo ========================================
echo  Import Complete KPI System Database
echo ========================================
echo.
echo This will:
echo  1. Create 'kpi_system' database
echo  2. Import all tables and data
echo  3. Set up everything automatically
echo.
echo WARNING: This will DROP existing kpi_system database!
echo Make sure you have a backup if needed.
echo.
echo Make sure XAMPP MySQL is running!
echo.
pause

echo.
echo Step 1: Creating database...
echo.

REM Create database
mysql -u root -e "DROP DATABASE IF EXISTS kpi_system; CREATE DATABASE kpi_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %ERRORLEVEL% EQU 0 (
    echo Database created successfully!
    echo.
    echo Step 2: Importing data...
    echo.
    
    REM Import the SQL file
    mysql -u root kpi_system < kpi_system_complete.sql
    
    if %ERRORLEVEL% EQU 0 (
        echo.
        echo ========================================
        echo  SUCCESS! Database imported.
        echo ========================================
        echo.
        echo Your KPI System is now ready to use!
        echo.
        echo Next steps:
        echo  1. Open: http://localhost/kpi_system
        echo  2. Login with:
        echo     Username: admin
        echo     Password: admin123
        echo.
        echo The system includes:
        echo  - Sample staff data
        echo  - KPI master list (21 KPIs)
        echo  - Performance scores
        echo  - All features ready to use
        echo.
    ) else (
        echo.
        echo ERROR! Import failed.
        echo Check if kpi_system_complete.sql exists in this folder.
        echo.
    )
) else (
    echo.
    echo ERROR! Could not create database.
    echo Make sure MySQL is running in XAMPP.
    echo.
)

pause
