@echo off
cd /d "c:\xampp\htdocs\moitruong"
"C:\xampp\mysql\bin\mysql.exe" -u root environmental_platform < phase7-11_simplified.sql
echo Phase 7-11 execution completed!
pause
