@echo off
setlocal enabledelayedexpansion

title Naumil Rice - System Runner
cd /d "%~dp0"

:: ----------------------------------------------------------------------
:: Pengaturan Argumen / Opsi
:: Penggunaan:
::   run.bat                 -> Menjalankan Laravel + Vite (mode single-terminal)
::   run.bat -s              -> Menjalankan Laravel & Vite di jendela terpisah
::   run.bat -nb             -> Menjalankan tanpa membuka browser otomatis
::   run.bat -r              -> Menyertakan WebSocket (Laravel Reverb)
::   run.bat -q              -> Menyertakan Queue Worker
::   run.bat --all           -> Menjalankan Laravel + Vite + Reverb + Queue
:: ----------------------------------------------------------------------

set "MODE=concurrent"
set "OPEN_BROWSER=1"
set "WITH_REVERB=1"
set "WITH_QUEUE=0"

:parse_args
if "%~1"=="" goto after_parse
if /i "%~1"=="--separate" set "MODE=separate"
if /i "%~1"=="-s" set "MODE=separate"
if /i "%~1"=="--no-browser" set "OPEN_BROWSER=0"
if /i "%~1"=="-nb" set "OPEN_BROWSER=0"
if /i "%~1"=="--reverb" set "WITH_REVERB=1"
if /i "%~1"=="-r" set "WITH_REVERB=1"
if /i "%~1"=="--queue" set "WITH_QUEUE=1"
if /i "%~1"=="-q" set "WITH_QUEUE=1"
if /i "%~1"=="--all" (
    set "WITH_REVERB=1"
    set "WITH_QUEUE=1"
)
shift
goto parse_args

:after_parse
echo ======================================================================
echo                NAUMIL RICE - MULTI-STORE SYSTEM
echo ======================================================================
echo.

:: 1. Verifikasi PHP
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP tidak terdeteksi di PATH sistem Anda!
    echo Silakan install PHP versi 8.2 atau lebih baru dan tambahkan direktori PHP ke Environment Variables.
    echo.
    pause
    exit /b 1
)

:: 2. Verifikasi Node.js & NPM
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Node.js / NPM tidak terdeteksi di PATH sistem Anda!
    echo Silakan install Node.js dari https://nodejs.org/ terlebih dahulu.
    echo.
    pause
    exit /b 1
)

:: 3. Verifikasi file konfigurasi .env
if not exist ".env" (
    echo [*] File .env belum ditemukan. Menyalin dari .env.example...
    copy ".env.example" ".env" >nul
    echo [*] Menghasilkan APP_KEY baru...
    call php artisan key:generate
    echo.
)

:: 4. Verifikasi folder vendor (Composer)
if not exist "vendor\autoload.php" (
    echo [*] Folder vendor belum ditemukan. Menjalankan composer install...
    call composer install
    if %errorlevel% neq 0 (
        echo [ERROR] Gagal menjalankan composer install.
        pause
        exit /b %errorlevel%
    )
    echo.
)

:: 5. Verifikasi folder node_modules (NPM)
if not exist "node_modules" (
    echo [*] Folder node_modules belum ditemukan. Menjalankan npm install...
    call npm install
    if %errorlevel% neq 0 (
        echo [ERROR] Gagal menjalankan npm install.
        pause
        exit /b %errorlevel%
    )
    echo.
)

echo ----------------------------------------------------------------------
echo  [*] Mode             : %MODE%
echo  [*] Laravel Backend  : http://127.0.0.1:8000
echo  [*] Vite Dev Server  : http://127.0.0.1:5173
echo  [*] Super Admin      : http://127.0.0.1:8000/superadmin
echo  [*] Tebet Store      : http://127.0.0.1:8000/tebet
echo  [*] Sudirman Store   : http://127.0.0.1:8000/sudirman
if "%WITH_REVERB%"=="1" echo  [*] Reverb WebSocket : ws://127.0.0.1:8085
if "%WITH_QUEUE%"=="1"  echo  [*] Queue Worker     : Aktif (database)
echo ----------------------------------------------------------------------

if "%OPEN_BROWSER%"=="1" (
    echo  [!] Membuka browser ke http://127.0.0.1:8000 ...
    start "" cmd /c "timeout /t 3 /nobreak >nul 2>&1 & start http://127.0.0.1:8000"
)

echo  [!] Tekan [Ctrl + C] di jendela ini untuk menghentikan server.
echo ======================================================================
echo.

:: Eksekusi Mode Jendela Terpisah (Separate Windows)
if "%MODE%"=="separate" (
    echo [*] Menjalankan server di jendela Command Prompt terpisah...
    start "Naumil Rice - Laravel Server" cmd /k "title Naumil Laravel Server && php artisan serve --port=8000"
    start "Naumil Rice - Vite Dev" cmd /k "title Naumil Vite Dev && npm run dev"
    if "%WITH_REVERB%"=="1" (
        start "Naumil Rice - Reverb WebSocket" cmd /k "title Naumil Reverb WebSocket && php artisan reverb:start --port=8085"
    )
    if "%WITH_QUEUE%"=="1" (
        start "Naumil Rice - Queue Worker" cmd /k "title Naumil Queue Worker && php artisan queue:listen --tries=1"
    )
    echo [*] Semua server berhasil dijalankan!
    exit /b 0
)

:: Eksekusi Mode Terpadu / Single Terminal (Concurrent)
set "RUN_CONCURRENT=node node_modules\concurrently\dist\bin\concurrently.js"
if not exist "node_modules\concurrently\dist\bin\concurrently.js" (
    set "RUN_CONCURRENT=npx concurrently"
)

if "%WITH_REVERB%"=="1" if "%WITH_QUEUE%"=="1" (
    call %RUN_CONCURRENT% -k --prefix-colors "cyan.bold,magenta.bold,yellow.bold,green.bold" --names "SERVER,VITE,REVERB,QUEUE" "php artisan serve --port=8000" "npm run dev" "php artisan reverb:start --port=8085" "php artisan queue:listen --tries=1"
    exit /b %errorlevel%
)

if "%WITH_REVERB%"=="1" (
    call %RUN_CONCURRENT% -k --prefix-colors "cyan.bold,magenta.bold,yellow.bold" --names "SERVER,VITE,REVERB" "php artisan serve --port=8000" "npm run dev" "php artisan reverb:start --port=8085"
    exit /b %errorlevel%
)

if "%WITH_QUEUE%"=="1" (
    call %RUN_CONCURRENT% -k --prefix-colors "cyan.bold,magenta.bold,green.bold" --names "SERVER,VITE,QUEUE" "php artisan serve --port=8000" "npm run dev" "php artisan queue:listen --tries=1"
    exit /b %errorlevel%
)

:: Default: Laravel Backend & Vite Frontend
call %RUN_CONCURRENT% -k --prefix-colors "cyan.bold,magenta.bold" --names "LARAVEL,VITE" "php artisan serve --port=8000" "npm run dev"

exit /b %errorlevel%
