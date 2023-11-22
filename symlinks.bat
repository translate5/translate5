:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: If we're on Windows - the below commands will create necessary symlinks   ::
:: as the ones that are coming with cloned repo do appear as just text files ::
:: with paths of symlinks destinations as contents of those text files       ::
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Private plugins
for %%i in (
  AcrossHotfolder ConnectWorldserver DeepL Enssner GroupShare InstantTranslate MittagQI
  PrivateTests Schueco Soluzione TermPortal TildeMT TrackChanges Translate24 VisualReview
) do (
   del       application\modules\editor\Plugins\%%i
   mklink /D application\modules\editor\Plugins\%%i ..\PrivatePlugins\%%i
)

:: This symlink is needed to speed up loading of plugins resources, as otherwise
:: they are loaded via php-proxy in IndexController->pluginpublicAction() which
:: is slow on Windows, and horrible slow up to unusable in case of docker-compose
:: setup having '/var/www/translate5' directory of a php-container filesystem
:: mapped as a bind-volume to some directory within Windows-host filesystem for
:: development purposes
mklink /D public\plugins ..\application\modules\editor\Plugins

:: Jquery
del public\js\jquery-ui
mklink /D public\js\jquery-ui ..\..\vendor\jquery\jquery-ui

:: Rangy
del public\js\rangy
mklink /D public\js\rangy     ..\..\vendor\translate5\rangy-lib

:: Fontawesone
for %%i in (css,js,webfonts) do (
  del       public\modules\editor\fontawesome\%%i
  mklink /D public\modules\editor\fontawesome\%%i ..\..\..\..\vendor\fortawesome\font-awesome\%%i
)

:: DateTimeField and DateTimePicker
for %%i in (DateTimeField.js,DateTimePicker.js) do (
  del    public\modules\editor\js\ux\%%i
  mklink public\modules\editor\js\ux\%%i ..\..\..\..\..\vendor\gportela85\datetimefield\src\%%i
)

:: Visual
mklink /D public\visual ..\data\visual
mklink /D data\visual\fonts\visualReview ..\..\editorVisualReviewFonts
mklink /D data\visual\resources\visualReview ..\..\..\application\modules\editor\PrivatePlugins\VisualReview\public\resources

:: Material design icons
for %%i in (font) do (
  del       public\modules\editor\material-design-icons\%%i
  mklink /D public\modules\editor\material-design-icons\%%i ..\..\..\..\vendor\axelbecher\material-design-icons\%%i
)




