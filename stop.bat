@echo off
setlocal enabledelayedexpansion

title Naumil Rice - Stop All Servers
cd /d "%~dp0"

echo ======================================================================
echo               NAUMIL RICE - STOP ALL SERVICES
echo ======================================================================
echo.

echo [*] Memeriksa dan menghentikan proses pada Port 8000 (Laravel Server)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000 ^| findstr LISTENING 2^>nul') do (
    echo     -> Menghentikan PID %%a
    taskkill /F /PID %%a >nul 2>&1
)

echo [*] Memeriksa dan menghentikan proses pada Port 5173 (Vite Dev Server)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :5173 ^| findstr LISTENING 2^>nul') do (
    echo     -> Menghentikan PID %%a
    taskkill /F /PID %%a >nul 2>&1
)

echo [*] Memeriksa jendela Command Prompt Naumil Rice...
taskkill /FI "WINDOWTITLE eq Naumil Laravel Server*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Naumil Vite Dev*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Naumil Reverb WebSocket*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq Naumil Queue Worker*" /T /F >nul 2>&1

echo.
echo ======================================================================
echo  [OK] Semua service Naumil Rice telah berhasil dihentikan.
echo ======================================================================
timeout /t 2 /nobreak >nul 2>&1
exit /b 0
