@echo off
REM ===== KIOSK (kumukuha ng ticket) =====
REM Buong screen, at diretso sa printer (walang print dialog) -- --kiosk-printing
REM Palitan ang URL kung iba ang pangalan ng folder / IP address ng server.
set URL=http://localhost/bank_queue_system/customer
set CHROME="C:\Program Files\Google\Chrome\Application\chrome.exe"
if not exist %CHROME% set CHROME="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"

REM Inilagay ang window flags para madaling ma-close gamit ang Alt+F4 o Task Manager
start "" %CHROME% --kiosk --kiosk-printing --disable-pinch --overscroll-history-navigation=0 --no-first-run --disable-default-apps --user-data-dir="%TEMP%\bq_kiosk" %URL%