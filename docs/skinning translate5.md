# Actual possible way(s) to do a skinning of translate5

## Editor
### CSS

In the client specific editorApplication.ini file add / modify / delete the following entry:

	runtimeOptions.publicAdditions.css.0 = 'css/editorAdditions.css'

runtimeOptions.publicAdditions.css is a array, so you can add multiple css files.

**Hint for own skinning:** The editorAdditions.css file provided by translate5 contains the default translate5 logo and must the therefore removed from ini or overwritten by own css.

Currently the referenced css file can be placed everywhere. The deployment process is responsible to put the files in the desired place. For the unified skinning mechanism described below this must be changed, and the path to the CSS file(s) would then be:

	runtimeOptions.publicAdditions.css.0 = 'client-specific/hereWhatEverYouWantPath.css'
	
### Logo and other image files

As specified above the default logo is defined by the default delivered translate5 css.

All other files should be delivered in one client specific directory, so it is possible to do any customizing possible with images and css, also the background of the header.

With the unified skinning mechanism all files can then be addressed like the above CSS files.

### Additional HTML structure in the Editor Header (branding)

In addition to CSS and images above, own HTML structure can be added to the Editor header. Therefore the header provides an container where HTML can be added  by the following config in editorApplication.ini:

	runtimeOptions.editor.branding = 'OWN HTML'
	
The container containing the above HTML is addressable by the following CSS:

	#head-panel .head-panel-brand
	
This will not be changed for the unified skinning mechanism.

### Tag Images
Segment Tags are rendered in Translate5 as HTML or as images in edited segments. The HTML ones can be styled by using CSS, see above. For the images there are several configuration parameters. 
See therefore the DB config category *imagetag*. This category contains all necessary configurations.

#### MQM Image Tags
For MQM tagging also image tags are used. This images can also be configured like the above Image Tags. All the values existing for imageTags can also be changed for MQM Tags. **The Image Tag settings are the default values for MQM Tags!**
Per default some colors are different for MQM Tags, so the are listed in the DB Config category *qmimagetag*. The namespace for the mqm tag settings starts with: *runtimeOptions.imageTags.qmSubSegment.*

## Frame-pages

### CSS, image and other public files

The used CSS file by the default layout is defined in the following application.ini config:

	runtimeOptions.server.pathToCSS = '/css/translate5.css?v=2'
	runtimeOptions.server.pathToCSSDir = '/css'
	
The first config “pathToCSS” defines the CSS file included by the default layout, and can therefore be customized. Another way to add more or customize the included CSS files is to provide a customized layout.

The second config “pathToCSSDir” is obsolete.

Other public files like images and so on, can actually be copied by the deploy script to the place where they should be accessible.

With the unified skinning mechanism all files can then be addressed like the above Editor CSS files in: 

	public/client-specific/YourStructure/
	
### Layout

The layout can be changed in the application.ini by setting:

	resources.layout.layoutPath = APPLICATION_PATH "/layouts/scripts"
	resources.layout.layout = "layout"
	
After the config refactoring for the install-and-update-kit this setting will come from DB config.

For customizing we have to provide a layout.phtml on a own location, and configure it above.

By customizing the layout, own CSS can be supplied. For using the default layout see the above section about CSS and other public files.

With the unified skinning mechanism the layout can be customized by using the following config:  all files can then be addressed like the above Editor CSS files in: 

	resources.layout.layoutPath = APPLICATION_PATH "/client-specific/layouts"
	resources.layout.layout = "layout"
	
### View Scripts

View Scripts can currently not be changed / overwritten. A patch in the ViewSetup is therefore needed.

With the unified skinning mechanism the view script files can the be overwritten by rebuilding the default module view script folder in:

	public/client-specific/views/
	
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

## todo in this document
after implementing TRANSLATE-277 rework this document, so that only the valid documentation remains.