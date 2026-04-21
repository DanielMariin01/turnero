@echo off
:loop
cd /d C:\xampp\htdocs\turnero
C:\xampp\php\php.exe artisan queue:work database --sleep=3 --tries=3 --timeout=60
timeout /t 5 /nobreak
goto loop