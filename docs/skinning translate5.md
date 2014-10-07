# Actual possible way(s) to do a skinning of translate5

## Editor
### CSS

In the configurations-table Zf_configuration modify the following config:

	runtimeOptions.publicAdditions.css = ['css/editorAdditions.css']

runtimeOptions.publicAdditions.css is a array, so you can add one or multiple css files.

**Hint for own skinning:** The editorAdditions.css file provided by translate5 contains the default translate5 logo and must therefore removed or overwritten by own css.

The CSS files of an own skin has to be placed in 

    APPLICATION_ROOT/client-specific/public

This results in the following Zf_configuration setting:

    runtimeOptions.publicAdditions.css = ['client-specific/editorAdditions.css']

The public directory above is already linked to the WEBROOT/client-specific/public directory. From there all custom styles and images can be accessed.

If MittagQI is maintaining the client-specific skin, the content of client-specific is provided by the build script and the deployment process is responsible to put the files in the desired place. For the unified skinning mechanism described below this must be changed, and the path to the CSS file(s) would then be:
	
### Logo and other image files

As specified above the default logo is defined by the default delivered translate5 css.

All other files needed by the skin should be delivered in the client-specific/public directory, so it is possible to do any customizing possible with images and css, also the background of the header.

### Additional HTML structure in the Editor Header (branding)

In addition to CSS and images above, own HTML structure can be added to the Editor header. Therefore the header provides an container where HTML can be added  by the following config in the DB, category layout:

	runtimeOptions.editor.branding = 'OWN HTML'
	
The container containing the above HTML is addressable by the following CSS:

	#head-panel .head-panel-brand

**Hint:** Code/Text in runtimeOptions.editor.branding will be send threw the translate5 translation mechanism. So ist you have defined a corresponding translation, the branding will be translatet into the actual language.

### Tag Images
Segment Tags are rendered in Translate5 as HTML or as images in edited segments. The HTML ones can be styled by using CSS, see above. For the images there are several configuration parameters. 
See therefore the DB config category *imagetag*. This category contains all necessary configurations.

#### MQM Image Tags
For MQM tagging also image tags are used. This images can also be configured like the above Image Tags. All the values existing for imageTags can also be changed for MQM Tags. **The Image Tag settings are the default values for MQM Tags!**
Per default some colors are different for MQM Tags, so the are listed in the DB Config category *qmimagetag*. The namespace for the mqm tag settings starts with: *runtimeOptions.imageTags.qmSubSegment.*

## Frame-pages

### CSS, image and other public files

The used CSS file by the default layout is defined in the following DB config (category layout):

	runtimeOptions.server.pathToCSS = '/css/translate5.css?v=2'
	
The first config “pathToCSS” defines the CSS file included by the default layout, and can therefore be customized. Another way to add more or customize the included CSS files is to provide a customized layout.

All public files like images and so on, must be placed in client-specific/public either managed manually by the client, or by MittagQI by the build and deploy process:

	APPLIACTION_ROOT/client-specific/public/YourStructure/
	
### Layout

The place of the layout file is defined in the application.ini and can can overwritten by adding the following setting in the installation.ini:

	resources.layout.layoutPath = APPLICATION_PATH "/client-specific/layouts/"

For customizing a own layout.phtml has to been provided on the above location.

By customizing the layout, own CSS can be supplied. For using the default layout see the above section about CSS and other public files.

### Own Menu

A own main menu can be provided by providing a own, edited layout.phtml as described above. 
	
### View Scripts
Own View Scripts can be provided in 

	APPLICATION_ROOT/client-specific/views/<modul>/scripts
	
You can either overwrite existing view files, by placing identically named files in the above defined directory, or you can add completly new view scripts which is described later.
<modul> in the above path ist the name of the actual-used modul. Normaly this should be "default" for ordinary frame-pages.

### Own Pages / Own Viewscripts / Disable default Viewscripts
If you want to provide your own pages in your skin, this is also possible in a simple manner. Each page is provided by one view script. So you can simply add own view scripts. They are callable as all other view scripts:

    /VIEWSCRIPTNAME/
    
For security reasons and to provide the posibility to deactivate default views, all valid views has to be listed in the following DB config, category system:

    runtimeOptions.content.viewTemplatesAllowed = ["index", "usage", "test", "source", "newsletter"]

##Translations
You can make client-specific translations. Therefor you should place your translation-xliff-files into

    APPLICATION_ROOT/client-specific/locales

Client-specific translation-files are loaded automatic after all other translation-files. So you can overwrite existing translations. Also you can define new translations which you use in your special views or layouts or even in your editor-branding as mentioned above.


## Other currently used client specific files

### Arial.ttf
For license reasons arial.ttf cant be included in the public translate5 repository, so we deliver it per client / installation. For the install-and-update-kit, this file should be fetched like an external dependency. This approach should also ensure that the file wont be deleted accidentally on further updates.

With the unified skinning mechanism this files has to kept and copied by the specific deploy scripts until the final release of the install-and-update-kit.

### index_prerun.php

This file is currently provided as some kind of a global specific file in: 	specific/index_prerun_fixes_translate5.net.php
Its needed only by our translate5.net instances. In general this can be used then as specific/clientX/index_prerun.php.
With the unified skinning mechanism this file is included automatically if it exists.

### .htaccess and .htpasswd

If above apache config files are needed, this is not in the responsibility of  the install-and-update-kit, so they should be therefore completely ignored. 