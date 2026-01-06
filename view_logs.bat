@echo off
echo === Latest Import Logs ===
echo.

set LOG_DIR=c:\xampp_new\htdocs\windeep_finance\application\logs
set TODAY=%date:~10,4%-%date:~4,2%-%date:~7,2%
set LOG_FILE=%LOG_DIR%\log-%TODAY%.php

if exist "%LOG_FILE%" (
    echo Log file: %LOG_FILE%
    echo.
    findstr /C:"import" /C:"match" /C:"transaction" /C:"Parsed" "%LOG_FILE%"
) else (
    echo No log file found for today: %LOG_FILE%
    echo.
    echo Available log files:
    dir /b "%LOG_DIR%\log-*.php"
)

echo.
pause