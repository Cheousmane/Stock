@echo off
cd /d "C:\Users\OMS\Desktop\Facturation_stock"

:loop
"C:\xampp\php\php.exe" artisan schedule:work
timeout /t 10 /nobreak >nul
goto loop