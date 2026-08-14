@echo off
echo Launching Yum's Berchg Food Order system in Microsoft Edge...

if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" "http://localhost/food_order/index.php"
) else if exist "C:\Program Files\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files\Microsoft\Edge\Application\msedge.exe" "http://localhost/food_order/index.php"
) else (
    start msedge "http://localhost/food_order/index.php"
)