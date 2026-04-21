@echo off
:loop
cd /d C:\xampp\htdocs\turnero
C:\xampp\php\php.exe artisan schedule:run
timeout /t 60 /nobreak
goto loop