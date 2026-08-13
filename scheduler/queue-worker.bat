@echo off
cd /d "C:\Users\OMS\Desktop\Facturation_stock"

:loop
"C:\xampp\php\php.exe" artisan queue:work --tries=3 --timeout=90 --max-time=3540
timeout /t 10 /nobreak >nul
goto loop