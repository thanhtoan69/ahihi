@echo off
echo Executing Phase 15: AI/ML Infrastructure...
echo.

C:\xampp\mysql\bin\mysql.exe -u root environmental_platform < phase15_ai_ml_infrastructure.sql

if %errorlevel% equ 0 (
    echo Phase 15 executed successfully!
    echo.
    echo Checking table count...
    C:\xampp\mysql\bin\mysql.exe -u root environmental_platform -e "SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'environmental_platform';"
    echo.
    echo Listing AI/ML tables...
    C:\xampp\mysql\bin\mysql.exe -u root environmental_platform -e "SELECT table_name FROM information_schema.tables WHERE table_schema = 'environmental_platform' AND table_name LIKE 'ai_%' ORDER BY table_name;"
) else (
    echo Phase 15 execution failed with error code %errorlevel%
)

pause
