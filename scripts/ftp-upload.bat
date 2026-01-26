@echo off
REM AIF Otomasyon Sistemi - FTP Upload Script (Batch)
REM Bu script, PowerShell script'ini çalıştırır

echo.
echo ========================================
echo AIF Otomasyon Sistemi - FTP Upload
echo ========================================
echo.

cd /d "%~dp0.."

powershell.exe -ExecutionPolicy Bypass -File "scripts\ftp-upload.ps1"

pause

