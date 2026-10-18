@echo off
setlocal
title SIATRACK Local Telegram
cd /d "%~dp0"

where php >nul 2>&1
if errorlevel 1 (
    echo PHP was not found. Install PHP or add it to PATH first.
    pause
    exit /b 1
)

where ngrok >nul 2>&1
if errorlevel 1 (
    echo ngrok was not found. Install ngrok and add it to PATH first.
    echo Then configure your ngrok authtoken with: ngrok config add-authtoken YOUR_NGROK_AUTHTOKEN
    pause
    exit /b 1
)

echo Checking for a configured ngrok authtoken...
powershell -NoProfile -Command "$std=Join-Path $env:LOCALAPPDATA 'ngrok\ngrok.yml'; if(-not (Test-Path -LiteralPath $std)){ $pkg=Get-ChildItem -Path (Join-Path $env:LOCALAPPDATA 'Packages') -Filter 'ngrok.yml' -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1; if($pkg){ New-Item -ItemType Directory -Force -Path (Split-Path $std) | Out-Null; Copy-Item -LiteralPath $pkg.FullName -Destination $std -Force } }; $hasToken=[bool]$env:NGROK_AUTHTOKEN; if(-not $hasToken -and (Test-Path -LiteralPath $std)){ $line=Get-Content -LiteralPath $std | Where-Object {$_ -match '^\s*authtoken\s*:'} | Select-Object -First 1; if($line){$value=$line.Substring($line.IndexOf(':')+1).Trim(); $hasToken=$value.Trim([char]34,[char]39).Length -gt 0} }; if($hasToken){exit 0}; exit 1"
if errorlevel 1 (
    echo No ngrok authtoken is configured.
    echo Add one with: ngrok config add-authtoken YOUR_NGROK_AUTHTOKEN
    echo Get your authtoken from the ngrok dashboard. This launcher will not display or store it.
    pause
    exit /b 1
)

if exist "%LOCALAPPDATA%\ngrok\ngrok.yml" (
    echo Validating ngrok configuration...
    ngrok config check >nul 2>&1
    if errorlevel 1 (
        echo The ngrok configuration is invalid. Fix it with: ngrok config edit
        pause
        exit /b 1
    )
)

echo Checking for the existing Laravel server on port 8000...
powershell -NoProfile -Command "$c=New-Object Net.Sockets.TcpClient; try { $c.Connect('127.0.0.1',8000); exit 0 } catch { exit 1 } finally { $c.Dispose() }"
if errorlevel 1 (
    echo Laravel is not running on port 8000.
    echo Start it first with START_SERVER.bat, then run this file again.
    pause
    exit /b 1
)

if not exist ".env" (
    echo .env was not found. Create it and configure the app before starting.
    pause
    exit /b 1
)

echo Clearing Laravel's cached configuration...
php artisan config:clear
if errorlevel 1 (
    echo Could not clear Laravel configuration. Fix the error above and retry.
    pause
    exit /b 1
)

echo Starting ngrok tunnel for port 8000...
start "SIATRACK ngrok" cmd /k "ngrok http 8000"

set "URL_FILE=%TEMP%\siatrack-ngrok-%RANDOM%.txt"
set "NGROK_URL="
set /a WAIT_COUNT=0

:wait_for_ngrok
if exist "%URL_FILE%" del /q "%URL_FILE%" >nul 2>&1
powershell -NoProfile -Command "$ErrorActionPreference='Stop'; try { $t=Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2; $u=$t.tunnels | Where-Object { $_.proto -eq 'https' } | Select-Object -First 1 -ExpandProperty public_url; if ($u) { Set-Content -NoNewline -Path '%URL_FILE%' -Value $u } } catch {}"
if exist "%URL_FILE%" set /p NGROK_URL=<"%URL_FILE%"
if defined NGROK_URL goto register_webhook

set /a WAIT_COUNT+=1
if %WAIT_COUNT% GEQ 30 goto ngrok_timeout
timeout /t 2 /nobreak >nul
goto wait_for_ngrok

:register_webhook
if exist "%URL_FILE%" del /q "%URL_FILE%" >nul 2>&1
echo.
echo ngrok HTTPS URL: %NGROK_URL%
echo Registering the Telegram webhook...
php artisan telegram:webhook %NGROK_URL%/api/telegram/webhook
if errorlevel 1 (
    echo.
    echo Webhook registration failed.
    echo Check that TELEGRAM_BOT_ENABLED=true, TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET
    echo are configured in .env, and that the token is valid.
    echo Keep the Laravel and ngrok windows open while testing.
    pause
    exit /b 1
)

echo.
echo Telegram local test setup is ready. Send /start to your bot.
echo Keep the Laravel server and ngrok windows open.
pause
exit /b 0

:ngrok_timeout
if exist "%URL_FILE%" del /q "%URL_FILE%" >nul 2>&1
echo.
echo Could not find an ngrok HTTPS tunnel at http://127.0.0.1:4040.
echo Check the ngrok window for an authtoken, network, or startup error.
pause
exit /b 1
