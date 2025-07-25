#!/usr/bin/php
<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
    "RELEASE" => "https://downloads.translate5.net/",
];

//The path to the md5hashtable which is used for comparing if a package was changed on server or not
$dep->md5hashtable = "RELEASE:md5hashtable";

//The path to the version file
$dep->versionfile = "RELEASE:version";

//basic application configuration
$dep->application = [
    "name" => "translate5",
    "label" => "Translate5 - latest version",
    "url" => "RELEASE:translate5.zip",
];

/*
 * For an explanation of each fields see the inline comments below!
 */

$dep->dependencies = [[
    "name" => "third-party-dependencies",               //used as internal name of the package / dependency
    "label" => "Third Party Dependencies pulled in by PHP composer",    //shown as name to the user
    "url" => "RELEASE:third-party-dependencies-7.0.0.zip",    //URL of the package to be downloaded
    "version" => "-na-",                                // currently not used, just for the sake of completeness
    "target" => "vendor/",                              //due to a bug in the downloader a target always must be given!
    // without a target the whole application is getting deleted!
    // that means we can provide only ZIP packages at the moment
    // with version 2.5.10 this is fixed, but lazy updaters will still have the problem!
    // so in near future target will be optional, and without a target
    // nothing will be unzipped, just downloaded
    "licenses" => [[
        // list of licenses to be confirmed for this package
        //"uses" => "several dependent libraries",        // is parsed into license title and agreement
        "usesFile" => "docs/third-party-licenses/third-party-dependency-license-overview.txt", //loads the content from the given filename, and places the content in the "uses" variable
        "relpath" => "docs/third-party-licenses/third-party-dependency-licenses.md",
        "title" => "License agreement for third party dependencies pulled in by PHP composer. Dependencies to be pulled:",
        "agreement" => '{USES}

  Please read the following license agreement and accept it for the third party dependencies.
                
  {RELPATH}
                
  You must accept the terms of this agreement for {LABEL} by typing "y" and <ENTER> before continuing with the installation.
  If you type "y", the translate5 installer will download and install the dependencies for you.{SUFFIX}',
        // relpath file is checked for existence,
        //  then the path (not the content) parsed into license agreement
        // agreement   optional, overwrites default agreement (defined in ZfExtended_Models_Installer_License)
        // title       optional, overwrites default title (defined in ZfExtended_Models_Installer_License)
    ]],
], [
    "name" => "extjs-62",
    "label" => "ExtJS (Version 6)",
    "version" => "6.2.0",
    "url" => "RELEASE:ext-6.2.0-gpl.zip",
    "target" => "public/ext-6.2.0",
    "licenses" => [[
        "uses" => "ExtJS",
        "license" => "GPL 3.0",
        "relpath" => "docs/third-party-licenses/ExtJs6-license.txt",
    ]],
], [
    "name" => "extjs-70",
    "label" => "ExtJS (Version 7)",
    "version" => "7.0.0",
    "url" => "RELEASE:extjs7-for-tmmaintenance.zip",
    "target" => "application/modules/editor/Plugins/TMMaintenance/public/resources/ext",
    "licenses" => [[
        "uses" => "ExtJS",
        "license" => "GPL 3.0",
        "relpath" => "docs/third-party-licenses/ExtJs7-license.txt",
    ]],
],
];

$dep->post_install_copy = [
    "vendor/fortawesome/font-awesome/css" => "public/modules/editor/fontawesome/css",
    "vendor/fortawesome/font-awesome/js" => "public/modules/editor/fontawesome/js",
    "vendor/fortawesome/font-awesome/webfonts" => "public/modules/editor/fontawesome/webfonts",
    "vendor/fortawesome/font-awesome/LICENSE.txt" => "public/modules/editor/fontawesome/LICENSE.txt",
    "vendor/jquery/jquery-ui" => "public/js/jquery-ui",
    "vendor/translate5/rangy-lib" => "public/js/rangy",
    "vendor/translate5/instanttranslate-roboto-font/" => "public/modules/editor/fonts/roboto",
    "vendor/google/material-design-icons" => "public/modules/editor/material-design-icons/material-design-icons",
    "vendor/google/material-design-icons/LICENSE" => "public/modules/editor/material-design-icons/LICENSE",
    'vendor/gportela85/datetimefield/src/DateTimeField.js' => 'public/modules/editor/js/ux/DateTimeField.js',
    'vendor/gportela85/datetimefield/src/DateTimePicker.js' => 'public/modules/editor/js/ux/DateTimePicker.js',
    'vendor/gportela85/datetimefield/src/LICENSE' => 'public/modules/editor/js/ux/LICENSE',
];

$dep = json_encode($dep, JSON_PRETTY_PRINT);
file_put_contents('dependencies.json', $dep);
