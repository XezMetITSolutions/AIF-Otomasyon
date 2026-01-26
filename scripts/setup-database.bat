@echo off
REM AIF Otomasyon Sistemi - Veritabanı Kurulum Scripti (Batch)
REM Bu script, PHP script'ini çalıştırır

echo.
echo ========================================
echo AIF Otomasyon - Veritabanı Kurulumu
echo ========================================
echo.

cd /d "%~dp0.."

REM PHP'nin yolunu kontrol et
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP bulunamadı!
    echo Lütfen PHP'yi yükleyin veya PATH'e ekleyin.
    echo.
    pause
    exit /b 1
)

echo PHP bulundu, script çalıştırılıyor...
echo.

php scripts\setup-database.php

echo.
pause

