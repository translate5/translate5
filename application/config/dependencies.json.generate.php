#!/usr/bin/php
<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
     * Since long strings are hard to maintain in JSON (all in one line, no line breaks)
     * and no comments are possible in JSON, I decided to create a PHP based generator of
     * the dependencies JSON. The same config structure is just build up in PHP and saved to disk as JSON in the end
     *
     * Assoc arrays are used instead of stdClass for better readability.
     */

$dep = new stdClass();

//the idea was to provide different channes, the problem is, how to choose the channel!
// Conclusion: the default channel can come from the dep.json, but if the user wants another this must be configured elsewhere
$dep->channels = [
    "RELEASE" => "https://downloads.translate5.net/"
];

//The path to the md5hashtable which is used for comparing if a package was changed on server or not
$dep->md5hashtable = "RELEASE:md5hashtable";

//The path to the version file
$dep->versionfile = "RELEASE:version";

//basic application configuration
$dep->application = [
    "name" => "translate5",
    "label" => "Translate5 - latest version",
    "url" => "RELEASE:translate5.zip"
];

/*
 * For an explanation of each fields see the inline comments below!
 */

$dep->dependencies = [[
        "name" => "translate5-DB-init",                     //used as internal name of the package / dependency
        "label" => "translate5 DB init",                    //shown as name to the user
        "url" => "RELEASE:translate5-DB-init.zip",          //URL of the package to be downloaded
        "target" => "dbinit/"                               //due to a bug in the downloader a target always must be given!
                                                            // without a target the whole application is getting deleted!
                                                            // that means we can provide only ZIP packages at the moment
                                                            // with version 2.5.10 this is fixed, but lazy updaters will still have the problem!
                                                            // so in near future target will be optional, and without a target
                                                            // nothing will be unzipped, just downloaded
    ],[
        "name" => "third-party-dependencies",               // see above
        "label" => "Third Party Dependencies pulled in by PHP composer",    // see above
        "url" => "RELEASE:third-party-dependencies.zip",       // see above
        "version" => "-na-",                                // currently not used, just for the sake of completeness
        "target" => "vendor/",                              // unzip target, see above
        "licenses" => [[                                    // list of licenses to be confirmed for this package
            //"uses" => "several dependent libraries",        // is parsed into license title and agreement
            "usesFile"  => "docs/third-party-licenses/third-party-dependency-license-overview.txt", //loads the content from the given filename, and places the content in the "uses" variable
            "relpath"   => "docs/third-party-licenses/third-party-dependency-licenses.md",
            "title"     => "License agreement for third party dependencies pulled in by PHP composer. Dependencies to be pulled:",
            "agreement" => '{USES}

  Please read the following license agreement and accept it for the third party dependencies.
                
  {RELPATH}
                
  You must accept the terms of this agreement for {LABEL} by typing "y" and <ENTER> before continuing with the installation.
  If you type "y", the translate5 installer will download and install the dependencies for you.{SUFFIX}'
                                                            // relpath file is checked for existence,
                                                            //  then the path (not the content) parsed into license agreement
            // agreement   optional, overwrites default agreement (defined in ZfExtended_Models_Installer_License)
            // title       optional, overwrites default title (defined in ZfExtended_Models_Installer_License)
        ]]
    ],[
        "name" => "Open_Sans",
        "label" => "Open Sans Fonts",
        "url" => "RELEASE:Open_Sans.zip",
        "target" => "application/modules/editor/ThirdParty/Open_Sans/",
        "licenses" => [[
            "uses" => "the Open Sans font",
            "license" => "Apache License 2.0",
            "relpath" => "docs/third-party-licenses/Open_Sans-license.txt"
        ]]
    ],[
        "name" => "termtagger",
        "label" => "openTMS TermTagger",
        "url" => "RELEASE:openTMStermTagger.zip",
        "target" => "application/modules/editor/ThirdParty/XliffTermTagger/",
        "licenses" => [[
            "uses" => "the openTMS TermTagger",
            "license" => "Apache License 2.0",
            "relpath" => "docs/third-party-licenses/openTMStermTagger-license.txt"
        ],[
            "label" => "openTMS TermTagger libraries",
            "license" => "CDDL 1.1",
            "relpath" => "docs/third-party-licenses/CDDL-license.txt",
            "agreement" => 'Grizzly project and others license agreement description:
  Some of the libraries openTMS TermTagger builds on are licensed
  under the CDDL license. Please read the following license agreement
  and accept it for these libraries (like the Grizzly project and
  others). Which library uses which license is listed in the openTMS
  TermTagger installation directory which you will find in your
  translate5 application directory beneath

    application/modules/editor/ThirdParty/XliffTermTagger/

  after installation. You must accept the terms of this agreement for
  these components by typing "y" before continuing with the
  installation.'
        ]]
    ],[
        "name" => "opentm2",
        "label" => "OpenTM2",
        "url" => "RELEASE:OpenTM2-Community-Edition-Setup.zip",
        "target" => "OpenTM2-Installer",
        "version" => "1.5.1.1",
        "licenses" => [[
            "uses" => "OpenTM2 Community Edition",
            "license" => "Eclipse Public License 1.0",
            "relpath" => "docs/third-party-licenses/OpenTM2-Community-Edition-license.txt",
            "agreement" => 'translate5 uses {USES} (version {VERSION}).
  Please read the following license agreement and accept it for
  OpenTM2.

    {RELPATH}

  You must accept the terms of this agreement for {LABEL} by typing
  "y" and <ENTER> before continuing with the installation.
  If you type "y", the translate5 installer will download {LABEL}
  for you.

  !!!!!!!!!!!!! ATTENTION !!!!!!!!!!!!!!!!!!!!!!
    Since {LABEL} is executable only under Microsoft Windows
    Operating Systems the installer can only download it for you.
  !!!!!!!!!!!!! ATTENTION !!!!!!!!!!!!!!!!!!!!!!

  The Installation has to be started manually!

  Please stop an already running {LABEL} instance before
  installing / updating it!
  The downloaded file will be located in the folder
                
    "OpenTM2-Installer/"

  {SUFFIX}'
        ]]
    ],[
        "name" => "extjs-62",
        "label" => "ExtJS (Version 6)",
        "version" => "6.2.0",
        "url" => "RELEASE:ext-6.2.0-gpl.zip",
        "target" => "public/ext-6.2.0",
        "licenses" => [[
            "uses" => "ExtJS",
            "license" => "GPL 3.0",
            "relpath" => "docs/third-party-licenses/ExtJs6-license.txt"
        ]]
    ],[
        "name" => "extjs-ux",
        "label" => "ThirdParty ExtJS UX libraries",
        "version" => "6.2.0",
        "url" => "RELEASE:extjs-ux.zip",
        "target" => "public/modules/editor/js/ux",
        //"licenses" => licenses are confirmed with third-party-dependencies package, since UX packages are maintained there, but pulled in separatly to public directory here
    ],[
        "name" => "rangy",
        "label" => "Rangy",
        "version" => "1.3.1-dev",
        "url" => "RELEASE:rangy.zip",
        "target" => "public/js/rangy",
        "licenses" => [[
            "uses" => "Rangy",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/rangy-license.txt"
        ]]
    ],[
        "name" => "jquery-ui",
        "label" => "JQuery UI",
        "version" => "1.12.1",
        "url" => "RELEASE:jquery-ui.zip",
        "target" => "public/js/jquery-ui",
        "licenses" => [[
            "uses" => "JQuery",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/jquery-license.txt"
        ],[
            "uses" => "JQuery UI",
            "license" => "JQuery UI",
            "relpath" => "docs/third-party-licenses/jquery-ui-license.txt"
        ],[
            "uses" => "Future Imperfect by HTML5 UP",
            "license" => "CCA 3.0",
            "relpath" => "docs/third-party-licenses/jquery-ui-license.txt"
        ],[
            "uses" => "Skel",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/skel-license.txt"
        ]]
    ],[
        "name" => "tag-it",
        "label" => "Tag-it: a jQuery UI plugin",
        "version" => "master-2019-08-01",
        "url" => "RELEASE:tag-it.zip",
        "target" => "public/js/jquery-ui",
        "preventTargetCleaning" => true,
        "licenses" => [[
            "uses" => "Tag-it",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/tag-it-license.txt"
        ]]
    ],[
        "name" => "jquery-ui-iconfont",
        "label" => "Icons for jQuery-UI",
        "version" => "2.3.2",
        "url" => "RELEASE:jquery-ui-iconfont.zip",
        "target" => "public/js/jquery-ui",
        "preventTargetCleaning" => true,
        "licenses" => [[
            "uses" => "Icons for jQuery-UI",
            "license" => "CC BY-SA 3.0",
            "relpath" => "docs/third-party-licenses/jquery-ui-iconfont-README.md"
        ]]
    ]
];

$dep->post_install_copy = [
    "vendor/fortawesome/font-awesome/css" => "public/modules/editor/fontawesome/css",
    "vendor/fortawesome/font-awesome/js" => "public/modules/editor/fontawesome/js",
    "vendor/fortawesome/font-awesome/webfonts" => "public/modules/editor/fontawesome/webfonts",
    "vendor/fortawesome/font-awesome/LICENSE.txt" => "public/modules/editor/fontawesome/LICENSE.txt"
];

$dep = json_encode($dep, JSON_PRETTY_PRINT);
file_put_contents('dependencies.json', $dep);