@echo off
echo ============================================
echo KPI System - Database Import Script
echo ============================================
echo.

echo Step 1: Importing base data (2022)...
echo Please import updated_sample_data.sql via phpMyAdmin:
echo 1. Open http://localhost/phpmyadmin
echo 2. Select 'kpi_system' database
echo 3. Click 'Import' tab
echo 4. Choose 'updated_sample_data.sql'
echo 5. Click 'Go'
echo.
pause

echo.
echo Step 2: Generating multi-year data (2023-2025)...
C:\xampp\php\php.exe generate_multiyear_data.php

echo.
echo ============================================
echo Import Complete!
echo ============================================
echo.
echo You can now test the training recommendations:
echo http://localhost/kpi_system/supervisor/training_recommendations.php
echo.
pause
