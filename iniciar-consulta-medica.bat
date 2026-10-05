@echo off
cd C:\xampp\htdocs\turnero
C:\xampp\php\php.exe artisan urgencias:sincronizar-consulta-medica >> C:\xampp\htdocs\turnero\storage\logs\bat-debug-consulta.log 2>&1