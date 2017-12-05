@echo off
cls
set CONFIG_FILE=windows-installer-config.ini
if NOT exist %CONFIG_FILE% (
  echo.
  echo   You are using the installer the first time!
  echo   The file 'windows-installer-config.ini' was generated right now 
  echo   with default configuration values: 
  echo.
  echo   INSTALL_PHP_PATH=c:\xampp\php\php.exe
  echo   INSTALL_MYSQL_PATH=c:\xampp\mysql\bin\mysql.exe
  echo.
  echo   Please adjust the paths to php.exe and mysql.exe in that config file, 
  echo   if you did a manual windows installation of translate5.
  echo INSTALL_PHP_PATH=c:\xampp\php\php.exe > %CONFIG_FILE%
  echo INSTALL_MYSQL_PATH=c:\xampp\mysql\bin\mysql.exe >> %CONFIG_FILE%
  exit /B
)

for /f "delims=" %%x in (%CONFIG_FILE%) do (set "%%x")
echo.
echo   Starting translate5 installer / updater:
echo.
echo   using as php.exe:  %INSTALL_PHP_PATH%
echo   using as mysql.exe:  %INSTALL_MYSQL_PATH%
echo.
"%INSTALL_PHP_PATH%" -r "require_once('application/modules/default/Models/Installer/Standalone.php'); Models_Installer_Standalone::mainLinux(array('mysql_bin' => '%INSTALL_MYSQL_PATH%'));"
echo.
pause
