@echo off
REM ===== NOW SERVING (TV / monitor) =====
REM Naka-on agad ang tunog (--autoplay-policy) kahit walang nag-click pagkatapos mag-restart.
set URL=http://localhost/bank_queue_system/now_serving
set CHROME="C:\Program Files\Google\Chrome\Application\chrome.exe"
if not exist %CHROME% set CHROME="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
start "" %CHROME% --kiosk --autoplay-policy=no-user-gesture-required --user-data-dir="%TEMP%\bq_tv" %URL%
