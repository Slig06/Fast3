@echo off

set phprc=
PATH=php5;%PATH%

:start
echo Starting Fast3.2.x...

"php5\php.exe" fast.php ingame.cfg update_stop10


rem If the script is run with 'update_stop10' as 2nd argument then
rem after autoupdate the script will stop with a errorlevel 10 to
rem indicate it should be restarted.

rem Next lines are used to restart the script after an autoupdate
if not errorlevel 10 goto end
if errorlevel 10 goto start
:end
pause
