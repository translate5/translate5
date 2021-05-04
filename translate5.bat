@echo off
cls
set CONFIG_FILE=windows-installer-config.ini
if NOT exist %CONFIG_FILE% (
  echo.
  echo   You have to run the installer install-and-update.bat first!
  echo.
  exit /B
)

for /f "delims=" %%x in (%CONFIG_FILE%) do (set "%%x")
"%INSTALL_PHP_PATH%" ./Translate5/maintenance-cli.php %*
echo.
pause
