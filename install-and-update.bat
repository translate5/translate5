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
  echo ; The PHP path is used on each call of the install-and-update.bat > %CONFIG_FILE%
  echo INSTALL_PHP_PATH=c:\xampp\php\php.exe >> %CONFIG_FILE%
  echo ; The MySQL path here is only used for installation of translate5! >> %CONFIG_FILE%
  echo ; On installation the path is stored in the installation.ini config file. >> %CONFIG_FILE%
  echo ; If it has to be changed after installation, >> %CONFIG_FILE%
  echo ; please change / set the following line in application/config/installation.ini >> %CONFIG_FILE%
  echo ;   resources.db.executable = "ABSOLUTE PATH OF YOUR MYSQL.EXE" >> %CONFIG_FILE%
  echo INSTALL_MYSQL_PATH=c:\xampp\mysql\bin\mysql.exe >> %CONFIG_FILE%
  exit /B
)

for /f "delims=" %%x in (%CONFIG_FILE%) do (set "%%x")
echo.
echo   Starting translate5 installer / updater:
echo.
echo   using as php.exe:  %INSTALL_PHP_PATH%
echo   using as mysql.exe on installation:  %INSTALL_MYSQL_PATH%
echo.
echo   for mysql.exe on updates see instructions in windows-installer-config.ini
echo.

set "CONFIG="
if "%~1" == "--check" (
    set "CONFIG=,'updateCheck' => '1'"
	goto run
)
if "%~1" == "--database" (
    set "CONFIG=,'dbOnly' => '1'"
	goto run
)
if "%~1" == "--help" (
    set "CONFIG=,'help' => '1'"
	goto run
)
if "%~1" == "--appState" (
    set "CONFIG=,'applicationState' => '1'"
	goto run
)
set "SECOND=%2"
if "%~1" == "--maintenance" (
	goto maintain
)
if NOT "%~1" == "" (
	set "CONFIG=,'applicationZipOverride' => '%~1'"
)
goto run
:maintain
if "%~2" == "" (
	set "CONFIG=,'maintenance' => 'show'"
)
if NOT "%~2" == "" (
	set "CONFIG=,'maintenance' => '%~2'"
)
:run 
"%INSTALL_PHP_PATH%" -r "require_once('application/modules/default/Models/Installer/Standalone.php'); Models_Installer_Standalone::mainLinux(array('mysql_bin' => '%INSTALL_MYSQL_PATH%'%CONFIG%));"
echo.
pause
